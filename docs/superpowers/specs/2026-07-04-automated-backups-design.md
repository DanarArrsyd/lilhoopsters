# Automated Backups — Design Spec

**Status:** Approved for planning
**Part of:** System-development roadmap — subsystem 3 of 5 (reminders ✅ → audit log ✅ → **backups** → analytics)

## Context

No backup infrastructure exists today. Database is small (~2MB), `storage/app` is small (~2.6MB, mostly payment proofs and future upload growth) — backup size/time is a non-issue at current scale. `config/filesystems.php` already has an `s3` disk stubbed with `AWS_*` env keys present but unconfirmed as production-ready; this subsystem targets **local disk only** — S3/off-site is explicitly deferred, not part of this spec.

The existing `routes/console.php` scheduler infrastructure (built for Reminder Automation: `Schedule::command(...)->dailyAt(...)`) and `NotificationService` (in-app + WhatsApp + email delivery, already used by `ReminderService` and the Audit Log's governance flows) are both reused here — no new scheduling or notification infrastructure needed.

## Scope

- Add `spatie/laravel-backup` (the standard Laravel backup package) rather than hand-rolling mysqldump/zip/retention/streaming logic.
- Back up **database + `storage/app`** (uploaded files), zipped, stored on a **new local disk** (`backups`, rooted outside `storage/app` to avoid the archive recursively including prior archives).
- **Daily** automated backup (01:00) + **daily** cleanup (01:30) enforcing a **flat 14-day retention** (not spatie's default tiered daily/weekly/monthly strategy).
- **Failure-only** notification to super_admins via `NotificationService` (success is silent — no daily spam).
- A **superadmin-only** page (`/superadmin/backups`): list existing archives (filename, size, created date), a **Backup Now** button (manual trigger), a **Download** action per row. No delete/restore UI — retention handles pruning, and restore is an operational/CLI action, not a web UI concern for this spec.

Out of scope: S3/off-site replication, restore-from-UI, backup encryption/password-protection, per-admin (non-super) visibility.

## Design

### 1. Package + configuration

`composer require spatie/laravel-backup`, then publish and edit `config/backup.php`:

```php
'backup' => [
    'name' => env('APP_NAME', 'basketballv2'),
    'source' => [
        'files' => [
            'include' => [storage_path('app')],
            'exclude' => [],
            'follow_links' => false,
        ],
        'databases' => ['mysql'],
    ],
    'destination' => [
        'filename_prefix' => '',
        'disks' => ['backups'],
    ],
],
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

`DefaultStrategy`'s first tier (`keep_all_backups_for_days`) keeps every backup younger than 14 days and deletes everything past that once the later (zeroed) tiers don't re-select it — this produces the flat "delete anything older than 14 days" behavior requested, without needing a custom cleanup strategy class.

New disk in `config/filesystems.php`:

```php
'backups' => [
    'driver' => 'local',
    'root' => storage_path('app/backups'),
    'throw' => false,
    'report' => false,
],
```

`storage_path('app/backups')` is a subdirectory of `storage/app` — this **would** be recursively included by `source.files.include => [storage_path('app')]` above. To avoid that, the source exclude list must explicitly exclude the backup destination:

```php
'source' => [
    'files' => [
        'include' => [storage_path('app')],
        'exclude' => [storage_path('app/backups')],
        'follow_links' => false,
    ],
    'databases' => ['mysql'],
],
```

### 2. Scheduling

In `routes/console.php`, alongside the existing reminder/expiry jobs:

```php
// Nightly full backup (DB + storage/app), then prune anything past the
// 14-day retention window. Requires the server cron already running
// `php artisan schedule:run` every minute (same requirement as the
// reminder jobs above).
Schedule::command('backup:run')->dailyAt('01:00');
Schedule::command('backup:clean')->dailyAt('01:30');
```

### 3. Failure notification

`spatie/laravel-backup` fires `Spatie\Backup\Events\BackupHasFailed` and `Spatie\Backup\Events\CleanupHasFailed` (both carry an `exception` property). Register listeners in `App\Providers\AppServiceProvider::boot()` (this Laravel 11 app has no separate `EventServiceProvider`) using `Event::listen(...)`, calling a new `NotificationService::toSuperAdmins()` method (mirrors the existing `toAdmins()` exactly, just scoped to the `super_admin` role):

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

Listener wiring (in `AppServiceProvider::boot()`):

```php
Event::listen(\Spatie\Backup\Events\BackupHasFailed::class, function ($event) {
    NotificationService::toSuperAdmins(
        'backup_failed',
        'Backup Failed',
        'The scheduled backup failed: ' . $event->exception->getMessage(),
    );
});

Event::listen(\Spatie\Backup\Events\CleanupHasFailed::class, function ($event) {
    NotificationService::toSuperAdmins(
        'backup_cleanup_failed',
        'Backup Cleanup Failed',
        'Pruning old backups failed: ' . $event->exception->getMessage(),
    );
});
```

Success events (`BackupWasSuccessful`, `CleanupWasSuccessful`) are intentionally not listened to — no notification on the happy path, per the "failure-only" decision.

### 4. Superadmin page

New route inside the existing `superadmin` route group (`role:super_admin` middleware, already established): `Route::get('/backups', ...)->name('backups')`.

New Livewire component `Superadmin\Backups`:

```php
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

public function backupNow(): void
{
    Artisan::call('backup:run');
    session()->flash('success', 'Backup started. Refresh in a moment to see it in the list.');
}
```

Download: a dedicated route (not a Livewire method, since file downloads need a real HTTP response) — `Route::get('/backups/{filename}/download', ...)`, guarded by the same `role:super_admin` middleware, validating `$filename` against `basename($filename)` (no path traversal) and confirming the file exists on the `backups` disk before calling `Storage::disk('backups')->download($path)`.

View: list table (Filename | Size | Created At) + "Backup Now" button + per-row "Download" link, following the same `x-card`/table pattern as other admin/superadmin list pages (e.g. Audit Log). Empty state if no backups exist yet.

### 5. Testing

- Config sanity: a test asserting `config('backup.backup.source.databases')` contains `mysql`, `config('backup.backup.destination.disks')` is `['backups']`, `config('filesystems.disks.backups.root')` equals `storage_path('app/backups')`, and `config('backup.backup.source.files.exclude')` contains that same path (regression guard against the recursive-inclusion bug this spec calls out).
- `tests/Feature/BackupNotificationTest.php`: dispatch `BackupHasFailed`/`CleanupHasFailed` with a fake exception, assert a `Notification` row is created for each super_admin user with the right `type`.
- `tests/Feature/Superadmin/BackupsPageTest.php`: page renders for super_admin, 403 for admin/coach/parent; lists files present on the `backups` disk (fake a couple of zip files via `Storage::fake('backups')` before rendering); "Backup Now" triggers `Artisan::call('backup:run')` (assert via `Artisan::spy()` or by asserting a real new file appears on the faked disk, whichever the implementer finds more reliable in this environment); download route streams a 200 with the right `Content-Disposition` for an existing file, and 404 for a made-up filename (path-traversal / nonexistent-file guard).

### 6. Files touched

- Edit: `composer.json`, `composer.lock` (new dependency)
- New: `config/backup.php` (published by the package, then edited)
- Edit: `config/filesystems.php` (new `backups` disk)
- Edit: `routes/console.php` (2 new scheduled commands)
- Edit: `app/Services/NotificationService.php` (new `toSuperAdmins()` method)
- Edit: `app/Providers/AppServiceProvider.php` (2 new event listeners in `boot()`)
- New: `app/Livewire/Superadmin/Backups.php`, `resources/views/livewire/superadmin/backups.blade.php`, `resources/views/superadmin/backups.blade.php`
- New: `app/Http/Controllers/Superadmin/BackupDownloadController.php`
- Edit: `routes/web.php` (2 new routes: page + download, inside existing `superadmin` group)
- Edit: `resources/views/components/superadmin-nav.blade.php` (nav link)
- New: `tests/Feature/BackupConfigTest.php`, `tests/Feature/BackupNotificationTest.php`, `tests/Feature/Superadmin/BackupsPageTest.php`

No new lang keys — matches the hardcoded-English convention already established by `ReminderService`/`NotificationService`/the Audit Log page.
