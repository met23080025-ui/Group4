<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;
use App\Policies\Concerns\TenantPolicy;

/**
 * Không nhận BodyMeasurement instance mà nhận Member — mọi ability ở đây
 * đều là "được thao tác trên chỉ số cơ thể của member X hay không", giống
 * cách Laravel hỗ trợ authorize('create', [Model::class, $extraArg]).
 */
class BodyMeasurementPolicy
{
    use TenantPolicy;

    public function viewAny(User $user, Member $member): bool
    {
        return $this->canAccess($user, $member);
    }

    // Member/Trainer/Staff/Owner đều được TỰ NHẬP (mục 15) — chỉ khác phạm vi.
    public function create(User $user, Member $member): bool
    {
        return $this->canAccess($user, $member);
    }

    /**
     * Owner/Staff: mọi member cùng Gym. Trainer: CHỈ member đã được phân
     * công cho chính mình (không phải mọi member cùng Gym — khác Owner/Staff).
     * Member: chỉ chính mình.
     */
    private function canAccess(User $user, Member $member): bool
    {
        if (in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true)) {
            return $this->sameGym($user, $member);
        }

        if ($user->role === User::ROLE_TRAINER) {
            return $user->trainer !== null && $member->trainer_id === $user->trainer->id;
        }

        if ($user->role === User::ROLE_MEMBER) {
            return $user->member !== null && $user->member->id === $member->id;
        }

        return false;
    }
}
