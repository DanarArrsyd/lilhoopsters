<?php
// tests/Feature/I18nTest.php

use App\Livewire\LocaleSwitcher;
use App\Models\Role;
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

    $this->parent = User::factory()->withRole('parent')->approved()->create();
});

it('defaults to English for a parent with no saved locale', function () {
    $this->actingAs($this->parent)
        ->get(route('parent.home'))
        ->assertSee('No players yet')
        ->assertDontSee('Belum ada pemain');
});

it('renders the portal in Indonesian when the user locale is id', function () {
    $this->parent->update(['locale' => 'id']);

    $this->actingAs($this->parent)
        ->get(route('parent.home'))
        ->assertSee('Belum ada pemain');
});

it('switcher saves the chosen locale to the user', function () {
    Livewire::actingAs($this->parent)->test(LocaleSwitcher::class)
        ->call('switchTo', 'id');

    expect($this->parent->fresh()->locale)->toBe('id');
});

it('switcher ignores an unsupported locale', function () {
    Livewire::actingAs($this->parent)->test(LocaleSwitcher::class)
        ->call('switchTo', 'fr');

    expect($this->parent->fresh()->locale)->toBeNull();
});

it('fully localises the coach portal to Indonesian', function () {
    $coach = User::factory()->withRole('coach')->approved()->create(['locale' => 'id']);

    $this->actingAs($coach)->get(route('coach.dashboard'))
        ->assertSee('Dasbor')
        ->assertSee('Minggu Ini');

    $this->actingAs($coach)->get(route('coach.profile'))
        ->assertSee('Pengaturan Profil')
        ->assertSee('Informasi Pribadi');
});

it('fully localises the News page to Indonesian', function () {
    $this->parent->update(['locale' => 'id']);

    $this->actingAs($this->parent)->get(route('parent.news'))
        ->assertSee('Berita')           // page title
        ->assertSee('Belum ada berita'); // empty state
});

it('fully localises the admin portal to Indonesian', function () {
    $admin = User::factory()->withRole('admin')->approved()->create(['locale' => 'id']);

    $this->actingAs($admin)->get(route('admin.dashboard'))
        ->assertSee('Dasbor')      // page title
        ->assertSee('Minggu Ini'); // dashboard section heading

    $this->actingAs($admin)->get(route('admin.reports'))
        ->assertSee('Bulan Ini'); // preset pill

    $this->actingAs($admin)->get(route('admin.owner'))
        ->assertSee('Retensi & Perpanjangan')  // renewal section heading
        ->assertSee('Pembayaran Tertunggak');   // AR section heading
});
