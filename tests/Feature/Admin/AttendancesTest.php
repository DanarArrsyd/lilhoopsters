<?php

use App\Livewire\Admin\Attendances;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Child;
use App\Models\Coach;
use App\Models\CoachSession;
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

it('filters students by date range', function () {
    $inRange  = Child::factory()->create(['name' => 'In Range Child']);
    $outRange = Child::factory()->create(['name' => 'Out Range Child']);

    Attendance::factory()->present()->create([
        'child_id'    => $inRange->id,
        'attended_at' => '2026-06-15',
    ]);
    Attendance::factory()->present()->create([
        'child_id'    => $outRange->id,
        'attended_at' => '2026-05-01',
    ]);

    Livewire::actingAs($this->admin)
        ->test(Attendances::class)
        ->set('filterDateFrom', '2026-06-01')
        ->set('filterDateTo', '2026-06-30')
        ->assertSee('In Range Child')
        ->assertDontSee('Out Range Child');
});

it('filters students with only a from-date bound', function () {
    $recent = Child::factory()->create(['name' => 'Recent Child']);
    $old    = Child::factory()->create(['name' => 'Old Child']);

    Attendance::factory()->present()->create(['child_id' => $recent->id, 'attended_at' => '2026-06-15']);
    Attendance::factory()->present()->create(['child_id' => $old->id, 'attended_at' => '2026-01-01']);

    Livewire::actingAs($this->admin)
        ->test(Attendances::class)
        ->set('filterDateFrom', '2026-06-01')
        ->assertSee('Recent Child')
        ->assertDontSee('Old Child');
});

it('filters coach sessions by date range', function () {
    $inRangeCoachUser = User::factory()->withRole('coach')->approved()->create(['name' => 'In Range Coach']);
    $inRangeCoach = Coach::factory()->create(['user_id' => $inRangeCoachUser->id]);

    $outRangeCoachUser = User::factory()->withRole('coach')->approved()->create(['name' => 'Out Range Coach']);
    $outRangeCoach = Coach::factory()->create(['user_id' => $outRangeCoachUser->id]);

    CoachSession::create([
        'schedule_id'   => $this->schedule->id,
        'coach_id'      => $inRangeCoach->id,
        'session_date'  => '2026-06-15',
        'role'          => 'primary',
        'checked_in_at' => now(),
    ]);

    CoachSession::create([
        'schedule_id'   => $this->schedule->id,
        'coach_id'      => $outRangeCoach->id,
        'session_date'  => '2026-05-01',
        'role'          => 'primary',
        'checked_in_at' => now(),
    ]);

    Livewire::actingAs($this->admin)
        ->test(Attendances::class)
        ->set('activeTab', 'coaches')
        ->set('filterDateFrom', '2026-06-01')
        ->set('filterDateTo', '2026-06-30')
        ->assertSee('In Range Coach')
        ->assertDontSee('Out Range Coach');
});
