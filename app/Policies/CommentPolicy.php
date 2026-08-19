<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Policies\Concerns\TenantPolicy;

class CommentPolicy
{
    use TenantPolicy;

    // Bất kỳ ai cùng Gym với post (member/trainer/staff/owner) đều comment được.
    public function create(User $user, Post $post): bool
    {
        return $user->gym_id !== null && $user->gym_id === $post->gym_id;
    }

    // Tự xoá comment của chính mình, hoặc Owner/Staff kiểm duyệt xoá comment bất kỳ trong Gym.
    public function delete(User $user, Comment $comment): bool
    {
        if ($comment->user_id === $user->id) {
            return true;
        }

        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true) && $this->sameGym($user, $comment);
    }
}
