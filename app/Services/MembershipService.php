<?php

namespace App\Services;

use App\Exceptions\CrossTenantOperationException;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Package;
use App\Models\Promotion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Business logic của workflow "chọn gói tập" (mục 26, giai đoạn trước thanh
 * toán): Member → Package → (tuỳ chọn) Promotion → Membership status=pending.
 *
 * QUAN TRỌNG: Service này KHÔNG BAO GIỜ tạo membership với status=active.
 * Việc active hóa chỉ xảy ra sau khi Payment được Staff/Admin xác nhận
 * (Ngày 2 — Khối Payment + VietQR). Xem TODO ở cuối method create().
 */
class MembershipService
{
    public function create(Member $member, Package $package, ?Promotion $promotion, array $attributes = []): Membership
    {
        $this->guardSameGym($member, $package, $promotion);

        if ($promotion && ! $promotion->isValidNow()) {
            throw new InvalidArgumentException(
                "Khuyến mãi '{$promotion->code}' đã hết hạn, chưa bắt đầu, đã hết lượt dùng, hoặc đang tạm dừng."
            );
        }

        return DB::transaction(function () use ($member, $package, $promotion, $attributes) {
            $originalPrice = (string) $package->price;
            $discountAmount = $this->calculateDiscount($originalPrice, $promotion);
            $finalPrice = bcsub($originalPrice, $discountAmount, 2);

            $startDate = $attributes['start_date'] ?? now()->toDateString();
            $endDate = Carbon::parse($startDate)->addDays($package->duration_days)->toDateString();

            $membership = Membership::create([
                'gym_id' => $member->gym_id,
                'member_id' => $member->id,
                'package_id' => $package->id,
                'promotion_id' => $promotion?->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'original_price' => $originalPrice,
                'discount_amount' => $discountAmount,
                'final_price' => $finalPrice,
                'remaining_pt_sessions' => $package->pt_sessions,
                // TUYỆT ĐỐI KHÔNG active ở đây.
                'status' => Membership::STATUS_PENDING,
            ]);

            if ($promotion) {
                $promotion->increment('used_count');
            }

            // TODO (Ngày 2 — Khối Payment + VietQR): sau khi Payment của membership
            // này được Staff/Admin xác nhận (payment.status = paid), cập nhật
            // $membership->status = Membership::STATUS_ACTIVE trong 1 transaction
            // cùng với việc tạo Invoice. KHÔNG active ở bất kỳ đâu khác.

            return $membership;
        });
    }

    /**
     * Tính tiền giảm giá: percent = original * value/100, fixed = value.
     * Luôn chặn không cho vượt quá original_price (final_price không âm).
     * Dùng bcmath (không dùng float) để tránh sai số làm tròn tiền.
     */
    private function calculateDiscount(string $originalPrice, ?Promotion $promotion): string
    {
        if (! $promotion) {
            return '0.00';
        }

        if ($promotion->discount_type === Promotion::DISCOUNT_TYPE_PERCENT) {
            $percent = bcdiv((string) $promotion->discount_value, '100', 6);
            $discount = bcmul($originalPrice, $percent, 2);
        } else {
            $discount = bcadd((string) $promotion->discount_value, '0', 2);
        }

        if (bccomp($discount, $originalPrice, 2) > 0) {
            $discount = $originalPrice;
        }

        if (bccomp($discount, '0', 2) < 0) {
            $discount = '0.00';
        }

        return $discount;
    }

    private function guardSameGym(Member $member, Package $package, ?Promotion $promotion): void
    {
        if ($member->gym_id !== $package->gym_id) {
            throw new CrossTenantOperationException(
                'Không thể tạo membership: gói tập không thuộc cùng Gym với hội viên.'
            );
        }

        if ($promotion && $promotion->gym_id !== $member->gym_id) {
            throw new CrossTenantOperationException(
                'Không thể tạo membership: khuyến mãi không thuộc cùng Gym với hội viên.'
            );
        }
    }
}
