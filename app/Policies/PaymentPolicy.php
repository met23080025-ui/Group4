<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;
use App\Policies\Concerns\TenantPolicy;

class PaymentPolicy
{
    use TenantPolicy;

    public function viewAny(User $user): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF, User::ROLE_MEMBER], true);
    }

    public function view(User $user, Payment $payment): bool
    {
        if (in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true)) {
            return $this->sameGym($user, $payment);
        }

        if ($user->role === User::ROLE_MEMBER) {
            return $user->member !== null && $user->member->id === $payment->member_id;
        }

        return false;
    }

    // Member tạo yêu cầu thanh toán (VietQR) cho membership của chính mình.
    public function create(User $user): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF, User::ROLE_MEMBER], true);
    }

    /**
     * Xác nhận thanh toán (payment.status -> paid) CHỈ Staff/Owner được làm.
     * Member tuyệt đối không được tự xác nhận thanh toán của mình (mục 8).
     */
    public function update(User $user, Payment $payment): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true)
            && $this->sameGym($user, $payment);
    }

    public function delete(User $user, Payment $payment): bool
    {
        return in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true)
            && $this->sameGym($user, $payment);
    }
}
