<?php

afterEach(function () {
    \Illuminate\Support\Facades\Storage::disk('backups')->deleteDirectory('/');
});

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
