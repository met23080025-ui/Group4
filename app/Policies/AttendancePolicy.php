<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\TenantPolicy;

class AttendancePolicy
{
    use TenantPolicy;

    // Staff/Owner quét QR check-in cho hội viên (mục 6) — Member không tự
    // check-in hộ chính mình qua action này (cần người vận hành xác nhận).
    public function create(User $user): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true);
    }

    // Xem nhật ký check-in trong ngày của Gym mình.
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true);
    }
}
