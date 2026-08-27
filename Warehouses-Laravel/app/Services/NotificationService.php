<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    /**
     * إنشاء إشعار عام لأي "Notifiable" (مدير، موظف، ...).
     */
    public function notify($notifiable, string $title, string $message, ?string $type = null, ?int $relatedId = null)
    {
        return Notification::create([
            'notifiable_id'   => $notifiable->id,
            'notifiable_type' => get_class($notifiable), // بيربط الإشعار مع الموديل
            'title'           => $title,
            'message'         => $message,
            'type'            => $type,
            'related_id'      => $relatedId,
            'is_read'         => false, // بشكل افتراضي غير مقروء
        ]);
    }
}
