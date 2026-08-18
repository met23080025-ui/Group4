<?php

namespace App\Policies;

use App\Models\Package;
use App\Models\User;
use App\Policies\Concerns\TenantPolicy;

class PackagePolicy
{
    use TenantPolicy;

    // Ai đã thuộc một Gym cũng xem được danh sách gói (member cần xem để chọn gói - mục 26).
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            User::ROLE_GYM_OWNER, User::ROLE_STAFF, User::ROLE_TRAINER, User::ROLE_MEMBER,
        ], true);
    }

    public function view(User $user, Package $package): bool
    {
        return $this->sameGym($user, $package);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true);
    }

    public function update(User $user, Package $package): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true)
            && $this->sameGym($user, $package);
    }

    public function delete(User $user, Package $package): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true)
            && $this->sameGym($user, $package);
    }
}
