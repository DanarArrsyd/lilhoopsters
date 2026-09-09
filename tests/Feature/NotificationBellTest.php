<?php

use App\Livewire\NotificationBell;
use App\Models\Notification;
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

    $this->user = User::factory()->withRole('parent')->approved()->create();
});

it('counts every unread notification, not just the latest 20 shown', function () {
    // 25 unread notifications: the bell only ever displays the latest 20,
    // but the badge must still report all 25 as unread.
    for ($i = 0; $i < 25; $i++) {
        Notification::create([
            'user_id' => $this->user->id,
            'type'    => 'test',
            'title'   => "Notification {$i}",
            'body'    => 'Body',
            'is_read' => false,
        ]);
    }

    Livewire::actingAs($this->user)
        ->test(NotificationBell::class)
        ->assertSet('isOpen', false)
        ->assertViewHas('unreadCount', 25);
});

it('does not count read notifications', function () {
    Notification::create([
        'user_id' => $this->user->id,
        'type'    => 'test',
        'title'   => 'Read one',
        'body'    => 'Body',
        'is_read' => true,
        'read_at' => now(),
    ]);
    Notification::create([
        'user_id' => $this->user->id,
        'type'    => 'test',
        'title'   => 'Unread one',
        'body'    => 'Body',
        'is_read' => false,
    ]);

    Livewire::actingAs($this->user)
        ->test(NotificationBell::class)
        ->assertViewHas('unreadCount', 1);
});
