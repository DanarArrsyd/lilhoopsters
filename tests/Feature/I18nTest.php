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
        ->get(route('parent.dashboard'))
        ->assertSee('Dashboard')
        ->assertDontSee('Dasbor');
});

it('renders the portal in Indonesian when the user locale is id', function () {
    $this->parent->update(['locale' => 'id']);

    $this->actingAs($this->parent)
        ->get(route('parent.dashboard'))
        ->assertSee('Dasbor')        // dashboard title
        ->assertSee('Berita')        // nav: News
        ->assertSee('Pembayaran');   // nav: Payments
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

it('localises parent page headers to Indonesian', function () {
    $this->parent->update(['locale' => 'id']);

    $this->actingAs($this->parent)->get(route('parent.payments'))
        ->assertSee('Pembayaran')
        ->assertSee('Riwayat transaksi');

    $this->actingAs($this->parent)->get(route('parent.attendance'))
        ->assertSee('Kehadiran');
});

it('fully localises the leave requests page to Indonesian', function () {
    $this->parent->update(['locale' => 'id']);

    $this->actingAs($this->parent)->get(route('parent.leaves'))
        ->assertSee('Izin / Sakit')                  // page title
        ->assertSee('Belum ada pendaftaran aktif');  // empty state (no enrollment)
});

it('fully localises the make-up classes page to Indonesian', function () {
    $this->parent->update(['locale' => 'id']);

    $this->actingAs($this->parent)->get(route('parent.makeup'))
        ->assertSee('Kelas Pengganti')                          // page title
        ->assertSee('Belum ada permintaan kelas pengganti');   // empty state
});

it('fully localises the parent Events page to Indonesian', function () {
    $this->parent->update(['locale' => 'id']);

    $this->actingAs($this->parent)->get(route('parent.events'))
        ->assertSee('Acara')              // page title
        ->assertSee('Belum ada acara');   // empty state (no open events)
});

it('fully localises the payments page to Indonesian', function () {
    $this->parent->update(['locale' => 'id']);

    $this->actingAs($this->parent)->get(route('parent.payments'))
        ->assertSee('Pembayaran')               // page title
        ->assertSee('Belum ada transaksi');     // empty state
});

it('fully localises the attendance page to Indonesian', function () {
    $this->parent->update(['locale' => 'id']);

    $this->actingAs($this->parent)->get(route('parent.attendance'))
        ->assertSee('Kehadiran')                // page title
        ->assertSee('Tidak ada program aktif'); // empty state
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

it('fully localises the My Players and News pages to Indonesian', function () {
    $this->parent->update(['locale' => 'id']);

    $this->actingAs($this->parent)->get(route('parent.players'))
        ->assertSee('Anak Saya')        // page title
        ->assertSee('Belum ada pemain'); // empty state

    $this->actingAs($this->parent)->get(route('parent.news'))
        ->assertSee('Berita')           // page title
        ->assertSee('Belum ada berita'); // empty state
});
