# BasketManage V2 — Frontend Redesign Design Spec

**Date:** 2026-06-10
**Status:** Approved for planning
**Scope:** Total frontend redesign of all Blade views & Livewire component templates. No backend changes (244 tests must stay green).

---

## 1. Goal & Constraints

Redesign every page of the Lil' Hoopsters Basketball Academy management system with a single, cohesive design language. Backend, routes, models, and Livewire PHP classes are complete and frozen — this work touches **Blade templates, layout components, navigation components, and `resources/css/app.css` only**. Where a flow change requires a Livewire property/method (e.g. selecting the active child on the consolidated portal page), the change must be additive and must not break existing tests.

**Hard constraints:**
- Stack stays: Laravel 12, Livewire 3, Tailwind CSS 4 (`@tailwindcss/vite`), Alpine.js.
- No new JS build dependencies unless explicitly approved (QR scanning may already use a library — verify before adding).
- All existing routes and route names remain valid.
- All 244 backend tests remain green.

---

## 2. Design System (foundations)

### 2.1 Atmosphere
Clean, confident, sporty-urban productivity tool. Energy comes from **heavy weight, uppercase headings, tight tracking, and strong navy/off-white contrast** — not from a special font or bright accent. Density: balanced (admin/coach lean denser; parent portal airier, card-based, mobile-first).

### 2.2 Color palette — strictly two-tone + functional status
Defined as Tailwind v4 `@theme` tokens in `resources/css/app.css`.

| Token | Hex | Role |
|---|---|---|
| `--color-navy` | `#0A0F1E` | Primary brand, sidebar, primary buttons, headings, dark accent surfaces |
| `--color-navy-2` | `#141B2E` | Elevated dark surface (e.g. portal header band, sidebar hover) |
| `--color-off` | `#FAF9F6` | Primary background (off-white) |
| `--color-surface` | `#FFFFFF` | Cards, inputs, raised containers |
| `--color-line` | `#E6E3DC` | Borders, 1px dividers |
| `--color-text-1` | `#0A0F1E` | Primary text (= navy) |
| `--color-text-2` | `#6B7280` | Secondary text, metadata, labels |
| `--color-text-3` | `#9AA0AC` | Tertiary / disabled |

**Status colors — used only in small doses (chips, dots, badges), never as decoration:**

| Token | Hex | Role |
|---|---|---|
| `--color-success` | `#15803D` (text) / `rgba(22,163,74,.14)` (bg) | Paid, present, approved |
| `--color-warning` | `#B45309` (text) / `rgba(217,119,6,.14)` (bg) | Pending, awaiting |
| `--color-danger` | `#B91C1C` (text) / `rgba(220,38,38,.14)` (bg) | Overdue, rejected, absent |
| `--color-info` | `#1D4ED8` (text) / `rgba(37,99,235,.12)` (bg) | Neutral informational |

No bright brand accent. No purple/neon. No gradients on headings.

### 2.3 Typography
- **Font stack:** `Arial, Helvetica, sans-serif` (system — no web font request). Drop the Google Fonts Inter `<link>` and the `--font-family-sans` Inter reference.
- **Headings:** heavy weight (700–800), `UPPERCASE`, tracking tight (`-0.02em`), `clamp()` sizing for fluid scale. Hierarchy via weight + size + color.
- **Body:** regular/medium, normal case, secondary color for metadata. Min 14px.
- **Numbers/stats:** bold, large, navy. (No mono font — keep it system-standard per user preference.)

### 2.4 Components (Blade `<x-...>` components, reused everywhere)
Existing component files to redesign (keep names so views don't break): `btn`, `card`, `badge`, `input`, `alert`, `empty-state`, `sidebar-link`, `sidebar-section`.

- **Buttons (`x-btn`):** variants `primary` (navy fill, off-white text), `secondary`/`ghost` (transparent, navy border+text), `danger`. Uppercase, tracking `.03em`, radius `~11px`, tactile `active:translate-y-px`. Min 44px tap target.
- **Cards (`x-card`):** white surface, `--color-line` border, radius ~14px, soft diffused shadow tinted toward navy. Optional header slot.
- **Badge/Chip (`x-badge`):** small uppercase pill; variants `navy` (solid), `outline`, `success`, `warning`, `danger`, `info`.
- **Inputs (`x-input`):** label above, white fill, `--color-line` border, navy focus ring, error text below. No floating labels.
- **Empty states (`x-empty-state`):** composed illustration/icon + message + primary action, not bare "No data".
- **Tables:** header row uppercase small label text; row hover tint; on mobile collapse to stacked cards (see responsive rules).
- **Loading:** skeleton blocks matching layout, not spinners.

### 2.5 Responsive rules (mobile-first, all screens)
- Authored mobile-first; scale up with `sm: md: lg:` breakpoints.
- **< 768px:** every multi-column layout collapses to single column. No horizontal scroll anywhere.
- **Sidebars:** off-white background with `--color-line` right border; **active nav item = navy fill, off-white text**, inactive = navy text on transparent with subtle hover tint. Off-canvas drawer on mobile (existing hamburger pattern kept), fixed on `lg:`.
- **Parent portal mobile:** bottom tab nav for thumb reach + off-canvas for secondary items.
- **Tables → cards** on mobile.
- Headlines via `clamp()`; section vertical gaps via `clamp(2rem, 6vw, 4rem)`.
- Full-height shells use `min-h-[100dvh]`, never `h-screen`.
- All interactive elements ≥ 44px tap target.

### 2.6 Layout shells (`resources/views/components/`)
All four shells (`admin`, `coach`, `parent-portal`, `superadmin`) and `auth` move to the **light theme**: off-white content background, **off-white sidebar** (with a `--color-line` right border) whose **active item is navy-filled**. Topbar white. `auth` becomes a light card on a soft neutral/off-white backdrop with prominent navy logo (replacing the current dark gradient).

> Decision: per the approved direction, everything is light — including the sidebar (off-white, navy active item). Navy is used for the active nav state, primary buttons, headings, and small accent surfaces. The parent portal may use a navy header *band* on mobile for contrast — confirmed acceptable.

---

## 3. Structural / Flow Changes

### 3.1 Parent Portal — consolidated per-child page
**Today:** info is split across separate pages (My Players, Attendance, Leave Requests, Make-Up, Report Cards, Payments).
**Target:** a single **"My Player"** page that shows everything for one selected child — child switcher at top (chips/tabs), then sections: next schedule, attendance summary, report card snapshot, invoices/payments, leave & make-up. Quick actions (Pay, Request Leave, Book Make-Up) surfaced as buttons. Dedicated pages remain reachable as "view all / history" drill-downs, but the dashboard-level experience is consolidated.
- Requires an additive Livewire concern for "active child" selection (property + method) on the portal dashboard/my-player component. Verify against `App\Livewire\Portal\*` and existing tests; keep additive.
- Mobile: bottom tab nav (Home · Attendance · Pay · Profile) + child switcher.

### 3.2 Admin — grouped navigation
Re-group `admin-nav` into five sections (replacing current Overview/Data Setup/People/Operational/Approvals):
- **People:** Parents, Players, Coaches
- **Programs:** Locations, Programs, Packages, Schedules
- **Operations:** Attendances, Leave Requests, Make-Up Classes, Enrollments
- **Finance:** Payments
- **Reports:** Report Cards
- (Dashboard stays as a top "Overview" entry above the groups.)

### 3.3 Coach — QR Scanner as dashboard quick action
- Coach dashboard gets a prominent **"Scan QR Check-in"** quick action (primary button / hero card) plus secondary quick actions (Take Attendance, View Roster).
- `coach-nav` labels translated to English ("Kehadiran" → "Attendance", "Roster Harian" → "Daily Roster").

### 3.4 Language
All UI copy → **full native English** across every view (several views/nav currently mix Indonesian). Replace as part of each page's redesign.

---

## 4. Rollout (incremental, page-by-page)

We build **one page at a time**, each its own task, verifiable in the browser before moving on. We start with **Login**. Foundations (tokens + the handful of components a page needs) are introduced lazily — established the first time a page needs them, then reused.

**Task 1 — Login page (first).** Redesign `auth/login.blade.php` + the `auth` layout shell. This task also lands the *minimal* foundations the login page depends on, so they're ready for everything after:
- `app.css` tokens: two-tone palette + status colors + Arial stack; drop the Inter `<link>` and Inter token.
- `x-btn` (primary/secondary) and `x-input` redesigned.
- `auth` shell: light backdrop, prominent navy logo, light card, English copy.

**Subsequent tasks (one page each, order flexible):**
2. Register wizard, 3. Pending page.
4. Shared shells + nav (admin off-white sidebar w/ grouped nav, coach off-white sidebar w/ English labels, superadmin, parent-portal) + remaining shared components (card, badge, alert, empty-state, sidebar-link/section) — folded in as the first admin/coach/portal page is built.
5+. Admin pages (dashboard, then list/CRUD pages), Superadmin pages.
N. Coach pages (dashboard w/ QR quick action, qr-scanner, check-in, roster, schedules, take-attendance, report-cards).
N. Parent portal: consolidated per-child page + drill-down pages, mobile bottom-nav.

Each task gets verified (build + render + tests green) before the next. We'll write a focused implementation plan for **Task 1 (Login)** now; later tasks get planned as we reach them.

---

## 5. Verification

- After each phase: `npm run build` succeeds; `php artisan serve` renders affected pages with no Blade/console errors; backend test suite stays green (`php artisan test`).
- Manual responsive check at ~375px, ~768px, ~1280px for redesigned pages (no horizontal scroll; nav collapses correctly).
- Visual spot-check against the locked direction (two-tone, Arial, uppercase headings, status chips).

---

## 6. Out of Scope

- Backend logic, validation, routes, model changes (beyond additive Livewire view-state for the consolidated portal page).
- New features beyond the three flow changes above.
- Dark-mode theme toggle.
- Replacing Arial with a web/display font (explicitly declined by user).
