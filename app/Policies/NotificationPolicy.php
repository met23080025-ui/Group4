<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;

/**
 * Notification hoàn toàn cá nhân — chỉ chính chủ mới xem/đánh dấu đã đọc
 * được, không có khái niệm Owner/Staff kiểm duyệt như Post/Review.
 */
class NotificationPolicy
{
    public function view(User $user, Notification $notification): bool
    {
        return $notification->user_id === $user->id;
    }

    public function update(User $user, Notification $notification): bool
    {
        return $notification->user_id === $user->id;
    }
}
