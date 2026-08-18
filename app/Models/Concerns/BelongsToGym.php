<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Multi-tenant isolation: mọi model dùng trait này bị lọc theo gym_id
 * của user đang đăng nhập, trừ platform_admin (thấy tất cả) và các
 * truy vấn chạy ngoài HTTP request (CLI/seeder — không có user đăng
 * nhập nên không bị lọc).
 */
trait BelongsToGym
{
    public static function bootBelongsToGym(): void
    {
        static::addGlobalScope('gym', function (Builder $builder): void {
            /** @var User|null $user */
            $user = auth()->user();

            // Chưa đăng nhập (guest) hoặc chạy qua CLI (seeder/tinker/queue):
            // không có tenant context nên không lọc, nếu không seeder sẽ hỏng.
            if (! $user) {
                return;
            }

            // Platform Admin quản lý toàn bộ nền tảng nên được thấy dữ liệu mọi Gym.
            if ($user->role === User::ROLE_PLATFORM_ADMIN) {
                return;
            }

            $builder->where($builder->getModel()->getTable().'.gym_id', $user->gym_id);
        });

        static::creating(function (Model $model): void {
            if (! is_null($model->gym_id)) {
                return;
            }

            $user = auth()->user();

            if ($user && $user->gym_id) {
                $model->gym_id = $user->gym_id;
            }
        });
    }

    /**
     * Truy vấn tường minh dữ liệu của một Gym cụ thể, bỏ qua global scope.
     * Dùng cho Platform Admin khi cần xem/quản lý dữ liệu của một Gym riêng lẻ.
     */
    public function scopeForGym(Builder $query, int $gymId): Builder
    {
        return $query->withoutGlobalScope('gym')->where($this->getTable().'.gym_id', $gymId);
    }

    /**
     * Truy vấn tường minh dữ liệu của TẤT CẢ các Gym, bỏ qua global scope.
     * Dùng cho Platform Admin dashboard (thống kê toàn nền tảng).
     */
    public function scopeAllGyms(Builder $query): Builder
    {
        return $query->withoutGlobalScope('gym');
    }
}
