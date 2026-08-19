<?php

namespace App\Http\Controllers;

use App\Services\AttendanceService;
use Illuminate\View\View;

/**
 * Member tự xem QR check-in của chính mình. Không đặt trong namespace Gym\
 * vì đây là action tự phục vụ của Member (giống PaymentController::mine),
 * không phải thao tác quản lý của Staff/Owner.
 */
class MemberQrController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    public function show(): View
    {
        $member = auth()->user()->member;

        return view('member.qr.show', [
            'member' => $member,
            'token' => $member ? $this->attendanceService->tokenFor($member) : null,
        ]);
    }
}
