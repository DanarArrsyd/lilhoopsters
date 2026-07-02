# Admin/Superadmin Top-Nav Redesign

## Goal

Replace the admin and superadmin sidebar shells with the same horizontal top-nav + avatar-menu theme already shipped on the parent portal, so all three roles share one visual language (navy/off-white, Public Sans, same component patterns). Scope: `components/admin.blade.php` + `components/admin-nav.blade.php` (admin, 19 pages) and `components/superadmin.blade.php` + `components/superadmin-nav.blade.php` (superadmin, 3 pages). Coach panel is out of scope for this pass.

## Architecture

Both shells are single Blade components that every page renders through (`<x-admin>...</x-admin>` / `<x-superadmin>...</x-superadmin>`), and `{{ $slot }}` content is untouched by this change. Because the shell is centralized (unlike the parent portal, which had 3 separate custom shells), swapping the shell markup cascades to every admin/superadmin page automatically — no per-page edits needed.

## Desktop (≥lg)

Header layout matches parent portal exactly: `grid grid-cols-[1fr_auto_1fr]` — logo left, nav centered, actions right (locale-switcher, notification-bell [admin only, superadmin currently has none — keep as-is], avatar-menu).

**Admin nav** — 6 top-level items, reusing the exact grouping/labels already defined in `admin-nav.blade.php`'s `<x-sidebar-section>` blocks:
- Dashboard (direct link)
- People ▾ — Parents, Players, Leads, Coaches, Members Import
- Programs ▾ — Locations, Programs, Packages, Schedules, Events
- Operations ▾ — Attendances, Leave Requests, Makeup Classes, Enrollments
- Finance ▾ — Payments, Reports, Owner Insights
- Reports ▾ — Report Cards, News

Each `▾` is a click-triggered dropdown panel (Alpine, `@click.outside` to close) containing `<x-sidebar-link>` items — that component is reused as-is (works fine on a white dropdown background). Badge counts (`$navBadges['parents']`, `leave_requests`, `makeup_classes`, `enrollments`, `report_cards`) stay wired the same way, just relocated into the dropdown items.

**Superadmin nav** — only 3 items total, no dropdown needed: Dashboard | Admin Accounts | System Settings, flat links styled like parent's `<x-portal.top-nav>`.

## Avatar-menu

New admin-scoped dropdown component (same interaction pattern as `<x-portal.avatar-menu>`): click avatar initial → panel with **Manage Profile** (→ `admin.profile`) and **Sign Out**. No "User Guide" item for admin in this pass. Superadmin has no profile page currently — dropdown there is just Sign Out (verify no `superadmin.profile` route exists first; if none, omit Manage Profile for superadmin).

## Mobile (<lg)

Topbar collapses to: logo + hamburger + avatar-menu (locale-switcher and notification-bell move inside the drawer to keep the topbar uncluttered). Tapping the hamburger opens a full-screen drawer overlay (not a persistent sidebar) themed with the same navy/off-white tokens. Drawer content reuses `<x-sidebar-section>` (already has expand/collapse + auto-open-on-active-child behavior) per group, with `<x-sidebar-link>` items inside — i.e., the existing sidebar partial content moves into this drawer largely unchanged. Tapping a link or tapping outside the drawer closes it.

## Rollout

1. **Stage 1 — Admin shell**: rewrite `admin.blade.php` (drop sidebar/collapse logic, add top-nav grid + mobile drawer scaffold) and `admin-nav.blade.php` (regroup into dropdown-friendly + drawer-friendly markup, reusable for both desktop dropdowns and mobile drawer accordions). Verify all 19 admin pages render, badges show, active-state highlighting works, `php artisan test` passes.
2. **Stage 2 — Superadmin shell**: same treatment for `superadmin.blade.php` + `superadmin-nav.blade.php` (flat nav, no dropdown). Verify all 3 superadmin pages render.
3. **Stage 3 — Full verification**: full test suite, manual browser check of dashboard + one page per group + profile page + mobile drawer + dropdown badges, commit per stage.

## Testing

No new automated tests planned beyond running the existing suite after each stage (shell change is presentational; existing route/page tests already assert `assertOk()` / content assertions per admin page, which will catch structural breakage). Manual browser verification covers dropdown open/close, drawer open/close, badge visibility, active-link highlighting, and avatar-menu sign-out.
