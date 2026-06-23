<?php
// tests/Feature/Admin/MembersImportTest.php

use App\Livewire\Admin\MembersImport;
use App\Models\Child;
use App\Models\Enrollment;
use App\Models\Location;
use App\Models\Package;
use App\Models\Program;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\UploadedFile;
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

    $this->admin    = User::factory()->withRole('admin')->approved()->create();
    $this->location = Location::factory()->create(['name' => 'Cikarang Court']);
    $this->program  = Program::factory()->create(['name' => 'Junior']);
    $this->package  = Package::factory()->regular()->create([
        'location_id'   => $this->location->id,
        'name'          => 'Regular 8',
        'session_count' => 8,
    ]);
    $this->schedule = Schedule::factory()->create([
        'location_id' => $this->location->id,
        'program_id'  => $this->program->id,
        'day_of_week' => 'monday',
        'is_active'   => true,
    ]);

    $header = 'Parent Name,Parent Email,Parent WhatsApp,Child Name,Birth,Gender,School,Jersey,No,'
            . 'Location,Program,Day,Package,Remaining,Expiry,Start';
    $this->csv = function (string $dataRow) use ($header) {
        return UploadedFile::fake()->createWithContent('members.csv', "{$header}\n{$dataRow}\n");
    };
});

it('imports a plain member without an enrollment', function () {
    $row = 'Ahmad,ahmad@e.com,08123456789,Rizki,15/03/2016,male,,,,,,,,,,';

    Livewire::actingAs($this->admin)->test(MembersImport::class)
        ->set('file', ($this->csv)($row))
        ->call('import');

    $child = Child::where('name', 'Rizki')->first();
    expect($child)->not->toBeNull();
    expect($child->status)->toBe('active');
    expect(Enrollment::where('child_id', $child->id)->exists())->toBeFalse();
    expect(User::where('email', 'ahmad@e.com')->first()->registration_status)->toBe('approved');
});

it('imports a migrated member with an active program enrollment', function () {
    $row = 'Dewi,dewi@e.com,08987654321,Siti,22/08/2017,female,,,,'
         . 'Cikarang Court,Junior,Monday,Regular 8,5,31/12/2026,01/06/2026';

    Livewire::actingAs($this->admin)->test(MembersImport::class)
        ->set('file', ($this->csv)($row))
        ->call('import');

    $child = Child::where('name', 'Siti')->first();
    $enr   = Enrollment::where('child_id', $child->id)->first();

    expect($enr)->not->toBeNull();
    expect($enr->type)->toBe('program');
    expect($enr->status)->toBe('approved');
    expect($enr->schedule_id)->toBe($this->schedule->id);
    expect($enr->package_id)->toBe($this->package->id);
    expect($enr->remaining_sessions)->toBe(5);
    expect($enr->total_sessions)->toBe(8);
    expect($enr->expires_at->format('Y-m-d'))->toBe('2026-12-31');
    expect($enr->started_at->format('Y-m-d'))->toBe('2026-06-01');
    expect($enr->transaction_id)->toBeNull();
});

it('rejects a row whose package does not belong to the location', function () {
    Location::factory()->create(['name' => 'Other Court']);
    $row = 'Budi,budi@e.com,08111,Joko,10/10/2015,male,,,,'
         . 'Other Court,Junior,Monday,Regular 8,5,31/12/2026,';

    Livewire::actingAs($this->admin)->test(MembersImport::class)
        ->set('file', ($this->csv)($row))
        ->call('import');

    expect(Child::where('name', 'Joko')->exists())->toBeFalse();
    expect(User::where('email', 'budi@e.com')->exists())->toBeFalse();
});

it('rejects a row with an incomplete enrollment block', function () {
    // Package given but Day/Expiry missing.
    $row = 'Sari,sari@e.com,08222,Tono,11/11/2015,male,,,,'
         . 'Cikarang Court,Junior,,Regular 8,5,,';

    Livewire::actingAs($this->admin)->test(MembersImport::class)
        ->set('file', ($this->csv)($row))
        ->call('import');

    expect(Child::where('name', 'Tono')->exists())->toBeFalse();
});

it('rejects a row when no matching class schedule exists', function () {
    // Tuesday — but the only Junior class at Cikarang is Monday.
    $row = 'Rina,rina@e.com,08333,Bayu,12/12/2015,male,,,,'
         . 'Cikarang Court,Junior,Tuesday,Regular 8,5,31/12/2026,';

    Livewire::actingAs($this->admin)->test(MembersImport::class)
        ->set('file', ($this->csv)($row))
        ->call('import');

    expect(Child::where('name', 'Bayu')->exists())->toBeFalse();
});
