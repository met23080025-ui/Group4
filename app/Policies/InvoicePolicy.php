<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;
use App\Policies\Concerns\TenantPolicy;

class InvoicePolicy
{
    use TenantPolicy;

    /**
     * Staff/Owner tải hóa đơn trong Gym mình; Member chỉ tải hóa đơn của
     * chính mình. Cross-tenant hoặc member khác không thấy -> 403/404
     * (route model binding của Invoice đã áp global scope BelongsToGym,
     * nên khác Gym sẽ 404 trước khi tới đây; policy này chặn thêm
     * trường hợp cùng Gym nhưng khác member, hoặc platform_admin xem chéo).
     */
    public function view(User $user, Invoice $invoice): bool
    {
        if (in_array($user->role, [User::ROLE_GYM_OWNER, User::ROLE_STAFF], true)) {
            return $this->sameGym($user, $invoice);
        }

        if ($user->role === User::ROLE_MEMBER) {
            return $user->member !== null && $user->member->id === $invoice->member_id;
        }

        return false;
    }
}
