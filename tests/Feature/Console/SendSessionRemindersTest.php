<?php

use App\Models\Child;
use App\Models\Enrollment;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Program;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use App\Services\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::insert([
        ['name' => 'super_admin', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'admin',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'coach',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'parent',      'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->location = Location::factory()->create();
    $this->program  = Program::factory()->create(['name' => 'MVP']);
    $this->tomorrow = strtolower(now()->addDay()->format('l'));
});

function enrolledChildForTomorrow(
    string $tomorrow,
    Location $location,
    ?Program $program,
    string $type = 'regular',
    ?string $childName = null,
): Child {
    $parent = User::factory()->withRole('parent')->approved()->create();
    $child  = Child::factory()->create(['user_id' => $parent->id, 'name' => $childName ?? 'Test Child']);

    $schedule = Schedule::factory()->create([
        'location_id' => $location->id,
        'program_id'  => $type === 'private' ? null : $program->id,
        'type'        => $type,
        'day_of_week' => $tomorrow,
        'start_time'  => '16:00:00',
        'end_time'    => '17:00:00',
    ]);

    Enrollment::factory()->program()->approved()->create([
        'child_id'    => $child->id,
        'schedule_id' => $schedule->id,
        'started_at'  => today()->subMonth(),
    ]);

    return $child;
}

it('sends a session reminder for a child with a regular session tomorrow', function () {
    $child = enrolledChildForTomorrow($this->tomorrow, $this->location, $this->program);

    $this->artisan('reminders:sessions')->assertSuccessful();

    $note = Notification::where('type', ReminderService::SESSION)->first();
    expect($note)->not->toBeNull();
    expect($note->user_id)->toBe($child->user_id);
    expect($note->body)->toContain($child->name);
    expect($note->body)->toContain('MVP');
});

it('sends a session reminder with a fallback label for a private session', function () {
    enrolledChildForTomorrow($this->tomorrow, $this->location, null, 'private');

    $this->artisan('reminders:sessions')->assertSuccessful();

    $note = Notification::where('type', ReminderService::SESSION)->first();
    expect($note->body)->toContain('Private Session');
});

it('combines multiple children of the same parent into one notification', function () {
    $parent = User::factory()->withRole('parent')->approved()->create();

    $child1 = Child::factory()->create(['user_id' => $parent->id, 'name' => 'Widia']);
    $child2 = Child::factory()->create(['user_id' => $parent->id, 'name' => 'Kai']);

    foreach ([$child1, $child2] as $child) {
        $schedule = Schedule::factory()->create([
            'location_id' => $this->location->id,
            'program_id'  => $this->program->id,
            'type'        => 'regular',
            'day_of_week' => $this->tomorrow,
        ]);

        Enrollment::factory()->program()->approved()->create([
            'child_id'    => $child->id,
            'schedule_id' => $schedule->id,
            'started_at'  => today()->subMonth(),
        ]);
    }

    $this->artisan('reminders:sessions')->assertSuccessful();

    expect(Notification::where('type', ReminderService::SESSION)->where('user_id', $parent->id)->count())->toBe(1);

    $note = Notification::where('type', ReminderService::SESSION)->first();
    expect($note->body)->toContain('Widia');
    expect($note->body)->toContain('Kai');
});

it('sends separate notifications to different parents', function () {
    $childA = enrolledChildForTomorrow($this->tomorrow, $this->location, $this->program, 'regular', 'Child A');
    $childB = enrolledChildForTomorrow($this->tomorrow, $this->location, $this->program, 'regular', 'Child B');

    $this->artisan('reminders:sessions')->assertSuccessful();

    expect(Notification::where('type', ReminderService::SESSION)->count())->toBe(2);
    expect(Notification::where('type', ReminderService::SESSION)->where('user_id', $childA->user_id)->exists())->toBeTrue();
    expect(Notification::where('type', ReminderService::SESSION)->where('user_id', $childB->user_id)->exists())->toBeTrue();
});

it('does not notify a parent whose child has no session tomorrow', function () {
    $otherDay = collect(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])
        ->first(fn($d) => $d !== $this->tomorrow);

    enrolledChildForTomorrow($otherDay, $this->location, $this->program);

    $this->artisan('reminders:sessions')->assertSuccessful();

    expect(Notification::where('type', ReminderService::SESSION)->count())->toBe(0);
});

it('does not duplicate a session reminder if the command runs twice the same day', function () {
    enrolledChildForTomorrow($this->tomorrow, $this->location, $this->program);

    $this->artisan('reminders:sessions')->assertSuccessful();
    $this->artisan('reminders:sessions')->assertSuccessful();

    expect(Notification::where('type', ReminderService::SESSION)->count())->toBe(1);
});
