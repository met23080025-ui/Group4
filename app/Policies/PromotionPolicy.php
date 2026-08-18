<?php

namespace App\Policies;

use App\Models\Promotion;
use App\Models\User;
use App\Policies\Concerns\TenantPolicy;

/**
 * Không nằm trong danh sách 5 policy gốc của Khối 6, nhưng CRUD Promotion ở
 * Khối 8 cần authorization ở backend (mục 22) như mọi resource khác — mở
 * rộng tự nhiên theo đúng pattern đã có (dùng chung trait TenantPolicy).
 */
class PromotionPolicy
{
    use TenantPolicy;

    // Member cũng cần xem để biết đang có ưu đãi gì khi chọn gói (mục 26).
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF, User::ROLE_MEMBER], true);
    }

    public function view(User $user, Promotion $promotion): bool
    {
        return $this->sameGym($user, $promotion);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true);
    }

    public function update(User $user, Promotion $promotion): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true)
            && $this->sameGym($user, $promotion);
    }

    public function delete(User $user, Promotion $promotion): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true)
            && $this->sameGym($user, $promotion);
    }
}
