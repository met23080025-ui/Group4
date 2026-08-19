<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use App\Policies\Concerns\TenantPolicy;

/**
 * Community feed (Khối 1, Ngày 3): Owner/Staff/Trainer đăng bài, Owner/Staff
 * kiểm duyệt (sửa/xoá MỌI bài trong Gym + ghim announcement), Trainer chỉ
 * sửa/xoá bài của chính mình, Member chỉ xem + tương tác (không tự đăng bài).
 */
class PostPolicy
{
    use TenantPolicy;

    public function viewAny(User $user): bool
    {
        return $user->gym_id !== null;
    }

    public function view(User $user, Post $post): bool
    {
        return $this->sameGym($user, $post);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF, User::ROLE_TRAINER], true);
    }

    public function update(User $user, Post $post): bool
    {
        if (in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true)) {
            return $this->sameGym($user, $post);
        }

        return $user->role === User::ROLE_TRAINER && $this->sameGym($user, $post) && $post->user_id === $user->id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $this->update($user, $post);
    }

    // Ghim/gỡ ghim announcement lên đầu feed: chỉ Owner/Staff (mục 1).
    public function pin(User $user, Post $post): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true) && $this->sameGym($user, $post);
    }
}
