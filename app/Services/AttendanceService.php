<?php

namespace App\Services;

use App\Exceptions\CrossTenantOperationException;
use App\Models\Attendance;
use App\Models\LoyaltyPointTransaction;
use App\Models\Member;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * QR check-in (Khối 5 — Ngày 2, mục 6/17).
 *
 * Thiết kế token QR — KHÔNG mã hóa ID trần, KHÔNG chứa SĐT/tên:
 *   token = base64( member_id . '|' . HMAC-SHA256(member_id, qr_secret) )
 * `qr_secret` là chuỗi ngẫu nhiên 40 ký tự SINH RIÊNG cho từng member (cột
 * `members.qr_secret`, cast 'encrypted' — mã hóa tại rest bằng APP_KEY),
 * không bao giờ xuất hiện trong QR hay được trả về client. Nếu QR chỉ chứa
 * ID trần, ai cũng có thể tự tạo QR giả bằng cách đoán số nguyên liên tiếp
 * (member_id 1, 2, 3...) và check-in giả mạo hộ người khác — chữ ký HMAC
 * khiến việc giả mạo bất khả thi nếu không biết đúng qr_secret của member
 * đó (server so sánh bằng hash_equals — constant-time, chống timing attack).
 * Muốn thu hồi 1 token bị lộ chỉ cần cấp lại qr_secret mới cho riêng member
 * đó (không ảnh hưởng người khác) — UI "cấp lại mã QR" CHƯA làm ở khối này
 * (COULD, TODO), nhưng thiết kế đã hỗ trợ sẵn qua ensureQrSecret().
 */
class AttendanceService
{
    /**
     * Sinh (hoặc tái sử dụng) token QR hiện tại của member — gọi mỗi lần
     * member mở trang "QR của tôi". qr_secret sinh lười (lazy) lần xem đầu
     * tiên, sau đó tái sử dụng để QR không đổi liên tục giữa các lần xem.
     */
    public function tokenFor(Member $member): string
    {
        $secret = $this->ensureQrSecret($member);

        return base64_encode($member->id.'|'.$this->sign($member->id, $secret));
    }

    /**
     * Xác thực token + ghi nhận check-in. Toàn bộ rule nghiệp vụ nằm ở đây
     * (Controller chỉ lo authorization + hiển thị), theo đúng convention đã
     * dùng cho ClassBookingService/PaymentService.
     */
    public function checkIn(string $rawToken, User $scannedBy): Attendance
    {
        $member = $this->resolveMember($rawToken);

        if (! $member) {
            throw new InvalidArgumentException('Mã QR không hợp lệ hoặc đã bị thay đổi.');
        }

        // Rule: token của member Gym A quét ở Gym B -> chặn. Member được đọc
        // qua withoutGlobalScope (xem resolveMember) nên phải tự so sánh
        // gym_id tường minh ở đây — cùng pattern 2 lớp phòng vệ cross-tenant
        // đã dùng ở ClassBookingService::book()/MembershipService.
        if ($member->gym_id !== $scannedBy->gym_id) {
            throw new CrossTenantOperationException(
                'Mã QR này thuộc hội viên của Gym khác, không thể check-in tại đây.'
            );
        }

        // Rule: member bị khóa -> chặn.
        if ($member->status === Member::STATUS_BLOCKED) {
            throw new InvalidArgumentException('Hội viên đang bị khóa, không thể check-in.');
        }

        // Rule: membership hết hạn (hoặc chưa từng có) -> chặn. Cố tình query
        // trực tiếp status=active + trong khoảng ngày thay vì dùng
        // Member::currentMembership()/status, giống lý do đã ghi ở
        // ClassBookingService::findActiveValidMembership() — không có job nào
        // tự chuyển Membership sang expired khi hết hạn.
        if (! $this->hasValidMembership($member)) {
            throw new InvalidArgumentException('Hội viên chưa có gói tập đang hoạt động hoặc gói đã hết hạn.');
        }

        return DB::transaction(function () use ($member) {
            $today = now()->toDateString();

            // Rule: không check-in trùng trong cùng 1 ngày. Có sẵn unique index
            // (gym_id, member_id, check_in_date) ở tầng DB (Khối 2) — pre-check
            // ở đây để trả thông báo tiếng Việt rõ ràng, và vẫn bắt QueryException
            // bên dưới làm lớp phòng vệ thứ 2 khi 2 request check-in cùng lúc.
            // whereDate() (không phải where() so trực tiếp chuỗi) vì cột
            // check_in_date cast 'date' được SQLite (dùng khi test) lưu nguyên
            // dạng "Y-m-d 00:00:00" thay vì "Y-m-d" thuần — so sánh chuỗi trực
            // tiếp sẽ KHÔNG khớp dù cùng ngày (đã xác minh qua test đỏ, cùng
            // loại vấn đề đã ghi chú ở ClassBookingService cho class_date).
            $alreadyCheckedIn = Attendance::query()
                ->withoutGlobalScope('gym')
                ->where('gym_id', $member->gym_id)
                ->where('member_id', $member->id)
                ->whereDate('check_in_date', $today)
                ->exists();

            if ($alreadyCheckedIn) {
                throw new InvalidArgumentException('Hội viên đã check-in hôm nay rồi.');
            }

            try {
                $attendance = Attendance::create([
                    'gym_id' => $member->gym_id,
                    'member_id' => $member->id,
                    'check_in_date' => $today,
                    'check_in_time' => now(),
                    'source' => Attendance::SOURCE_QR,
                ]);
            } catch (QueryException $e) {
                if ($this->isDuplicateCheckIn($e)) {
                    throw new InvalidArgumentException('Hội viên đã check-in hôm nay rồi.');
                }

                throw $e;
            }

            $this->awardLoyaltyPoints($member, $attendance);

            return $attendance;
        });
    }

    /**
     * Cộng điểm loyalty khi check-in (mục 17). Ghi 1 dòng
     * `loyalty_point_transactions` đúng cấu trúc bảng đã có sẵn từ Khối 2.
     *
     * TODO (module Loyalty — nhóm COULD, CHƯA build UI/service riêng): hiện
     * balance_after chỉ tính bằng cách đọc dòng gần nhất của member (không
     * khóa dòng) — AN TOÀN cho check-in vì unique constraint đã đảm bảo tối
     * đa 1 lần cộng điểm/member/ngày trong transaction này, nhưng KHI module
     * Loyalty đầy đủ được xây (thêm nguồn cộng/trừ điểm khác như review, đổi
     * quà...), cần khóa dòng cuối cùng (lockForUpdate) trước khi tính
     * balance_after để tránh lost-update giữa nhiều nguồn cộng điểm đồng thời.
     */
    private function awardLoyaltyPoints(Member $member, Attendance $attendance): void
    {
        $lastBalance = LoyaltyPointTransaction::query()
            ->withoutGlobalScope('gym')
            ->where('member_id', $member->id)
            ->latest('id')
            ->value('balance_after') ?? 0;

        LoyaltyPointTransaction::create([
            'gym_id' => $member->gym_id,
            'member_id' => $member->id,
            'points' => LoyaltyPointTransaction::POINTS_CHECK_IN,
            'reason' => LoyaltyPointTransaction::REASON_CHECK_IN,
            'reference_type' => Attendance::class,
            'reference_id' => $attendance->id,
            'balance_after' => $lastBalance + LoyaltyPointTransaction::POINTS_CHECK_IN,
        ]);
    }

    private function ensureQrSecret(Member $member): string
    {
        if ($member->qr_secret) {
            return $member->qr_secret;
        }

        $secret = Str::random(40);
        $member->forceFill(['qr_secret' => $secret])->save();

        return $secret;
    }

    private function sign(int $memberId, string $secret): string
    {
        return hash_hmac('sha256', (string) $memberId, $secret);
    }

    /**
     * Giải mã + xác thực chữ ký. Đọc Member qua withoutGlobalScope vì cần
     * biết member này (nếu hợp lệ) thuộc Gym nào để so sánh cross-tenant ở
     * checkIn() ngay sau đó — KHÔNG dùng để bỏ qua tenant isolation, chỉ để
     * tự quyết định lỗi phù hợp (token sai vs. token đúng-nhưng-khác-Gym).
     */
    private function resolveMember(string $rawToken): ?Member
    {
        $decoded = base64_decode($rawToken, true);

        if ($decoded === false || ! str_contains($decoded, '|')) {
            return null;
        }

        [$idPart, $signature] = explode('|', $decoded, 2);

        if (! ctype_digit($idPart)) {
            return null;
        }

        /** @var Member|null $member */
        $member = Member::query()->withoutGlobalScope('gym')->find((int) $idPart);

        if (! $member || ! $member->qr_secret) {
            return null;
        }

        $expected = $this->sign($member->id, $member->qr_secret);

        return hash_equals($expected, $signature) ? $member : null;
    }

    private function hasValidMembership(Member $member): bool
    {
        $today = now()->toDateString();

        return Membership::query()
            ->withoutGlobalScope('gym')
            ->where('member_id', $member->id)
            ->where('status', Membership::STATUS_ACTIVE)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->exists();
    }

    /**
     * SQLSTATE 23000 = integrity constraint violation trên cả MySQL lẫn
     * SQLite (dùng khi test), nhưng thông điệp lỗi khác nhau giữa 2 driver
     * (MySQL nêu tên constraint "attendances_gym_id_member_id_check_in_date_unique",
     * SQLite liệt kê thẳng tên cột "UNIQUE constraint failed: attendances...")
     * nên chỉ cần khớp SQLSTATE + tên bảng, không dựa vào tên constraint.
     */
    private function isDuplicateCheckIn(QueryException $e): bool
    {
        return $e->getCode() === '23000' && str_contains($e->getMessage(), 'attendances');
    }
}
