<?php

namespace App\Policies\Concerns;

use App\Models\User;

/**
 * Dùng chung cho mọi Policy trong hệ thống:
 * - before(): platform_admin luôn được phép (bypass mọi check khác của policy).
 * - sameGym(): so sánh gym_id trực tiếp từ $user->gym_id (User KHÔNG có global
 *   scope — xem ghi chú Khối 3/5), không query lại bảng users.
 */
trait TenantPolicy
{
    public function before(User $user): ?bool
    {
        return $user->role === User::ROLE_PLATFORM_ADMIN ? true : null;
    }

    protected function sameGym(User $user, object $model): bool
    {
        return $user->gym_id !== null && $user->gym_id === $model->gym_id;
    }
}
