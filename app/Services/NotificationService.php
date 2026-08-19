<?php

namespace App\Services;

use App\Models\Gym;
use App\Models\Membership;
use App\Models\Notification;
use App\Models\User;

/**
 * Điểm tập trung tạo Notification (Khối 2, Ngày 3) — mọi trigger trong hệ
 * thống (comment mới, class đặt/huỷ, announcement mới, membership sắp hết
 * hạn, payment_confirmed từ Ngày 2...) đều đi qua đây, không rải rác
 * `Notification::create()` khắp Controller/Service.
 */
class NotificationService
{
    public function notify(User $user, string $type, string $title, ?string $body = null, array $data = []): Notification
    {
        return Notification::create([
            'gym_id' => $user->gym_id,
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }

    /**
     * Thông báo hàng loạt cho mọi user của 1 Gym (dùng cho "announcement mới").
     * $exceptUserId để không tự thông báo cho chính tác giả.
     */
    public function notifyGymUsers(Gym $gym, string $type, string $title, ?string $body = null, array $data = [], ?int $exceptUserId = null): int
    {
        $users = User::query()
            ->where('gym_id', $gym->id)
            ->when($exceptUserId, fn ($q) => $q->where('id', '!=', $exceptUserId))
            ->get();

        foreach ($users as $user) {
            $this->notify($user, $type, $title, $body, $data);
        }

        return $users->count();
    }

    /**
     * Trigger "membership sắp hết hạn" (mục 2, Ngày 3) — quét Membership
     * active có end_date trong vòng $daysAhead ngày tới. Idempotent trong
     * cùng 1 ngày: nếu user đã được thông báo hôm nay rồi thì bỏ qua, tránh
     * spam khi command được chạy lại nhiều lần/ngày (vd. lúc demo).
     */
    public function notifyExpiringMemberships(int $daysAhead = 3): int
    {
        $today = now()->toDateString();
        $limit = now()->addDays($daysAhead)->toDateString();

        $memberships = Membership::query()
            ->withoutGlobalScope('gym')
            ->where('status', Membership::STATUS_ACTIVE)
            ->whereDate('end_date', '>=', $today)
            ->whereDate('end_date', '<=', $limit)
            ->with('member.user')
            ->get();

        $notified = 0;

        foreach ($memberships as $membership) {
            $user = $membership->member?->user;

            if (! $user) {
                continue;
            }

            $alreadyNotifiedToday = Notification::query()
                ->withoutGlobalScope('gym')
                ->where('user_id', $user->id)
                ->where('type', Notification::TYPE_MEMBERSHIP_EXPIRING)
                ->whereDate('created_at', $today)
                ->exists();

            if ($alreadyNotifiedToday) {
                continue;
            }

            $this->notify(
                $user,
                Notification::TYPE_MEMBERSHIP_EXPIRING,
                'Membership sắp hết hạn',
                "Gói tập của bạn sẽ hết hạn vào {$membership->end_date->format('d/m/Y')}. Vui lòng gia hạn để không bị gián đoạn.",
                ['membership_id' => $membership->id],
            );
            $notified++;
        }

        return $notified;
    }
}
