<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;
use App\Policies\Concerns\TenantPolicy;

class ReactionPolicy
{
    use TenantPolicy;

    // Bất kỳ ai cùng Gym với post đều react được (toggle xử lý ở Service/Controller).
    public function create(User $user, Post $post): bool
    {
        return $user->gym_id !== null && $user->gym_id === $post->gym_id;
    }
}
