<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ClassBooking;
use App\Models\Equipment;
use App\Models\Gym;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\Schedule;
use App\Models\User;

/**
 * Số liệu dashboard theo role (mục 20, Ngày 3). Mọi query ở đây (trừ
 * platformOverview) đều gọi trên các model dùng BelongsToGym — global scope
 * tự lọc theo gym_id của user đang đăng nhập, KHÔNG cần filter gym_id thủ
 * công. Đây chính là cơ chế đảm bảo "Owner/Staff chỉ thấy số liệu Gym mình".
 */
class DashboardService
{
    /**
     * whereDate() (không phải where() so chuỗi trực tiếp) cho MỌI so sánh
     * trên cột cast 'date' (class_date, end_date...) — SQLite (driver test)
     * lưu "Y-m-d 00:00:00" đầy đủ nên so chuỗi trực tiếp có thể sai lệch ở
     * biên hôm nay, đã từng gây bug thật ở Khối 5/7 (Ngày 2).
     */
    public function ownerOverview(): array
    {
        $today = now()->toDateString();
        $expiringLimit = now()->addDays(7)->toDateString();

        return [
            'total_members' => Member::count(),
            'active_members' => Member::where('status', Member::STATUS_ACTIVE)->count(),
            'members_without_valid_membership' => Member::query()
                ->whereDoesntHave('memberships', fn ($q) => $q->where('status', Membership::STATUS_ACTIVE)->whereDate('end_date', '>=', $today))
                ->count(),
            'revenue_this_month' => (float) Invoice::query()
                ->whereBetween('issued_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('total'),
            'new_memberships_this_month' => Membership::query()
                ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'upcoming_schedules' => Schedule::query()
                ->whereDate('class_date', '>=', $today)
                ->where('status', Schedule::STATUS_SCHEDULED)
                ->orderBy('class_date')->orderBy('start_time')
                ->limit(5)->get(),
            'checkins_today' => Attendance::query()->whereDate('check_in_date', $today)->count(),
            'pending_payments' => Payment::where('status', Payment::STATUS_PENDING)->count(),
            'expiring_memberships' => Membership::query()
                ->where('status', Membership::STATUS_ACTIVE)
                ->whereDate('end_date', '>=', $today)
                ->whereDate('end_date', '<=', $expiringLimit)
                ->count(),
            'equipment_due_for_maintenance' => $this->equipmentDueForMaintenanceCount(),
        ];
    }

    public function staffOverview(): array
    {
        $today = now()->toDateString();
        $expiringLimit = now()->addDays(7)->toDateString();

        return [
            'checkins_today' => Attendance::query()->whereDate('check_in_date', $today)->count(),
            'pending_payments' => Payment::where('status', Payment::STATUS_PENDING)->count(),
            'expiring_memberships' => Membership::query()
                ->where('status', Membership::STATUS_ACTIVE)
                ->whereDate('end_date', '>=', $today)
                ->whereDate('end_date', '<=', $expiringLimit)
                ->count(),
            'upcoming_schedules' => Schedule::query()
                ->whereDate('class_date', '>=', $today)
                ->where('status', Schedule::STATUS_SCHEDULED)
                ->orderBy('class_date')->orderBy('start_time')
                ->limit(5)->get(),
            'equipment_due_for_maintenance' => $this->equipmentDueForMaintenanceCount(),
        ];
    }

    /**
     * "Sắp đến lịch bảo trì" (Khối 4, Ngày 3) = next_maintenance_at trong
     * vòng $daysAhead ngày tới, TÍNH CẢ thiết bị đã quá hạn (next_maintenance_at
     * <= hôm nay) — quá hạn càng cần hiển thị cảnh báo, không phải lọc bỏ.
     */
    public function equipmentDueForMaintenanceCount(int $daysAhead = 14): int
    {
        return Equipment::query()
            ->whereNotNull('next_maintenance_at')
            ->whereDate('next_maintenance_at', '<=', now()->addDays($daysAhead)->toDateString())
            ->count();
    }

    // Gym/User KHÔNG dùng BelongsToGym (đây chính là root tenant + quyết định
    // kiến trúc Khối 3 Ngày 1) nên platform_admin tự nhiên thấy TẤT CẢ, không
    // cần bypass thủ công.
    public function platformOverview(): array
    {
        return [
            'total_gyms' => Gym::count(),
            'active_gyms' => Gym::where('is_active', true)->count(),
            'total_users' => User::count(),
            'users_by_role' => User::query()->selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role'),
        ];
    }

    public function memberOverview(Member $member): array
    {
        $today = now()->toDateString();

        $currentMembership = Membership::query()
            ->where('member_id', $member->id)
            ->where('status', Membership::STATUS_ACTIVE)
            ->whereDate('end_date', '>=', $today)
            ->with('package')
            ->latest('end_date')
            ->first();

        $measurements = $member->bodyMeasurements()->orderByDesc('measured_at')->orderByDesc('id')->limit(2)->get();

        return [
            'current_membership' => $currentMembership,
            'days_remaining' => $currentMembership
                ? now()->startOfDay()->diffInDays($currentMembership->end_date)
                : null,
            'upcoming_bookings' => $member->classBookings()
                ->where('status', ClassBooking::STATUS_BOOKED)
                ->whereHas('schedule', fn ($q) => $q->whereDate('class_date', '>=', $today))
                ->with('schedule')
                ->get(),
            'checkins_this_month' => $member->attendances()
                ->whereBetween('check_in_date', [now()->startOfMonth(), now()->endOfMonth()])
                ->count(),
            'checked_in_today' => $member->attendances()->whereDate('check_in_date', $today)->exists(),
            'latest_measurement' => $measurements->first(),
            'previous_measurement' => $measurements->skip(1)->first(),
            'loyalty_balance' => $member->loyaltyPointTransactions()->latest('id')->value('balance_after') ?? 0,
        ];
    }
}
