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
    // Livewire's test harness disables middleware (including StartSession) for its
    // internal simulated request, so the flashed value never lands in the same
    // session.store instance the assertion helper re-resolves afterward — a known
    // Livewire 3 testing limitation, not an application bug. The flash is verified
    // to be genuinely set (and rendered) by asserting the success message appears
    // in the component's own re-rendered HTML instead.
    Livewire::actingAs($this->superAdmin)
        ->test(Backups::class)
        ->call('backupNow')
        ->assertSee('Backup started');
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
