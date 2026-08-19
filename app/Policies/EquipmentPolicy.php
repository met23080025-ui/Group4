<?php

namespace App\Policies;

use App\Models\Equipment;
use App\Models\User;
use App\Policies\Concerns\TenantPolicy;

/**
 * Equipment + maintenance (Khối 4, Ngày 3) — chỉ Owner/Staff quản lý, cùng
 * mức quyền với Member/Package (mục 3).
 */
class EquipmentPolicy
{
    use TenantPolicy;

    public function viewAny(User $user): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true);
    }

    public function view(User $user, Equipment $equipment): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true) && $this->sameGym($user, $equipment);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true);
    }

    public function update(User $user, Equipment $equipment): bool
    {
        return $this->view($user, $equipment);
    }

    public function delete(User $user, Equipment $equipment): bool
    {
        return $this->view($user, $equipment);
    }
}
