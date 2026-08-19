<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;
use App\Models\WorkoutPlan;
use App\Policies\Concerns\TenantPolicy;

/**
 * Chỉ Owner/Staff/Trainer (đã được phân công) LẬP kế hoạch tập cho member —
 * Member không tự tạo/sửa plan của chính mình, chỉ được xem (mục 16).
 */
class WorkoutPlanPolicy
{
    use TenantPolicy;

    public function viewAny(User $user, Member $member): bool
    {
        return $this->canManage($user, $member)
            || ($user->role === User::ROLE_MEMBER && $user->member !== null && $user->member->id === $member->id);
    }

    public function create(User $user, Member $member): bool
    {
        return $this->canManage($user, $member);
    }

    public function update(User $user, WorkoutPlan $plan): bool
    {
        return $this->canManage($user, $plan->member);
    }

    public function delete(User $user, WorkoutPlan $plan): bool
    {
        return $this->canManage($user, $plan->member);
    }

    private function canManage(User $user, Member $member): bool
    {
        if (in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true)) {
            return $this->sameGym($user, $member);
        }

        if ($user->role === User::ROLE_TRAINER) {
            return $user->trainer !== null && $member->trainer_id === $user->trainer->id;
        }

        return false;
    }
}
