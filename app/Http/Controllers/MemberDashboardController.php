<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\View\View;

/**
 * Không đặt trong namespace Gym\ — đây là action tự phục vụ của Member
 * (giống PaymentController::mine/MemberQrController), không phải thao tác
 * quản lý của Staff/Owner.
 */
class MemberDashboardController extends Controller
{
    public function index(DashboardService $dashboardService): View
    {
        $member = auth()->user()->member;

        return view('member.dashboard', [
            'member' => $member,
            'stats' => $member ? $dashboardService->memberOverview($member) : null,
        ]);
    }
}
