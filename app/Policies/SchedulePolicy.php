<?php

namespace App\Policies;

use App\Models\Schedule;
use App\Models\User;
use App\Policies\Concerns\TenantPolicy;

class SchedulePolicy
{
    use TenantPolicy;

    // Mọi role trong Gym đều xem được lịch tập (member đặt lớp, trainer xem lịch dạy).
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            User::ROLE_GYM_OWNER, User::ROLE_STAFF, User::ROLE_TRAINER, User::ROLE_MEMBER,
        ], true);
    }

    public function view(User $user, Schedule $schedule): bool
    {
        return $this->sameGym($user, $schedule);
    }

    // Chỉ Owner/Staff được tạo/sửa/hủy lớp (mục 11) — Trainer/Member chỉ xem và đặt lớp.
    public function create(User $user): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true);
    }

    public function update(User $user, Schedule $schedule): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true)
            && $this->sameGym($user, $schedule);
    }

    public function delete(User $user, Schedule $schedule): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true)
            && $this->sameGym($user, $schedule);
    }
}
