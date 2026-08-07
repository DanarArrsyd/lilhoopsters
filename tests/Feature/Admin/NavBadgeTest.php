<?php

use App\Models\Enrollment;
use App\Models\LeaveRequest;
use App\Models\MakeUpClass;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// The one class the sidebar-section count pill carries and nothing else does.
const NAV_BADGE_MARKER = 'bg-[#DC2626]';

beforeEach(function () {
    Role::insert([
        ['name' => 'super_admin', 'created_at' => now(), 'updated_at' => now()],
        ['name' => 'admin',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'coach',       'created_at' => now(), 'updated_at' => now()],
        ['name' => 'parent',      'created_at' => now(), 'updated_at' => now()],
    ]);

    $this->admin = User::factory()->withRole('admin')->approved()->create();
});

it('shows an aggregate badge on the Operations group trigger', function () {
    LeaveRequest::factory()->count(4)->create(['status' => 'pending']);
    MakeUpClass::factory()->count(2)->create(['status' => 'pending']);
    Enrollment::factory()->count(1)->create(['status' => 'pending']);

    // Nested factories may create extra records, so derive the true aggregate
    // the same way the nav composer does.
    $expected = LeaveRequest::where('status', 'pending')->count()
        + MakeUpClass::where('status', 'pending')->count()
        + Enrollment::where('status', 'pending')->count();

    expect($expected)->toBeGreaterThan(0);

    $html = $this->actingAs($this->admin)->get(route('admin.dashboard'))->getContent();

    // Match the badge element itself, not a bare number anywhere on the page —
    // the dashboard week strip renders day-of-month digits in spans too.
    expect($html)->toMatch(
        '/<span class="' . preg_quote(NAV_BADGE_MARKER, '/') . '[^"]*">\s*' . $expected . '\s*<\/span>/'
    );
});

it('renders no group badge when nothing is pending', function () {
    $html = $this->actingAs($this->admin)->get(route('admin.dashboard'))->getContent();

    // Operations label still present, but with no badge pill rendered at all.
    expect($html)->toContain(__('messages.admin.section.operations'));
    expect($html)->not->toContain(NAV_BADGE_MARKER);
});
