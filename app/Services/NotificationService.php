<?php

namespace App\Services;

use App\Mail\GenericNotificationMail;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Kirim in-app notification + email (kalau MAIL_MAILER diset).
     */
    public static function send(int $userId, string $type, string $title, string $message, ?string $actionUrl = null, array $data = []): void
    {
        // 1. In-app notification
        $notification = Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'data' => $data,
        ]);

        // 2. Email (kalau user punya email & mail configured)
        $user = User::find($userId);
        if ($user && $user->email) {
            try {
                Mail::to($user->email)->send(new GenericNotificationMail($notification));
            } catch (\Throwable $e) {
                // Mail failure gak boleh block in-app notification
                \Log::warning('Email send failed: ' . $e->getMessage());
            }
        }
    }
}
