<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;
use App\Policies\Concerns\TenantPolicy;

class MemberPolicy
{
    use TenantPolicy;

    public function viewAny(User $user): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true);
    }

    public function view(User $user, Member $member): bool
    {
        if (in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true)) {
            return $this->sameGym($user, $member);
        }

        // Member tự xem hồ sơ của chính mình. $user->member có thể null nếu
        // tài khoản mới tự đăng ký chưa có hồ sơ Member (xem Khối 5) — không giả
        // định luôn tồn tại, chỉ so sánh khi có.
        if ($user->role === User::ROLE_MEMBER) {
            return $user->member !== null && $user->member->id === $member->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true);
    }

    public function update(User $user, Member $member): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true)
            && $this->sameGym($user, $member);
    }

    public function delete(User $user, Member $member): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true)
            && $this->sameGym($user, $member);
    }

    public function restore(User $user, Member $member): bool
    {
        return $this->delete($user, $member);
    }

    /**
     * Trainer chỉ "huấn luyện" (xem hồ sơ, ghi body measurement, tạo
     * workout/nutrition plan) được member ĐÃ ĐƯỢC PHÂN CÔNG cho chính mình
     * (Khối 6) — tách biệt với view()/update() ở trên (đó là quyền quản trị
     * hồ sơ member, chỉ dành cho Owner/Staff). sameGym() không đủ ở đây vì
     * còn phải khớp đúng trainer_id, không phải chỉ cùng Gym.
     */
    public function coach(User $user, Member $member): bool
    {
        return $user->role === User::ROLE_TRAINER
            && $user->trainer !== null
            && $member->trainer_id === $user->trainer->id;
    }
}
