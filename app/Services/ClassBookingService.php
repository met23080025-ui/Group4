<?php

namespace App\Services;

use App\Exceptions\CrossTenantOperationException;
use App\Models\ClassBooking;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Schedule;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Đặt lớp/buổi PT (mục 12) và huỷ booking. Toàn bộ rule nằm ở đây (không ở
 * Controller) để Policy/Controller chỉ lo authorization, Service lo nghiệp vụ
 * + tính đúng đắn khi có tranh chấp đồng thời (capacity, remaining_pt_sessions).
 */
class ClassBookingService
{
    /**
     * Thứ tự khóa CỐ ĐỊNH để tránh deadlock giữa các request đồng thời:
     * Schedule -> Member -> Membership. Mọi luồng gọi book() đều khóa theo
     * đúng thứ tự này.
     */
    public function book(Member $member, Schedule $schedule): ClassBooking
    {
        if ($member->gym_id !== $schedule->gym_id) {
            throw new CrossTenantOperationException(
                'Không thể đặt lớp: lớp không thuộc cùng Gym với hội viên.'
            );
        }

        return DB::transaction(function () use ($member, $schedule) {
            // Khóa dòng Schedule (SELECT ... FOR UPDATE): 2 request đặt chỗ gần
            // đồng thời cho lớp gần đầy sẽ tuần tự hóa tại đây — request thứ 2
            // luôn đọc được bookedCount MỚI NHẤT (đã tính request thứ nhất vừa
            // commit), không bao giờ để lọt quá capacity.
            /** @var Schedule $lockedSchedule */
            $lockedSchedule = Schedule::query()->whereKey($schedule->id)->lockForUpdate()->firstOrFail();

            if ($lockedSchedule->status !== Schedule::STATUS_SCHEDULED) {
                throw new InvalidArgumentException('Lớp này hiện không nhận đặt chỗ (đã huỷ hoặc đã diễn ra).');
            }

            // Khóa dòng Member: tuần tự hóa các thao tác đặt chỗ CỦA CÙNG 1
            // member (kể cả với 2 Schedule khác nhau) — cần thiết để rule
            // "không đặt 2 lớp trùng khung giờ" đúng khi member bấm đặt 2 lớp
            // trùng giờ gần như đồng thời (2 tab, double-click...).
            /** @var Member $lockedMember */
            $lockedMember = Member::query()->whereKey($member->id)->lockForUpdate()->firstOrFail();

            if ($this->alreadyBooked($lockedMember, $lockedSchedule)) {
                throw new InvalidArgumentException('Bạn đã đặt lớp này rồi.');
            }

            $bookedCount = $lockedSchedule->classBookings()->where('status', ClassBooking::STATUS_BOOKED)->count();
            if ($bookedCount >= $lockedSchedule->capacity) {
                throw new InvalidArgumentException('Lớp đã đủ chỗ, vui lòng chọn buổi khác.');
            }

            $this->guardNoOverlappingBooking($lockedMember, $lockedSchedule);

            $membership = $this->findActiveValidMembership($lockedMember, $lockedSchedule->is_pt_session);

            if (! $membership) {
                throw new InvalidArgumentException(
                    'Hội viên chưa có gói tập đang hoạt động và còn hạn để đặt lớp.'
                );
            }

            if ($lockedSchedule->is_pt_session) {
                if ($membership->remaining_pt_sessions <= 0) {
                    throw new InvalidArgumentException('Gói tập đã hết số buổi PT còn lại.');
                }

                $membership->decrement('remaining_pt_sessions');
            }

            return ClassBooking::create([
                'gym_id' => $lockedSchedule->gym_id,
                'schedule_id' => $lockedSchedule->id,
                'member_id' => $lockedMember->id,
                'membership_id' => $membership->id,
                'status' => ClassBooking::STATUS_BOOKED,
                'booked_at' => now(),
            ]);
        });
    }

    /**
     * Huỷ booking: nếu booking thuộc buổi PT, hoàn lại đúng 1 remaining_pt_sessions
     * cho membership đã dùng lúc đặt (membership_id lưu sẵn trên booking — không
     * suy luận lại membership "hiện tại" của member, vì có thể đã đổi gói).
     */
    public function cancel(ClassBooking $booking): ClassBooking
    {
        return DB::transaction(function () use ($booking) {
            /** @var ClassBooking $locked */
            $locked = ClassBooking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== ClassBooking::STATUS_BOOKED) {
                throw new InvalidArgumentException('Booking này đã được huỷ trước đó, không thể huỷ lại.');
            }

            $locked->forceFill([
                'status' => ClassBooking::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ])->save();

            $schedule = Schedule::query()->withoutGlobalScope('gym')->find($locked->schedule_id);

            if ($schedule?->is_pt_session && $locked->membership_id) {
                Membership::query()->withoutGlobalScope('gym')
                    ->whereKey($locked->membership_id)
                    ->lockForUpdate()
                    ->increment('remaining_pt_sessions');
            }

            return $locked->fresh();
        });
    }

    private function alreadyBooked(Member $member, Schedule $schedule): bool
    {
        return $member->classBookings()
            ->where('schedule_id', $schedule->id)
            ->where('status', ClassBooking::STATUS_BOOKED)
            ->exists();
    }

    /**
     * 2 lớp coi là trùng khung giờ khi cùng class_date và khoảng [start,end)
     * giao nhau: start_a < end_b AND end_a > start_b.
     *
     * Cố tình KHÔNG lọc class_date/start_time/end_time bằng SQL thô: cột date
     * "date" cast của Eloquent lưu full datetime ("2026-08-19 00:00:00") —
     * MySQL (DATE column) tự cắt về đúng ngày, nhưng SQLite (dùng khi chạy
     * test) lưu nguyên chuỗi đầy đủ, nên so `class_date = '2026-08-19'` sẽ
     * KHÔNG khớp trên SQLite dù cùng ngày (đã xác minh bằng thực nghiệm — bug
     * y hệt từng gặp với start_time/end_time). Lấy toàn bộ booking đang
     * 'booked' của member (tập nhỏ) rồi so sánh bằng Carbon ở PHP — đúng và
     * nhất quán bất kể driver DB.
     */
    private function guardNoOverlappingBooking(Member $member, Schedule $schedule): void
    {
        $bookedSchedules = $member->classBookings()
            ->where('status', ClassBooking::STATUS_BOOKED)
            ->with('schedule')
            ->get()
            ->pluck('schedule');

        foreach ($bookedSchedules as $existing) {
            if (! $existing->class_date->isSameDay($schedule->class_date)) {
                continue;
            }

            if ($existing->start_time->lt($schedule->end_time) && $existing->end_time->gt($schedule->start_time)) {
                throw new InvalidArgumentException('Bạn đã có lớp khác trùng khung giờ này.');
            }
        }
    }

    /**
     * Membership đang active VÀ còn hạn (start_date <= hôm nay <= end_date) —
     * cố tình KHÔNG dùng Member::currentMembership() vì hàm đó không tự kiểm
     * tra end_date (chỉ dựa status=active, có thể đã hết hạn nhưng chưa có
     * job nào chuyển sang expired). Khóa dòng Membership khi là buổi PT vì
     * bước sau sẽ decrement remaining_pt_sessions.
     */
    private function findActiveValidMembership(Member $member, bool $lockForPtDecrement): ?Membership
    {
        $today = now()->toDateString();

        $query = Membership::query()
            ->where('member_id', $member->id)
            ->where('status', Membership::STATUS_ACTIVE)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->latest('end_date');

        if ($lockForPtDecrement) {
            $query->lockForUpdate();
        }

        return $query->first();
    }
}
