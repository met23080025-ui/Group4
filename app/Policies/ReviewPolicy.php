<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;
use App\Policies\Concerns\TenantPolicy;

/**
 * Review/Rating (Khối 2, Ngày 3): Member review Gym (trainer_id=null) hoặc
 * review 1 Trainer cụ thể. Owner/Staff kiểm duyệt (ẩn review không phù hợp).
 * Trainer chỉ xem review VỀ MÌNH — lọc ở tầng Controller (query theo
 * trainer_id), không phải per-instance Policy, giống cách PaymentController::mine()
 * đã làm cho Payment.
 */
class ReviewPolicy
{
    use TenantPolicy;

    // Ai đã đăng nhập + có gym cũng xem được feed review công khai (is_visible),
    // riêng nội dung hiển thị theo role được lọc ở Controller.
    public function viewAny(User $user): bool
    {
        return $user->gym_id !== null;
    }

    // Chỉ Member tự viết review (cho Gym hoặc cho 1 Trainer), cần đã có hồ sơ Member.
    public function create(User $user): bool
    {
        return $user->role === User::ROLE_MEMBER && $user->member !== null;
    }

    // Ẩn/hiện review không phù hợp: chỉ Owner/Staff, cùng Gym.
    public function moderate(User $user, Review $review): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true) && $this->sameGym($user, $review);
    }
}
