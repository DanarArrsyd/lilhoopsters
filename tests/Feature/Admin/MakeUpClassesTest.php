<?php

use App\Livewire\Admin\MakeUpClasses;
use App\Models\Child;
use App\Models\Enrollment;
use App\Models\MakeUpClass;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::insert([
        ['name' => 'super_admin', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'admin',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'coach',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'parent',      'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->admin  = User::factory()->withRole('admin')->approved()->create();
    $this->child  = Child::factory()->create();
    $this->makeUp = MakeUpClass::factory()->create([
        'child_id' => $this->child->id,
        'status'   => 'pending',
    ]);
});

it('renders make-up classes page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.makeup-classes'))
        ->assertOk();
});

it('shows pending make-up requests by default', function () {
    Livewire::actingAs($this->admin)
        ->test(MakeUpClasses::class)
        ->assertSee($this->child->name);
});

it('can approve a make-up class request', function () {
    Livewire::actingAs($this->admin)
        ->test(MakeUpClasses::class)
        ->call('openReview', $this->makeUp->id, 'approve')
        ->assertSet('showReview', true)
        ->set('adminNotes', 'Jadwal tersedia')
        ->call('saveReview');

    expect($this->makeUp->fresh()->status)->toBe('approved');
    expect($this->makeUp->fresh()->approved_by)->toBe($this->admin->id);
    expect($this->makeUp->fresh()->admin_notes)->toBe('Jadwal tersedia');
});

it('can reject a make-up class request', function () {
    Livewire::actingAs($this->admin)
        ->test(MakeUpClasses::class)
        ->call('openReview', $this->makeUp->id, 'reject')
        ->call('saveReview');

    expect($this->makeUp->fresh()->status)->toBe('rejected');
});

it('can filter by status', function () {
    $approved = MakeUpClass::factory()->approved()->create(['child_id' => $this->child->id]);

    Livewire::actingAs($this->admin)
        ->test(MakeUpClasses::class)
        ->set('filterStatus', 'approved')
        ->assertSee($this->child->name);
});

it('can search by child name', function () {
    $other    = Child::factory()->create(['name' => 'Unique Child XYZ']);
    MakeUpClass::factory()->create(['child_id' => $other->id, 'status' => 'pending']);

    Livewire::actingAs($this->admin)
        ->test(MakeUpClasses::class)
        ->set('search', 'Unique Child')
        ->assertSee('Unique Child XYZ')
        ->assertDontSee($this->child->name);
});
