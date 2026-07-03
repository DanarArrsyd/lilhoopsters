# Automated Backups Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Nightly automated backup (database + `storage/app`) to local disk with 14-day retention, failure-only notification to superadmins, and a simple superadmin page to view/trigger/download backups.

**Architecture:** `spatie/laravel-backup` handles the dump/zip/retention mechanics via its Artisan commands (`backup:run`, `backup:clean`), scheduled through the existing `routes/console.php` cron infrastructure. A new local `backups` disk (outside `storage/app`, to avoid recursive self-inclusion) holds the archives. Two event listeners bridge the package's failure events into the existing `NotificationService`. A thin Livewire page + download controller expose the archives to superadmins only.

**Tech Stack:** Laravel 11, `spatie/laravel-backup` (published `config/backup.php`), Livewire 3, Pest.

## Global Constraints

- Local disk only — no S3/off-site replication in this plan.
- Backup source = database (`mysql` connection) + `storage_path('app')`, **excluding** the backup destination itself (`storage_path('app/backups')`) to prevent an archive recursively including prior archives.
- Retention: flat 14 days (`keep_all_backups_for_days = 14`, all other `DefaultStrategy` tiers zeroed) — not spatie's default tiered daily/weekly/monthly scheme.
- Notification: **failure only** (`BackupHasFailed`, `CleanupHasFailed`). Do not listen to the success events — no notification on the happy path.
- Viewers: super_admin only, via the existing `role:super_admin` middleware already on the `superadmin` route group.
- No delete/restore UI, no new lang keys (hardcoded English, matching `ReminderService`/`NotificationService`/Audit Log convention).
- This dev machine's `mysqldump` is **not on PATH** — it's bundled at `/Applications/XAMPP/xamppfiles/bin/mysqldump`. The dump binary path must be configurable via a `DB_DUMP_PATH` env var (empty default, so production environments where `mysqldump` is already on PATH need no change) rather than hardcoded, so this plan's own tests can actually run `backup:run` for real in this environment.

---

### Task 1: Package install, backup config, dump-binary-path fix, scheduling

**Files:**
- Modify: `composer.json`, `composer.lock` (new dependency)
- Create: `config/backup.php` (published by the package, then edited)
- Modify: `config/filesystems.php` (new `backups` disk)
- Modify: `config/database.php:47-65` (add `dump` key to the `mysql` connection)
- Modify: `.env` (add `DB_DUMP_PATH=/Applications/XAMPP/xamppfiles/bin` for this local machine)
- Modify: `.env.example` (add commented `DB_DUMP_PATH=` placeholder)
- Modify: `routes/console.php` (2 new scheduled commands)
- Test: `tests/Feature/BackupConfigTest.php`

**Interfaces:**
- Produces: the `backups` filesystem disk (`config('filesystems.disks.backups')`), the published `config/backup.php` with this plan's exact values, and the two scheduled commands `backup:run`/`backup:clean`. Later tasks (Task 2, Task 3) read/write files via `Storage::disk('backups')` and rely on `backup:run` actually working end-to-end in this dev environment.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/BackupConfigTest.php`:

```php
<?php

it('configures the backup source, destination, and retention correctly', function () {
    expect(config('backup.backup.source.databases'))->toBe(['mysql']);
    expect(config('backup.backup.source.files.include'))->toBe([storage_path('app')]);
    expect(config('backup.backup.source.files.exclude'))->toContain(storage_path('app/backups'));
    expect(config('backup.backup.destination.disks'))->toBe(['backups']);

    expect(config('backup.cleanup.default_strategy.keep_all_backups_for_days'))->toBe(14);
    expect(config('backup.cleanup.default_strategy.keep_daily_backups_for_days'))->toBe(0);
    expect(config('backup.cleanup.default_strategy.keep_weekly_backups_for_weeks'))->toBe(0);
    expect(config('backup.cleanup.default_strategy.keep_monthly_backups_for_months'))->toBe(0);
});

it('configures the backups disk outside storage/app', function () {
    expect(config('filesystems.disks.backups.driver'))->toBe('local');
    expect(config('filesystems.disks.backups.root'))->toBe(storage_path('app/backups'));
});

it('can actually run a real backup in this environment', function () {
    $exitCode = \Illuminate\Support\Facades\Artisan::call('backup:run');

    expect($exitCode)->toBe(0);

    $files = \Illuminate\Support\Facades\Storage::disk('backups')->allFiles();
    $zips  = array_filter($files, fn($f) => str_ends_with($f, '.zip'));

    expect($zips)->not->toBeEmpty();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/BackupConfigTest.php`

Expected: FAIL — `config('backup.*')` keys don't exist yet (package not installed), and `backup:run` command doesn't exist.

- [ ] **Step 3: Install the package**

Run: `composer require spatie/laravel-backup`

Then publish its config:

Run: `php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"`

This creates `config/backup.php` with the package's defaults.

- [ ] **Step 4: Edit `config/backup.php`**

Open `config/backup.php`. Find the `'backup' => ['source' => [...]]` section and replace its `files` and `databases` sub-keys with:

```php
        'source' => [
            'files' => [
                'include' => [
                    storage_path('app'),
                ],
                'exclude' => [
                    storage_path('app/backups'),
                ],
                'follow_links' => false,
                'ignore_unreadable_directories' => false,
                'relative_path' => null,
            ],
            'databases' => [
                'mysql',
            ],
        ],
```

Find the `'destination' => [...]` section and set `disks`:

```php
        'destination' => [
            'filename_prefix' => '',
            'disks' => [
                'backups',
            ],
        ],
```

Find the `'cleanup' => ['default_strategy' => [...]]` section (near the bottom of the file) and set:

```php
    'cleanup' => [
        'strategy' => \Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days' => 14,
            'keep_daily_backups_for_days' => 0,
            'keep_weekly_backups_for_weeks' => 0,
            'keep_monthly_backups_for_months' => 0,
            'keep_yearly_backups_for_years' => 0,
            'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
        ],
    ],
```

(Leave every other key in the published `config/backup.php` at its package default — notifications, monitor_backups, etc. are not touched by this plan.)

- [ ] **Step 5: Add the `backups` disk**

In `config/filesystems.php`, inside the `'disks' => [...]` array, add a new entry alongside `local`/`public`/`s3`:

```php
        'backups' => [
            'driver' => 'local',
            'root' => storage_path('app/backups'),
            'throw' => false,
            'report' => false,
        ],
```

- [ ] **Step 6: Make the mysqldump binary path configurable**

In `config/database.php`, inside the `'mysql' => [...]` connection array (currently ending at line 64 with the `'options'` key), add a `dump` key right after `'options'`:

```php
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                (PHP_VERSION_ID >= 80500 ? Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA) => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
            'dump' => [
                'dump_binary_path' => env('DB_DUMP_PATH', ''),
            ],
```

`spatie/laravel-backup` (via its `spatie/db-dumper` dependency) reads `database.connections.mysql.dump.dump_binary_path` as the **directory** containing the `mysqldump` binary. An empty string means "look on PATH" (the right default for most production servers).

- [ ] **Step 7: Set the local `.env` value**

Add to `.env` (not `.env.example`, since this exact path is specific to this XAMPP install):

```
DB_DUMP_PATH=/Applications/XAMPP/xamppfiles/bin
```

Add to `.env.example` a documented placeholder so future setups know the option exists:

```
# Directory containing the mysqldump binary, only needed if it's not on
# PATH (e.g. XAMPP: /Applications/XAMPP/xamppfiles/bin). Leave empty
# otherwise.
DB_DUMP_PATH=
```

- [ ] **Step 8: Register the scheduled commands**

In `routes/console.php`, add after the existing reminder/expiry schedule entries:

```php
// Nightly full backup (DB + storage/app), then prune anything past the
// 14-day retention window. Requires the server cron already running
// `php artisan schedule:run` every minute (same requirement as the
// reminder jobs above).
Schedule::command('backup:run')->dailyAt('01:00');
Schedule::command('backup:clean')->dailyAt('01:30');
```

- [ ] **Step 9: Run the tests**

Run: `php artisan config:clear && php artisan test tests/Feature/BackupConfigTest.php`

Expected: all 3 tests PASS. The third test actually runs `mysqldump` against the test database and produces a real zip in `storage/app/backups` — if it fails with a "mysqldump: command not found" style error, double check Step 7's `.env` value and that you ran `php artisan config:clear` (config is cached and won't pick up new `.env`/config file values otherwise).

- [ ] **Step 10: Clean up the test-created backup file**

The third test in Step 1 creates a real zip file in `storage/app/backups` as a side effect (this is intentional — it's the only way to prove `backup:run` genuinely works in this environment, not just that the config is theoretically correct). Add a cleanup to that test so repeated test runs don't accumulate backup files:

```php
afterEach(function () {
    \Illuminate\Support\Facades\Storage::disk('backups')->deleteDirectory('/');
});
```

Place this `afterEach` at the top of `tests/Feature/BackupConfigTest.php`, before the three `it(...)` blocks.

- [ ] **Step 11: Re-run to confirm cleanup works**

Run: `php artisan test tests/Feature/BackupConfigTest.php` twice in a row.

Expected: PASS both times, and `ls storage/app/backups` is empty after the test run completes (confirming the `afterEach` cleaned up).

- [ ] **Step 12: Commit**

```bash
git add composer.json composer.lock config/backup.php config/filesystems.php config/database.php .env.example routes/console.php tests/Feature/BackupConfigTest.php
git commit -m "feat: install spatie/laravel-backup, configure source/destination/retention, schedule nightly runs"
```

Note: `.env` is git-ignored — its `DB_DUMP_PATH` change from Step 7 is a local machine setting only and will not be committed (this is correct; every environment sets its own `.env`).

---

### Task 2: Failure notifications

**Files:**
- Modify: `app/Services/NotificationService.php` (new `toSuperAdmins()` method)
- Modify: `app/Providers/AppServiceProvider.php:1-9,20-25` (2 new event listeners in `boot()`)
- Test: `tests/Feature/BackupNotificationTest.php`

**Interfaces:**
- Consumes: `Spatie\Backup\Events\BackupHasFailed`, `Spatie\Backup\Events\CleanupHasFailed` (from Task 1's installed package — both events expose a public `$exception` property of type `\Exception`).
- Produces: `NotificationService::toSuperAdmins(string $type, string $title, string $body, array $data = []): void` — mirrors the existing `NotificationService::toAdmins()` exactly, scoped to the `super_admin` role instead of `admin`.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/BackupNotificationTest.php`:

```php
<?php

use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Backup\Events\BackupHasFailed;
use Spatie\Backup\Events\CleanupHasFailed;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::insert([
        ['name' => 'super_admin', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'admin',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'coach',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'parent',      'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->superAdmin = User::factory()->withRole('super_admin')->approved()->create();
});

it('notifies super admins when a backup fails', function () {
    event(new BackupHasFailed(new \Exception('disk full')));

    $note = Notification::where('user_id', $this->superAdmin->id)
        ->where('type', 'backup_failed')
        ->first();

    expect($note)->not->toBeNull();
    expect($note->body)->toContain('disk full');
});

it('notifies super admins when cleanup fails', function () {
    event(new CleanupHasFailed(new \Exception('permission denied')));

    $note = Notification::where('user_id', $this->superAdmin->id)
        ->where('type', 'backup_cleanup_failed')
        ->first();

    expect($note)->not->toBeNull();
    expect($note->body)->toContain('permission denied');
});

it('does not notify admin or coach roles, only super_admin', function () {
    $admin = User::factory()->withRole('admin')->approved()->create();

    event(new BackupHasFailed(new \Exception('disk full')));

    expect(Notification::where('user_id', $admin->id)->exists())->toBeFalse();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/BackupNotificationTest.php`

Expected: FAIL — no listener exists yet, so no `Notification` rows are created.

- [ ] **Step 3: Add `NotificationService::toSuperAdmins()`**

In `app/Services/NotificationService.php`, add this method right after the existing `toAdmins()` method:

```php
    public static function toSuperAdmins(string $type, string $title, string $body, array $data = []): void
    {
        $roleId = Role::where('name', 'super_admin')->value('id');
        if (!$roleId) return;

        User::where('role_id', $roleId)
            ->where('is_active', true)
            ->each(fn($u) => static::send($u->id, $type, $title, $body, $data));
    }
```

(`Role` and `User` are already imported at the top of this file for `toAdmins()` — no new imports needed.)

- [ ] **Step 4: Register the event listeners**

In `app/Providers/AppServiceProvider.php`, add two imports at the top:

```php
use App\Services\NotificationService;
use Illuminate\Support\Facades\Event;
use Spatie\Backup\Events\BackupHasFailed;
use Spatie\Backup\Events\CleanupHasFailed;
```

Then inside the existing `boot()` method, after the two `View::composer(...)` lines and before the closing `View::composer('auth.pending', ...)` block (or after it — order doesn't matter, just add both `Event::listen(...)` calls somewhere inside `boot()`):

```php
        Event::listen(BackupHasFailed::class, function (BackupHasFailed $event) {
            NotificationService::toSuperAdmins(
                'backup_failed',
                'Backup Failed',
                'The scheduled backup failed: ' . $event->exception->getMessage(),
            );
        });

        Event::listen(CleanupHasFailed::class, function (CleanupHasFailed $event) {
            NotificationService::toSuperAdmins(
                'backup_cleanup_failed',
                'Backup Cleanup Failed',
                'Pruning old backups failed: ' . $event->exception->getMessage(),
            );
        });
```

- [ ] **Step 5: Run the tests**

Run: `php artisan test tests/Feature/BackupNotificationTest.php`

Expected: all 3 tests PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Services/NotificationService.php app/Providers/AppServiceProvider.php tests/Feature/BackupNotificationTest.php
git commit -m "feat: notify super admins on backup/cleanup failure"
```

---

### Task 3: Superadmin backups page + download

**Files:**
- Create: `app/Livewire/Superadmin/Backups.php`
- Create: `resources/views/livewire/superadmin/backups.blade.php`
- Create: `resources/views/superadmin/backups.blade.php`
- Create: `app/Http/Controllers/Superadmin/BackupDownloadController.php`
- Modify: `routes/web.php` (2 new routes inside the existing `superadmin` group)
- Modify: `resources/views/components/superadmin-nav.blade.php`
- Test: `tests/Feature/Superadmin/BackupsPageTest.php`

**Interfaces:**
- Consumes: the `backups` disk from Task 1 (`Storage::disk('backups')`), `Artisan::call('backup:run')`.

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Superadmin/BackupsPageTest.php`:

```php
<?php

use App\Livewire\Superadmin\Backups;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::insert([
        ['name' => 'super_admin', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'admin',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'coach',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'parent',      'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->superAdmin = User::factory()->withRole('super_admin')->approved()->create();

    Storage::fake('backups');
});

it('renders the backups page for super_admin', function () {
    $this->actingAs($this->superAdmin)
        ->get(route('superadmin.backups'))
        ->assertOk();
});

it('coach and parent cannot access the backups page', function () {
    $coach  = User::factory()->withRole('coach')->approved()->create();
    $parent = User::factory()->withRole('parent')->approved()->create();

    $this->actingAs($coach)->get(route('superadmin.backups'))->assertForbidden();
    $this->actingAs($parent)->get(route('superadmin.backups'))->assertForbidden();
});

it('lists existing backup archives', function () {
    Storage::disk('backups')->put('basketballv2/2026-07-04-01-00-00.zip', 'fake zip contents');

    Livewire::actingAs($this->superAdmin)
        ->test(Backups::class)
        ->assertSee('2026-07-04-01-00-00.zip');
});

it('shows an empty state when there are no backups yet', function () {
    Livewire::actingAs($this->superAdmin)
        ->test(Backups::class)
        ->assertSee('No backups');
});

it('triggers a manual backup run and flashes a success message', function () {
    Livewire::actingAs($this->superAdmin)
        ->test(Backups::class)
        ->call('backupNow')
        ->assertSessionHas('success');
});

it('downloads an existing backup file', function () {
    Storage::disk('backups')->put('basketballv2/2026-07-04-01-00-00.zip', 'fake zip contents');

    $response = $this->actingAs($this->superAdmin)
        ->get(route('superadmin.backups.download', ['filename' => 'basketballv2/2026-07-04-01-00-00.zip']));

    $response->assertOk();
    $response->assertHeader('content-disposition');
});

it('returns 404 for a non-existent backup file', function () {
    $this->actingAs($this->superAdmin)
        ->get(route('superadmin.backups.download', ['filename' => 'does-not-exist.zip']))
        ->assertNotFound();
});

it('rejects a path-traversal attempt in the download filename', function () {
    $this->actingAs($this->superAdmin)
        ->get(route('superadmin.backups.download', ['filename' => '../../../../etc/passwd']))
        ->assertNotFound();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/Superadmin/BackupsPageTest.php`

Expected: FAIL — route `superadmin.backups` and `App\Livewire\Superadmin\Backups` don't exist yet.

- [ ] **Step 3: Create the Livewire component**

Create `app/Livewire/Superadmin/Backups.php`:

```php
<?php

namespace App\Livewire\Superadmin;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class Backups extends Component
{
    public function backupNow(): void
    {
        Artisan::call('backup:run');

        session()->flash('success', 'Backup started. Refresh in a moment to see it in the list.');
    }

    public function render()
    {
        $files = collect(Storage::disk('backups')->allFiles())
            ->filter(fn($f) => str_ends_with($f, '.zip'))
            ->map(fn($f) => [
                'path'       => $f,
                'name'       => basename($f),
                'size'       => Storage::disk('backups')->size($f),
                'created_at' => Carbon::createFromTimestamp(Storage::disk('backups')->lastModified($f)),
            ])
            ->sortByDesc('created_at')
            ->values();

        return view('livewire.superadmin.backups', ['files' => $files]);
    }
}
```

- [ ] **Step 4: Create the view**

Create `resources/views/livewire/superadmin/backups.blade.php`:

```blade
<div class="max-w-5xl mx-auto">

    <x-admin.page-header title="Backups" subtitle="Database and file backups, retained for 14 days." />

    @if (session('success'))
        <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
    @endif

    <x-card class="mb-4">
        <div class="flex items-center justify-between">
            <p class="text-sm text-muted">Backups run automatically every night at 01:00.</p>
            <button type="button" wire:click="backupNow" wire:loading.attr="disabled"
                    class="bg-navy text-off text-xs font-bold uppercase tracking-wide px-4 py-2 rounded-xl hover:bg-navy/90 transition-colors disabled:opacity-60">
                <span wire:loading.remove wire:target="backupNow">Backup Now</span>
                <span wire:loading wire:target="backupNow">Running...</span>
            </button>
        </div>
    </x-card>

    <x-card padding="p-0">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-line">
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Filename</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Size</th>
                        <th class="text-left py-3 px-4 text-xs font-bold text-muted uppercase tracking-wide">Created At</th>
                        <th class="py-3 px-4"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse ($files as $file)
                        <tr class="hover:bg-off transition-colors">
                            <td class="py-3 px-4 text-ink">{{ $file['name'] }}</td>
                            <td class="py-3 px-4 text-muted">{{ number_format($file['size'] / 1024 / 1024, 2) }} MB</td>
                            <td class="py-3 px-4 text-ink">{{ $file['created_at']->format('d M Y H:i') }}</td>
                            <td class="py-3 px-4 text-right">
                                <a href="{{ route('superadmin.backups.download', ['filename' => $file['path']]) }}"
                                   class="text-navy text-xs font-semibold hover:underline">Download</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-2">
                            <x-empty-state title="No backups yet" description="The first automatic backup runs tonight at 01:00, or click Backup Now above." />
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-card>

</div>
```

- [ ] **Step 5: Create the route wrapper view**

Create `resources/views/superadmin/backups.blade.php`:

```blade
<x-superadmin title="Backups">
    <livewire:superadmin.backups />
</x-superadmin>
```

(Confirm `x-superadmin` is the correct component name for the superadmin layout shell by checking how `resources/views/superadmin/dashboard.blade.php` wraps its content — copy that exact wrapper tag name.)

- [ ] **Step 6: Create the download controller**

Create `app/Http/Controllers/Superadmin/BackupDownloadController.php`:

```php
<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BackupDownloadController extends Controller
{
    public function __invoke(Request $request, string $filename)
    {
        // Guard against path traversal: only allow the exact relative path
        // as stored, and refuse to serve outside the backups disk.
        if (str_contains($filename, '..') || !Storage::disk('backups')->exists($filename)) {
            abort(404);
        }

        return Storage::disk('backups')->download($filename);
    }
}
```

- [ ] **Step 7: Register the routes**

In `routes/web.php`, find the `superadmin` route group (search for `Route::get('/system-settings',` to locate it) and add, inside that group:

```php
        Route::get('/backups', fn() => view('superadmin.backups'))->name('backups');
        Route::get('/backups/{filename}/download', \App\Http\Controllers\Superadmin\BackupDownloadController::class)
            ->where('filename', '.*')
            ->name('backups.download');
```

The `->where('filename', '.*')` is required because the stored backup filenames include a subdirectory (e.g. `basketballv2/2026-07-04-01-00-00.zip`) — Laravel's default route parameter pattern doesn't match `/`.

- [ ] **Step 8: Add the nav link**

In `resources/views/components/superadmin-nav.blade.php`, add a new `<x-sidebar-link>` inside the existing `management` section (the same `<x-sidebar-section :label="__('messages.superadmin.section.management')">` block that already contains the Admin Accounts, System Settings, and Audit Log links), following the exact same markup pattern as its neighbors:

```blade
    <x-sidebar-link href="{{ route('superadmin.backups') }}" :active="request()->routeIs('superadmin.backups')">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0H4m8-9v9"/>
        </svg>
        Backups
    </x-sidebar-link>
```

Place it after the existing Audit Log link in that file.

- [ ] **Step 9: Run the tests**

Run: `php artisan test tests/Feature/Superadmin/BackupsPageTest.php`

Expected: all 8 tests PASS. Note the `'triggers a manual backup run and flashes a success message'` test will actually invoke `backup:run` for real (same as Task 1's config test) — this is expected and will take a few seconds.

- [ ] **Step 10: Run the full test suite**

Run: `php artisan test`

Expected: all tests PASS, no regressions (this task touches a shared nav component).

- [ ] **Step 11: Commit**

```bash
git add app/Livewire/Superadmin/Backups.php resources/views/livewire/superadmin/backups.blade.php resources/views/superadmin/backups.blade.php app/Http/Controllers/Superadmin/BackupDownloadController.php routes/web.php resources/views/components/superadmin-nav.blade.php tests/Feature/Superadmin/BackupsPageTest.php
git commit -m "feat: add superadmin backups page with manual trigger and download"
```

---

## Post-implementation note

If deploying to a server where `mysqldump` is already on `PATH` (the common case for most hosting), leave `DB_DUMP_PATH` unset/empty in that server's `.env` — the code defaults to relying on `PATH` in that case. Only set `DB_DUMP_PATH` on machines (like this local XAMPP install) where `mysqldump` isn't globally discoverable.
