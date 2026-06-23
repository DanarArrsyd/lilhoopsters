<?php

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class NotificationBell extends Component
{
    public bool $isOpen = false;

    public function toggle(): void
    {
        $this->isOpen = !$this->isOpen;
    }

    public function close(): void
    {
        $this->isOpen = false;
    }

    public function markRead(int $id): void
    {
        auth()->user()->appNotifications()
            ->where('id', $id)
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    public function markAllRead(): void
    {
        auth()->user()->appNotifications()
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);
    }

    public function render()
    {
        $notifications = auth()->user()->appNotifications()
            ->latest()
            ->limit(20)
            ->get();

        $unreadCount = $notifications->where('is_read', false)->count();

        return view('livewire.notification-bell', compact('notifications', 'unreadCount'));
    }
}
