<?php

namespace App\Http\Controllers\Gym;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Chỉ Owner (route `role:gym_owner` — xem routes/web.php) — thuần role-gate
 * giống `/gym/dashboard`, không cần Policy riêng vì không có per-instance
 * authorization nào ở đây (Report luôn là của ĐÚNG Gym đang đăng nhập).
 */
class ReportController extends Controller
{
    public function revenue(Request $request, ReportService $reportService): View
    {
        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        /** @var \App\Models\Gym $gym */
        $gym = $request->user()->gym;

        $report = $reportService->revenue($gym, $request->input('from'), $request->input('to'));

        return view('gym.reports.revenue', [
            'report' => $report,
            'filters' => $request->only(['from', 'to']),
        ]);
    }
}
