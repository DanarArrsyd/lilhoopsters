<?php

namespace App\Services;

use App\Mail\ParentNotification;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * @param bool  $email        Also send a branded email to the user.
     * @param array $emailDetails Optional key => value rows for the email (e.g. a receipt).
     */
    public static function send(
        int $userId,
        string $type,
        string $title,
        string $body,
        array $data = [],
        bool $email = false,
        array $emailDetails = [],
    ): void {
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

        // Email is queued; a failure here must never break the triggering action.
        if ($email && $user?->email) {
            try {
                Mail::to($user->email)->queue(
                    new ParentNotification($title, $title, [$body], $emailDetails)
                );
            } catch (\Throwable $e) {
                report($e);
            }
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

    public static function toSuperAdmins(string $type, string $title, string $body, array $data = []): void
    {
        $roleId = Role::where('name', 'super_admin')->value('id');
        if (!$roleId) return;

        User::where('role_id', $roleId)
            ->where('is_active', true)
            ->each(fn($u) => static::send($u->id, $type, $title, $body, $data));
    }
}
