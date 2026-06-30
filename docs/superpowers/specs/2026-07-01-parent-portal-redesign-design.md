# Parent Portal Redesign — Design Spec

**Branch:** `feat/i18n-admin` (or new branch — TBD at plan time)
**Scope:** Phase 1 of the broader "total redesign" effort. Covers the parent portal only. Admin portal redesign is a separate, later sub-project.

## Goal

The parent portal currently has 11+ separate top-level pages (dashboard, players, events, news, payments, leaves, attendance, makeup, report-cards, profile, enroll, private). Parents — who are not necessarily tech-savvy — have to navigate between many pages to see information about one child. The redesign consolidates child-specific data into one scannable home page per child, while keeping the existing visual design system (navy/off-white two-tone, Public Sans + IBM Plex Mono, established in this session) but applying it more deliberately to this page.

## Out of scope

- Admin portal redesign (separate sub-project, later)
- Coach portal redesign (not requested)
- Backend/model changes — this is a presentation-layer and routing change only. Existing Livewire components' query logic is reused, not rewritten.
- New features beyond what currently exists (no new data types, no new actions)

## Information architecture

**Before:** 11 nav-accessible routes (`dashboard`, `players`, `events`, `news`, `payments`, `leaves`, `attendance`, `makeup`, `report-cards`, `profile`, `enroll`, `private`).

**After:** 3 bottom-nav items + 2 action-only routes.

| Route | Nav? | Notes |
|---|---|---|
| `parent.home` (new) | Yes — replaces `dashboard` | Landing page, per-child consolidated view |
| `parent.news` | Yes | Unchanged — academy-wide announcements, not child-specific |
| `parent.profile` | Yes | Unchanged |
| `parent.enroll` | No (button from Home) | Unchanged content, just removed from nav |
| `parent.dashboard` | N/A | Redirects (301) to `parent.home` so old bookmarks/links don't 404 |

Routes removed from nav entirely (content absorbed into Home as inline sections): `players`, `events`, `payments`, `leaves`, `attendance`, `makeup`, `report-cards`, `private`. Their Livewire components are **not deleted** — they're embedded into Home as child components/modals (see Components section).

## Home page composition

Single scrollable page, sections in this fixed priority order (confirmed with stakeholder):

1. **Header** — greeting + child switcher (pill selector, only shown if parent has >1 child)
2. **Next session** — highlight card: program name, coach, location, time. "View full schedule" expands a weekly list inline (no navigation)
3. **Payment status** — active package summary, amount due/paid badge. "Payment history" expands inline
4. **Attendance & progress strip** — compact present/absent count this month, link to report card if one exists for the active period
5. **Quick actions** — 3 buttons: Ajukan Izin (leave request), Kelas Pengganti (makeup), Sesi Privat (private session) — each opens the existing wizard Livewire component in a modal
6. **Active event** — conditionally rendered only if there's an open-registration event scoped to the child's program/location

All "view more" actions expand **inline** (Alpine `x-collapse`/`x-show`), never navigate to a separate page. This is the core UX principle distinguishing this from the old multi-page structure.

## Components

**New:**
- `app/Livewire/Portal/Home.php` — primary Livewire component, replaces `Dashboard`. Properties: `activeChildId` (int, persisted via Livewire's `wire:model` + session for the duration of the visit). Computed property `child` loads the active child with eager-loaded relations (`enrollments.program`, `schedules`, `payments`, `attendances`, `leaveRequests`). Method `switchChild(int $id)` re-resolves the computed property — no extra query round-trip beyond what eager loading already does.
- Blade section components, each a pure presentational component receiving `$child` as a prop (not separate Livewire components, to avoid N+1 network round-trips for a single-page view):
  - `resources/views/components/portal/schedule-card.blade.php`
  - `resources/views/components/portal/payment-card.blade.php`
  - `resources/views/components/portal/attendance-strip.blade.php`
  - `resources/views/components/portal/quick-actions.blade.php`
  - `resources/views/components/portal/event-banner.blade.php`

**Reused as-is (embedded, not rewritten):**
- `App\Livewire\Portal\LeaveRequests` (existing 3-step wizard) — rendered via `@livewire('portal.leave-requests', ['childId' => $child->id], key('leave-'.$child->id))` inside a modal triggered from Quick Actions
- `App\Livewire\Portal\PrivateSessions` — same pattern
- `App\Livewire\Portal\MakeUpClasses` — same pattern

**Deprecated (kept in codebase, removed from nav):**
- `App\Livewire\Portal\Dashboard`, `MyPlayers`, `Events` (parent-facing), `Payments`, `AttendanceHistory`, `ReportCards` — their query/display logic gets extracted into the new Blade section components above rather than duplicated. If a component becomes fully redundant after extraction, remove it at plan/implementation time (not a spec-time decision — verify no other route uses it first).

## Data flow

`Home::mount()` resolves the parent's children once. `activeChildId` defaults to the first child (or the last-viewed child, stored in session, if returning). Switching children only re-evaluates the computed `child` property — it does not re-fetch the children list. All section data (schedule, payments, attendance) comes from the single eager-loaded `child` model graph; no section makes its own query.

## Error / empty states

| Condition | Behavior |
|---|---|
| Parent has zero children | Full-page empty state: "Daftarkan anak pertama" + button to `parent.enroll` |
| Active child has no active schedule | Schedule section shows its own empty state ("Belum ada jadwal aktif"); other sections render normally |
| Active child has no payment history | Payment section shows "Belum ada riwayat pembayaran" |
| A section's query throws | Caught per-section (not page-wide) — section shows "Gagal memuat data, coba lagi" with a refresh action; rest of the page stays usable |

## Testing

New: `tests/Feature/Portal/HomeTest.php` covering:
- Renders with one child, all sections show correct data
- Renders with multiple children, switcher works, switching changes displayed data
- Empty states (zero children, child with no schedule, no payments)
- Quick action modals open and submit through existing wizard components correctly
- `parent.dashboard` redirects to `parent.home`

Existing tests (`AttendanceTest`, `PaymentsTest`, `LeaveRequestsTest`, etc. under `tests/Feature/Portal/`) are reviewed at implementation time: assertions against routes that no longer exist in nav get migrated into `HomeTest` (testing the same underlying behavior through the new page), not deleted outright. Assertions against the underlying Livewire wizard components (leave request submission logic, etc.) stay where they are since those components are unchanged.

## Rollout

Direct replacement, no feature flag. Old nav-accessible routes removed in the same change that ships Home. `parent.dashboard` becomes a redirect. Sidebar/bottom-nav updated to 3 items (Home, News, Profile) in the same change — partial rollout (new page live, old nav still showing 11 items) would be confusing, so this ships as one atomic change.

## Visual system (already in place, no new work needed)

Font: Public Sans (headings + body) + IBM Plex Mono (numbers) — already swapped in `resources/views/components/app.blade.php` and `resources/css/app.css`. Color tokens: navy `#1A2F5E` / off-white `#F4F7FC` two-tone, already defined in `@theme`. This spec reuses both without modification. Component visual style (cards, badges, status chips) follows the mockup direction approved earlier in this session: ledger-style sections (`border-top` + `divide-y` for dense lists, e.g. payment history), small saturated status chips (not full-color blocks), `font-mono` for all currency/numeric values.
