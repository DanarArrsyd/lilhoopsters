# Maps Navigation Button Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a small "open in maps" icon button next to every location name shown on a session card (parent home, coach dashboard/schedules/check-in, admin dashboard week calendar), linking to that location's `maps_url`.

**Architecture:** One new reusable Blade component (`x-maps-button`) that renders nothing when `maps_url` is null, one data-plumbing change in `ChildSchedulePlanner` (the only place location is flattened to an array instead of passed as a model), and mechanical insertions of the component wherever a location name is already rendered in the plan's scope.

**Tech Stack:** Laravel 11, Blade components, Pest.

## Global Constraints

- Component renders nothing (no broken link/placeholder) when `maps_url` is null — per spec section 1.
- Button uses `target="_blank" rel="noopener"` and `onclick="event.stopPropagation()"` — per spec section 1.
- Reuses the existing map-pin SVG path already used in `locations.blade.php`'s geofence hint — per spec section 1.
- Placement: immediately after the location name text, same line — per spec section 3.
- Scope is exactly: `portal/week-card.blade.php` (today + rest-of-week), `coach/dashboard.blade.php` (3 sections), `coach/schedules.blade.php`, `coach/check-in.blade.php` (2 sections), `admin/week-calendar.blade.php`. No other file changes — per spec section 3 and "Out of scope".
- Translation key `messages.common.open_in_maps` — EN "Open in Maps", ID "Buka di Maps" — per spec section 4.

---

### Task 1: Component + data plumbing + parent week-card

**Files:**
- Create: `resources/views/components/maps-button.blade.php`
- Modify: `app/Support/ChildSchedulePlanner.php:76-83` (`nextSession()`), `:98-103` (`weekSessions()`)
- Modify: `resources/views/components/portal/week-card.blade.php:82-92` (today's sessions), `:208-219` (rest-of-week/selected-day sessions)
- Modify: `lang/en/messages.php`, `lang/id/messages.php`
- Test: `tests/Feature/Portal/HomeTest.php`

**Interfaces:**
- Produces: `<x-maps-button :url="..." />` component — consumed by this task's own files and by Task 2's files (same component, no shared state).
- Produces: `ChildSchedulePlanner::nextSession()` and `weekSessions()` array shape gains a `location_maps_url` key (string|null) alongside the existing `location` string key.

- [ ] **Step 1: Write the failing test**

Add to `tests/Feature/Portal/HomeTest.php` (append at end of file):

```php
it('shows a maps button next to the next session location when the location has a maps url', function () {
    $child = Child::factory()->create(['user_id' => $this->parent->id, 'status' => 'active']);
    $location = \App\Models\Location::factory()->create(['maps_url' => 'https://maps.google.com/?q=test-location']);
    $program  = \App\Models\Program::factory()->create();
    $schedule = \App\Models\Schedule::factory()->create([
        'location_id' => $location->id,
        'program_id'  => $program->id,
        'day_of_week' => strtolower(now()->format('l')),
        'is_active'   => true,
    ]);
    \App\Models\Enrollment::factory()->create([
        'child_id' => $child->id, 'schedule_id' => $schedule->id,
        'type' => 'program', 'status' => 'approved',
        'started_at' => now()->subMonth(), 'expires_at' => now()->addMonth(),
    ]);

    Livewire::actingAs($this->parent)
        ->test(Home::class)
        ->assertSee('https://maps.google.com/?q=test-location', false);
});

it('shows no maps button when the location has no maps url', function () {
    $child = Child::factory()->create(['user_id' => $this->parent->id, 'status' => 'active']);
    $location = \App\Models\Location::factory()->create(['maps_url' => null]);
    $program  = \App\Models\Program::factory()->create();
    $schedule = \App\Models\Schedule::factory()->create([
        'location_id' => $location->id,
        'program_id'  => $program->id,
        'day_of_week' => strtolower(now()->format('l')),
        'is_active'   => true,
    ]);
    \App\Models\Enrollment::factory()->create([
        'child_id' => $child->id, 'schedule_id' => $schedule->id,
        'type' => 'program', 'status' => 'approved',
        'started_at' => now()->subMonth(), 'expires_at' => now()->addMonth(),
    ]);

    Livewire::actingAs($this->parent)
        ->test(Home::class)
        ->assertDontSee(__('messages.common.open_in_maps'));
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter "shows a maps button next to the next session location|shows no maps button when the location has no maps url"`
Expected: FAIL — `messages.common.open_in_maps` doesn't exist yet (translation missing) and the maps URL never appears in the rendered output (component doesn't exist yet, `location_maps_url` key doesn't exist yet).

- [ ] **Step 3: Create the component**

Create `resources/views/components/maps-button.blade.php`:

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

- [ ] **Step 4: Add the translation key**

In `lang/en/messages.php`, inside the `'common' => [...]` array (find it with `grep -n "'common' => \[" lang/en/messages.php`), add:

```php
        'open_in_maps' => 'Open in Maps',
```

In `lang/id/messages.php`, inside its own `'common' => [...]` array (find it with `grep -n "'common' => \[" lang/id/messages.php` — key order may differ from the English file), add:

```php
        'open_in_maps' => 'Buka di Maps',
```

- [ ] **Step 5: Add `location_maps_url` to `ChildSchedulePlanner`**

In `app/Support/ChildSchedulePlanner.php`, replace:

```php
                return [
                    'program'  => $enrollment->schedule->program->name,
                    'location' => $enrollment->schedule->location->name,
                    'coach'    => $enrollment->schedule->coach?->user?->name,
                    'date'     => $date,
                    'start'    => $enrollment->schedule->start_time,
                    'end'      => $enrollment->schedule->end_time,
                ];
```

with:

```php
                return [
                    'program'           => $enrollment->schedule->program->name,
                    'location'          => $enrollment->schedule->location->name,
                    'location_maps_url' => $enrollment->schedule->location->maps_url,
                    'coach'             => $enrollment->schedule->coach?->user?->name,
                    'date'              => $date,
                    'start'             => $enrollment->schedule->start_time,
                    'end'               => $enrollment->schedule->end_time,
                ];
```

Replace:

```php
                $sessions = self::sessionsOn($enrollments, $date)->map(fn (Enrollment $e) => [
                    'program'  => $e->schedule->program->name,
                    'location' => $e->schedule->location->name,
                    'start'    => $e->schedule->start_time,
                    'end'      => $e->schedule->end_time,
                ]);
```

with:

```php
                $sessions = self::sessionsOn($enrollments, $date)->map(fn (Enrollment $e) => [
                    'program'           => $e->schedule->program->name,
                    'location'          => $e->schedule->location->name,
                    'location_maps_url' => $e->schedule->location->maps_url,
                    'start'             => $e->schedule->start_time,
                    'end'               => $e->schedule->end_time,
                ]);
```

- [ ] **Step 6: Wire the component into `week-card.blade.php`**

Replace:

```blade
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-ink truncate">{{ $sess['program'] }}</p>
                            <p class="text-[10px] text-faint truncate">{{ $sess['location'] }}</p>
                            <p class="text-[10px] text-muted font-semibold">
                                {{ \Illuminate\Support\Carbon::parse($sess['start'])->format('H:i') }}–{{ \Illuminate\Support\Carbon::parse($sess['end'])->format('H:i') }}
                            </p>
                        </div>
```

with:

```blade
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-ink truncate">{{ $sess['program'] }}</p>
                            <p class="text-[10px] text-faint truncate flex items-center gap-1">
                                <span class="truncate">{{ $sess['location'] }}</span>
                                <x-maps-button :url="$sess['location_maps_url']" />
                            </p>
                            <p class="text-[10px] text-muted font-semibold">
                                {{ \Illuminate\Support\Carbon::parse($sess['start'])->format('H:i') }}–{{ \Illuminate\Support\Carbon::parse($sess['end'])->format('H:i') }}
                            </p>
                        </div>
```

Replace:

```blade
                                    <p class="text-[11px] text-faint truncate">{{ $enr->schedule->location->name }}</p>
                                </div>
                                <p class="text-[11px] font-semibold text-muted tabular-nums shrink-0">
                                    {{ \Illuminate\Support\Carbon::parse($enr->schedule->start_time)->format('H:i') }}
                                </p>
```

with:

```blade
                                    <p class="text-[11px] text-faint truncate flex items-center gap-1">
                                        <span class="truncate">{{ $enr->schedule->location->name }}</span>
                                        <x-maps-button :url="$enr->schedule->location->maps_url" />
                                    </p>
                                </div>
                                <p class="text-[11px] font-semibold text-muted tabular-nums shrink-0">
                                    {{ \Illuminate\Support\Carbon::parse($enr->schedule->start_time)->format('H:i') }}
                                </p>
```

- [ ] **Step 7: Run tests to verify they pass**

Run: `php artisan test tests/Feature/Portal/HomeTest.php`
Expected: PASS, all tests including the 2 new ones.

- [ ] **Step 8: Commit**

```bash
git add resources/views/components/maps-button.blade.php app/Support/ChildSchedulePlanner.php resources/views/components/portal/week-card.blade.php lang/en/messages.php lang/id/messages.php tests/Feature/Portal/HomeTest.php
git commit -m "feat: add maps navigation button to parent home session cards"
```

---

### Task 2: Coach + admin dashboard wiring

**Files:**
- Modify: `resources/views/livewire/coach/dashboard.blade.php:82-83, :197-198, :305-306`
- Modify: `resources/views/livewire/coach/schedules.blade.php:30-31`
- Modify: `resources/views/livewire/coach/check-in.blade.php:57-58, :102-106`
- Modify: `resources/views/components/admin/week-calendar.blade.php:77-78`
- Test: `tests/Feature/Coach/DashboardTest.php`, `tests/Feature/Coach/SchedulesTest.php` (check exact filename with `find tests/Feature/Coach -iname "*schedule*"` first — it may be named differently), `tests/Feature/Coach/CheckInTest.php` (check with `find tests/Feature/Coach -iname "*checkin*" -o -iname "*check-in*"`), `tests/Feature/Admin/DashboardTest.php`

**Interfaces:**
- Consumes: `<x-maps-button :url="..." />` from Task 1. All files in this task access a real `Schedule` model directly (`$sched->location->maps_url` / `$schedule->location->maps_url`), not the planner's array shape — no further plumbing needed.

- [ ] **Step 1: Locate the exact test files**

Run: `find tests/Feature/Coach tests/Feature/Admin -iname "*dashboard*" -o -iname "*schedule*" -o -iname "*checkin*" -o -iname "*check-in*"` and confirm the four test files referenced above exist under those names. Adjust the paths in the steps below if any differ.

- [ ] **Step 2: Write the failing tests**

Add to `tests/Feature/Coach/DashboardTest.php` (append at end of file — check the file's existing `beforeEach` for how a coach user + schedule + location are already set up, and reuse that pattern rather than inventing new setup):

```php
it('shows a maps button for a schedule location that has a maps url', function () {
    $location = \App\Models\Location::factory()->create(['maps_url' => 'https://maps.google.com/?q=coach-test']);
    $schedule = \App\Models\Schedule::factory()->create([
        'coach_id'    => $this->coach->id,
        'location_id' => $location->id,
        'day_of_week' => strtolower(now()->format('l')),
    ]);

    Livewire::actingAs($this->coachUser)
        ->test(\App\Livewire\Coach\Dashboard::class)
        ->assertSee('https://maps.google.com/?q=coach-test', false);
});
```

(If `DashboardTest.php`'s `beforeEach` uses different property names than `$this->coach`/`$this->coachUser`, read the file first and match its actual variable names — do not guess.)

Add to `tests/Feature/Admin/DashboardTest.php` (append at end of file, same rule — read the file's `beforeEach` first and match its actual setup pattern for an admin user + today's schedule):

```php
it('shows a maps button for a schedule location that has a maps url on the week calendar', function () {
    $location = \App\Models\Location::factory()->create(['maps_url' => 'https://maps.google.com/?q=admin-test']);
    $program  = \App\Models\Program::factory()->create();
    \App\Models\Schedule::factory()->create([
        'location_id' => $location->id,
        'program_id'  => $program->id,
        'day_of_week' => strtolower(now()->format('l')),
        'is_active'   => true,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.dashboard'))
        ->assertSee('https://maps.google.com/?q=admin-test', false);
});
```

- [ ] **Step 3: Run tests to verify they fail**

Run: `php artisan test --filter "shows a maps button for a schedule location"`
Expected: FAIL — the maps URL string never appears in either rendered page (component not wired in yet).

- [ ] **Step 4: Wire the component into `coach/dashboard.blade.php`**

Replace (today's schedules section):

```blade
                                    <p class="text-xs font-bold text-ink truncate">{{ $sched->program->name }}</p>
                                    <p class="text-[10px] text-faint truncate">{{ $sched->location->name }}</p>
```

with:

```blade
                                    <p class="text-xs font-bold text-ink truncate">{{ $sched->program->name }}</p>
                                    <p class="text-[10px] text-faint truncate flex items-center gap-1">
                                        <span class="truncate">{{ $sched->location->name }}</span>
                                        <x-maps-button :url="$sched->location->maps_url" />
                                    </p>
```

Replace (upcoming/next-session card section):

```blade
                                    <p class="text-xs text-faint">{{ $schedule->location->name }}</p>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
```

with:

```blade
                                    <p class="text-xs text-faint flex items-center gap-1">
                                        <span class="truncate">{{ $schedule->location->name }}</span>
                                        <x-maps-button :url="$schedule->location->maps_url" />
                                    </p>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
```

Replace (rest-of-week list section):

```blade
                                        <p class="text-[11px] text-faint truncate">{{ $sched->location->name }}</p>
                                    </div>
                                    <p class="text-[11px] font-semibold text-muted tabular-nums shrink-0">
                                        {{ \Carbon\Carbon::parse($sched->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($sched->end_time)->format('H:i') }}
                                    </p>
                                </div>
                            @endforeach
```

with:

```blade
                                        <p class="text-[11px] text-faint truncate flex items-center gap-1">
                                            <span class="truncate">{{ $sched->location->name }}</span>
                                            <x-maps-button :url="$sched->location->maps_url" />
                                        </p>
                                    </div>
                                    <p class="text-[11px] font-semibold text-muted tabular-nums shrink-0">
                                        {{ \Carbon\Carbon::parse($sched->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($sched->end_time)->format('H:i') }}
                                    </p>
                                </div>
                            @endforeach
```

- [ ] **Step 5: Wire the component into `coach/schedules.blade.php`**

Replace:

```blade
                                        <div>
                                            <p class="font-semibold text-ink text-sm">{{ $schedule->program->name }}</p>
                                            <p class="text-xs text-faint">{{ $schedule->location->name }}</p>
                                        </div>
```

with:

```blade
                                        <div>
                                            <p class="font-semibold text-ink text-sm">{{ $schedule->program->name }}</p>
                                            <p class="text-xs text-faint flex items-center gap-1">
                                                <span class="truncate">{{ $schedule->location->name }}</span>
                                                <x-maps-button :url="$schedule->location->maps_url" />
                                            </p>
                                        </div>
```

- [ ] **Step 6: Wire the component into `coach/check-in.blade.php`**

Replace (upcoming-schedules section, line ~57-58):

```blade
                                <p class="text-sm font-semibold text-ink truncate">{{ $sched->program?->name ?? __('messages.admin.schedules.type_private') }}</p>
                                <p class="text-xs text-faint">{{ $sched->location->name }} · {{ \Carbon\Carbon::parse($sched->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($sched->end_time)->format('H:i') }}</p>
```

with:

```blade
                                <p class="text-sm font-semibold text-ink truncate">{{ $sched->program?->name ?? __('messages.admin.schedules.type_private') }}</p>
                                <p class="text-xs text-faint flex items-center gap-1">
                                    <span>{{ $sched->location->name }} · {{ \Carbon\Carbon::parse($sched->start_time)->format('H:i') }}–{{ \Carbon\Carbon::parse($sched->end_time)->format('H:i') }}</span>
                                    <x-maps-button :url="$sched->location->maps_url" />
                                </p>
```

Replace (all-schedules section, line ~102-106):

```blade
                                <p class="text-xs text-faint">
                                    {{ $sched->location->name }}
                                    · {{ \Carbon\Carbon::parse($sched->start_time)->format('g:i A') }}–{{ \Carbon\Carbon::parse($sched->end_time)->format('g:i A') }}
                                    · {{ $enrolled }} enrolled
                                </p>
```

with:

```blade
                                <p class="text-xs text-faint flex items-center gap-1">
                                    <span>
                                        {{ $sched->location->name }}
                                        · {{ \Carbon\Carbon::parse($sched->start_time)->format('g:i A') }}–{{ \Carbon\Carbon::parse($sched->end_time)->format('g:i A') }}
                                        · {{ $enrolled }} enrolled
                                    </span>
                                    <x-maps-button :url="$sched->location->maps_url" />
                                </p>
```

- [ ] **Step 7: Wire the component into `admin/week-calendar.blade.php`**

Replace:

```blade
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-bold text-ink truncate">{{ $sched->program->name }}</p>
                                            <p class="text-[10px] text-faint truncate">{{ $sched->location->name }}</p>
```

with:

```blade
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-bold text-ink truncate">{{ $sched->program->name }}</p>
                                            <p class="text-[10px] text-faint truncate flex items-center gap-1">
                                                <span class="truncate">{{ $sched->location->name }}</span>
                                                <x-maps-button :url="$sched->location->maps_url" />
                                            </p>
```

- [ ] **Step 8: Run tests to verify they pass**

Run: `php artisan test tests/Feature/Coach/DashboardTest.php tests/Feature/Admin/DashboardTest.php`
Expected: PASS, all tests including the 2 new ones.

- [ ] **Step 9: Commit**

```bash
git add resources/views/livewire/coach/dashboard.blade.php resources/views/livewire/coach/schedules.blade.php resources/views/livewire/coach/check-in.blade.php resources/views/components/admin/week-calendar.blade.php tests/Feature/Coach/DashboardTest.php tests/Feature/Admin/DashboardTest.php
git commit -m "feat: add maps navigation button to coach and admin dashboard session cards"
```

---

### Task 3: Full verification

**Files:** none (verification only)

- [ ] **Step 1: Run all affected test files**

Run: `php artisan test tests/Feature/Portal/HomeTest.php tests/Feature/Coach/DashboardTest.php tests/Feature/Admin/DashboardTest.php tests/Feature/Coach/SchedulesTest.php tests/Feature/Coach/CheckInTest.php`

(Use the exact filenames confirmed in Task 2 Step 1 if `SchedulesTest.php`/`CheckInTest.php` differ.)

Expected: PASS, all tests, no regressions.

- [ ] **Step 2: Run the full test suite**

Run: `php -d memory_limit=512M ./vendor/bin/pest`
Expected: PASS, no regressions (this repo's CLI defaults to a 128M memory_limit that OOMs the full suite; the raised limit is the established workaround).

- [ ] **Step 3: Rebuild CSS**

Run: `npm run build`
(No new Tailwind classes were introduced by this plan beyond ones already used elsewhere in the codebase — `flex items-center gap-1`, `truncate`, `shrink-0` — but rebuild anyway per this project's convention of rebuilding after any Blade edit, since XAMPP serves pre-built assets.)

- [ ] **Step 4: Manual smoke check**

Visit `parent/home` as a parent with an active enrollment whose location has a `maps_url`: confirm the small map-pin icon appears next to the location name in both the today's-session card and the rest-of-week list, and clicking it opens the maps URL in a new tab without navigating away from the page.

Visit `coach/dashboard`, `coach/schedules`, `coach/check-in` as a coach: confirm the same icon appears in each of the 5 wired locations and behaves the same way.

Visit `admin/dashboard` as an admin: confirm the icon appears in the week calendar's today's-sessions list.

For a location with `maps_url = null`, confirm no icon renders anywhere (no empty link, no broken icon) — visually identical to before this change.
