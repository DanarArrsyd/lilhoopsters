<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Role;
use App\Models\User;

class NotificationService
{
    public static function send(int $userId, string $type, string $title, string $body, array $data = []): void
    {
        Notification::create([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'body'    => $body,
            'data'    => $data ?: null,
        ]);

        $user = User::find($userId);
        if ($user?->whatsapp_number) {
            app(WhatsAppService::class)->send(
                $user->whatsapp_number,
                "*{$title}*\n{$body}\n\n_Lil' Hoopsters_"
            );
        }
    }

    public static function toAdmins(string $type, string $title, string $body, array $data = []): void
    {
        $adminRoleId = Role::where('name', 'admin')->value('id');
        if (!$adminRoleId) return;

        User::where('role_id', $adminRoleId)
            ->where('is_active', true)
            ->each(fn($u) => static::send($u->id, $type, $title, $body, $data));
    }
}
