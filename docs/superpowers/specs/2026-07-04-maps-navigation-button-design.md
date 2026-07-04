# Maps Navigation Button — Design

**Date:** 2026-07-04
**Pages:** parent `parent/home` (week/session cards), coach `coach/dashboard`, `coach/schedules`, `coach/check-in`, admin `admin/dashboard` (week calendar)

## Goal

Wherever a session card shows a location, add a small button that opens that location's `Location.maps_url` in a new tab — so parents, coaches, and admins can jump straight to navigation instead of copy-pasting the address. Explicitly out of scope: data-management tables (`admin/locations`, `admin/schedules`), PDF receipts, and other non-"go here now" contexts (payments, leads, enrollments) — those show location as reference data, not as a place to travel to.

## 1. Reusable component

New Blade component `resources/views/components/maps-button.blade.php`:

```blade
@props(['url' => null])

@if ($url)
    <a href="{{ $url }}" target="_blank" rel="noopener"
       {{ $attributes->merge(['class' => 'inline-flex items-center justify-center text-navy/60 hover:text-navy transition-colors shrink-0']) }}
       title="{{ __('messages.common.open_in_maps') }}"
       aria-label="{{ __('messages.common.open_in_maps') }}"
       onclick="event.stopPropagation()">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
    </a>
@endif
```

- Renders nothing if `maps_url` is null — a location without a maps link degrades silently to today's plain-text look, no broken link, no placeholder.
- Reuses the existing map-pin icon path already used in `locations.blade.php`'s geofence hint, so it reads as the same "location/map" symbol across the app.
- `event.stopPropagation()` matters where the button sits inside a larger clickable row (e.g. a schedule row that might get a future click handler) — clicking the pin must not also trigger the row.
- `target="_blank" rel="noopener"` — opens Google Maps in a new tab, doesn't navigate the parent/coach away from their session list.

## 2. Data plumbing (parent only)

`app/Support/ChildSchedulePlanner.php`'s `nextSession()` and `weekSessions()` currently flatten each session to an array with `'location' => $enrollment->schedule->location->name` (string only). Add a sibling key:

```php
'location_maps_url' => $enrollment->schedule->location->maps_url,
```

at both call sites (`nextSession()` line ~78, `weekSessions()` line ~100). Everywhere else in this spec's scope (coach dashboard/schedules/check-in, admin week-calendar), the view already has the full `Schedule` model in scope (`$sched->location->maps_url`) — no plumbing needed, just call the component directly.

## 3. Placement

In every location listed below, the button sits immediately after the location name text, same line, small, secondary to the text (per the pattern already used for badges like the "PVT" tag on coach dashboard):

- `resources/views/components/portal/week-card.blade.php:87` (today's session) and `:218` (rest-of-week list) — `<x-maps-button :url="$sess['location_maps_url']" />` / `<x-maps-button :url="$enr->schedule->location->maps_url" />`
- `resources/views/livewire/coach/dashboard.blade.php:83, :198, :306`
- `resources/views/livewire/coach/schedules.blade.php:31`
- `resources/views/livewire/coach/check-in.blade.php:58, :103`
- `resources/views/components/admin/week-calendar.blade.php:78`

## 4. Translation

Add `messages.common.open_in_maps` to both `lang/en/messages.php` and `lang/id/messages.php` (used as the button's `title`/`aria-label`):
- EN: "Open in Maps"
- ID: "Buka di Maps"

## Out of scope

- `admin/locations`, `admin/schedules` tables (reference/management views, not "go here" views).
- `pdf/payment-receipt.blade.php`, `admin/payments.blade.php`, `admin/leads.blade.php`, `admin/enrollments.blade.php` — location is secondary/reference data there, not the page's purpose.
- `portal/enroll-player.blade.php`, `portal/private-sessions.blade.php`, `portal/makeup-classes.blade.php`, `portal/leave-requests.blade.php`, `portal/events.blade.php` — these are browsing/booking flows (choosing a schedule), not "you have a session, go now" cards. Can be added later if requested; excluded here to keep this change scoped to the exact request.
- No change to the `Location` model or database — `maps_url` already exists and is already nullable.
