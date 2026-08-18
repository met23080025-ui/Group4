<?php

namespace App\Policies;

use App\Models\ClassBooking;
use App\Models\User;
use App\Policies\Concerns\TenantPolicy;

class ClassBookingPolicy
{
    use TenantPolicy;

    // Chỉ Member tự đặt lớp cho chính mình (mục 12) — Staff/Owner không đặt hộ.
    public function create(User $user): bool
    {
        return $user->role === User::ROLE_MEMBER;
    }

    public function view(User $user, ClassBooking $booking): bool
    {
        if (in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true)) {
            return $this->sameGym($user, $booking);
        }

        return $user->role === User::ROLE_MEMBER
            && $user->member !== null
            && $user->member->id === $booking->member_id;
    }

    // Huỷ booking: chỉ member sở hữu booking đó tự huỷ.
    public function delete(User $user, ClassBooking $booking): bool
    {
        return $user->role === User::ROLE_MEMBER
            && $user->member !== null
            && $user->member->id === $booking->member_id;
    }
}
