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
}
