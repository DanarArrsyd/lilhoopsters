# Admin Content Layout Parity (Profile + Dashboard)

## Goal

Bring the admin Profile and Dashboard page *content* layouts into parity with their parent-portal counterparts (Profile, Home) — same max-width container, same 2-column arrangement with a right sidebar, filling the dead right-hand whitespace admin pages currently have. Data and Livewire logic are unchanged; only Blade markup is reframed. This is the first slice ("Profile + Dashboard first") of a larger layout-parity effort; remaining admin pages come later.

## Scope

- `resources/views/livewire/admin/profile.blade.php` — reframe to parent-profile 2-column layout.
- `resources/views/livewire/admin/dashboard.blade.php` — reframe to parent-Home grid arrangement (content left, calendar right), extracting the weekly-calendar card into a reusable component.
- `resources/views/components/admin/week-calendar.blade.php` — NEW component, the extracted weekly-calendar card (rendered twice: mobile-top + desktop-right), mirroring how the parent portal extracted `week-strip`/`calendar-panel`.
- `lang/en/messages.php` + `lang/id/messages.php` — a handful of new `messages.admin.profile.*` keys for the right-sidebar strings.

Out of scope: any change to `app/Livewire/Admin/Dashboard.php` / `Profile.php` (public properties, methods, queries all untouched), the month-calendar modal behavior, and all other admin pages.

## Profile design

Mirror `resources/views/livewire/portal/profile.blade.php` exactly:
- Wrapper `max-w-6xl mx-auto`, page header above the grid.
- `grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px] lg:items-start`.
- Left column (`space-y-6 min-w-0`): the existing Personal Information card, Change Password card, and `<livewire:admin.payment-accounts />` — unchanged.
- Right column (`hidden lg:block lg:sticky lg:top-20 space-y-6`):
  - **Account Overview** card: avatar initial, name, email, then a divided list with `Role` (Administrator / Super Admin, chosen via `match($user->role?->name)`) and `Member since` (`created_at->format('M Y')`).
  - **Quick Links** card: three links reusing `messages.admin.nav.*` labels — Dashboard (`admin.dashboard`), Coaches (`admin.coaches`), Payments (`admin.payments`).

New lang keys under `messages.admin.profile`: `account_overview`, `role`, `member_since`, `quick_links`, `role_admin`, `role_superadmin`.

## Dashboard design

Mirror `resources/views/livewire/portal/home.blade.php`'s grid arrangement:
- Wrapper `max-w-6xl mx-auto` (the admin shell's `<main>` already supplies padding).
- Page header above the grid (unchanged text).
- `lg:grid lg:grid-cols-3 lg:gap-6 lg:items-start`.
- Left (`lg:col-span-2 space-y-6`): the existing Quick Access grid + Today's Activity card, unchanged.
- Right (`hidden lg:block lg:sticky lg:top-20`): `<x-admin.week-calendar>`.
- Mobile: `<x-admin.week-calendar>` rendered once inside a `lg:hidden` block above the left content (preserves today's mobile ordering: calendar first).
- Month-calendar modal stays exactly where it is (still gated by `$showCalendar && $calendar`, still uses `openCalendar`/`prevMonth`/`nextMonth`/`selectDate`).

The extracted `<x-admin.week-calendar>` receives the data it needs as props: `:week-days`, `:schedules-by-day`, `:today-schedules`. All three are already computed in `Dashboard.php::render()` and currently passed to the view — the component just relocates the existing markup verbatim, swapping the local `$weekDays`/`$schedulesByDay`/`$todaySchedules` references for the passed props. The `openCalendar` wire:click button moves with it (Livewire wire:click resolves against the parent component regardless of Blade component nesting, same as the parent portal's calendar-panel).

## Testing

No new automated tests. Verification: `php artisan test` (expect 345 pass, unchanged — presentational only) plus manual browser check of `/admin/dashboard` and `/admin/profile` on desktop (2-column, calendar on right, sidebar cards render) and mobile-width (single column, calendar on top, no horizontal overflow). Build with `npm run build` after edits.
