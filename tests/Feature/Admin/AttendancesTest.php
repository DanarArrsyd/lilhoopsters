<?php

use App\Livewire\Admin\Attendances;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Child;
use App\Models\Coach;
use App\Models\Enrollment;
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

    $this->admin = User::factory()->withRole('admin')->approved()->create();

    $this->coachUser = User::factory()->withRole('coach')->approved()->create();
    $this->coach     = Coach::factory()->create(['user_id' => $this->coachUser->id]);
    $this->schedule  = Schedule::factory()->create(['coach_id' => $this->coach->id]);
    $this->child     = Child::factory()->create();
    $this->enrollment = Enrollment::factory()->approved()->create([
        'child_id'    => $this->child->id,
        'schedule_id' => $this->schedule->id,
    ]);

    $this->attendance = Attendance::factory()->present()->create([
        'child_id'      => $this->child->id,
        'enrollment_id' => $this->enrollment->id,
        'schedule_id'   => $this->schedule->id,
        'coach_id'      => $this->coach->id,
        'attended_at'   => now(),
    ]);
});

it('renders the attendances page', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.attendances'))
        ->assertOk();
});

it('non-admin cannot access attendances page', function () {
    $parent = User::factory()->withRole('parent')->approved()->create();
    $this->actingAs($parent)->get(route('admin.attendances'))->assertForbidden();
});

it('shows attendance records', function () {
    Livewire::actingAs($this->admin)
        ->test(Attendances::class)
        ->assertSee($this->child->name);
});

it('can filter by child name', function () {
    $other = Child::factory()->create(['name' => 'ZZZ Unique Name']);
    Attendance::factory()->present()->create(['child_id' => $other->id, 'attended_at' => now()]);

    Livewire::actingAs($this->admin)
        ->test(Attendances::class)
        ->set('search', 'ZZZ Unique')
        ->assertSee('ZZZ Unique Name')
        ->assertDontSee($this->child->name);
});

it('can filter by status', function () {
    $absentChild = Child::factory()->create();
    Attendance::factory()->absent()->create(['child_id' => $absentChild->id, 'attended_at' => now()]);

    Livewire::actingAs($this->admin)
        ->test(Attendances::class)
        ->set('filterStatus', 'no_show')
        ->assertSee($absentChild->name)
        ->assertDontSee($this->child->name);
});

it('can override attendance status', function () {
    Livewire::actingAs($this->admin)
        ->test(Attendances::class)
        ->call('openOverride', $this->attendance->id)
        ->assertSet('showOverride', true)
        ->set('overrideStatus', 'sick')
        ->set('overrideNotes', 'Demam')
        ->call('saveOverride');

    expect($this->attendance->fresh()->status)->toBe('sick');
    expect($this->attendance->fresh()->notes)->toBe('Demam');
});

it('closes override modal on cancel', function () {
    Livewire::actingAs($this->admin)
        ->test(Attendances::class)
        ->call('openOverride', $this->attendance->id)
        ->assertSet('showOverride', true)
        ->call('closeOverride')
        ->assertSet('showOverride', false);
});

it('records an audit log when an attendance record is overridden', function () {
    $attendance = Attendance::factory()->create(['status' => 'present']);

    Livewire::actingAs($this->admin)
        ->test(Attendances::class)
        ->call('openOverride', $attendance->id)
        ->set('overrideStatus', 'no_show')
        ->call('saveOverride');

    $log = AuditLog::where('action', 'attendance.overridden')->first();
    expect($log)->not->toBeNull();
    expect($log->subject_id)->toBe($attendance->id);
    expect($log->meta['old_status'])->toBe('present');
    expect($log->meta['new_status'])->toBe('no_show');
});
