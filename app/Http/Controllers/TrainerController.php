<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\View\View;

/**
 * Dashboard tự phục vụ của Trainer (Khối 6) — thay placeholder cũ ở
 * /trainer/dashboard. Không đặt trong namespace Gym\ vì đây là action tự
 * phục vụ, giống PaymentController::mine/MemberQrController.
 */
class TrainerController extends Controller
{
    public function dashboard(): View
    {
        $trainer = auth()->user()->trainer;

        if (! $trainer) {
            return view('placeholders.trainer');
        }

        $today = now()->toDateString();

        $todaySchedules = Schedule::query()
            ->where('trainer_id', $trainer->id)
            ->whereDate('class_date', $today)
            ->where('status', '!=', Schedule::STATUS_CANCELLED)
            ->orderBy('start_time')
            ->get();

        $upcomingSchedules = Schedule::query()
            ->where('trainer_id', $trainer->id)
            ->whereDate('class_date', '>', $today)
            ->where('status', Schedule::STATUS_SCHEDULED)
            ->orderBy('class_date')->orderBy('start_time')
            ->limit(5)
            ->get();

        // "Đã dạy" = buổi có class_date đã qua và không bị huỷ. Cố tình KHÔNG
        // dựa vào status=completed: không có job/thao tác nào tự chuyển
        // Schedule sang completed (cùng lý do đã ghi ở
        // ClassBookingService::findActiveValidMembership — không có job tự
        // cập nhật trạng thái theo thời gian).
        $sessionsTaughtCount = Schedule::query()
            ->where('trainer_id', $trainer->id)
            ->whereDate('class_date', '<', $today)
            ->where('status', '!=', Schedule::STATUS_CANCELLED)
            ->count();

        $assignedMembers = $trainer->assignedMembers()->with('user')->orderBy('member_code')->get();

        return view('trainer.dashboard', [
            'trainer' => $trainer,
            'todaySchedules' => $todaySchedules,
            'upcomingSchedules' => $upcomingSchedules,
            'sessionsTaughtCount' => $sessionsTaughtCount,
            'assignedMembers' => $assignedMembers,
        ]);
    }
}
