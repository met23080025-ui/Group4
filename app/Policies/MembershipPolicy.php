<?php

namespace App\Policies;

use App\Models\Membership;
use App\Models\User;
use App\Policies\Concerns\TenantPolicy;

class MembershipPolicy
{
    use TenantPolicy;

    public function viewAny(User $user): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF, User::ROLE_MEMBER], true);
    }

    public function view(User $user, Membership $membership): bool
    {
        if (in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true)) {
            return $this->sameGym($user, $membership);
        }

        if ($user->role === User::ROLE_MEMBER) {
            return $user->member !== null && $user->member->id === $membership->member_id;
        }

        return false;
    }

    // Member tự khởi tạo đăng ký gói cho chính mình (mục 26); Staff/Owner tạo hộ.
    public function create(User $user): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF, User::ROLE_MEMBER], true);
    }

    public function update(User $user, Membership $membership): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true)
            && $this->sameGym($user, $membership);
    }

    public function delete(User $user, Membership $membership): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true)
            && $this->sameGym($user, $membership);
    }
}
