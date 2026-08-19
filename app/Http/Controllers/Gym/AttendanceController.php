<?php

namespace App\Http\Controllers\Gym;

use App\Exceptions\CrossTenantOperationException;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    /**
     * Staff/Owner: form nhập/quét token QR + nhật ký check-in trong ngày của Gym.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Attendance::class);

        $today = Attendance::query()
            ->where('check_in_date', now()->toDateString())
            ->with('member.user')
            ->orderByDesc('check_in_time')
            ->get();

        return view('gym.checkin.index', ['todayCheckIns' => $today]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Attendance::class);

        $request->validate(['token' => ['required', 'string']]);

        try {
            $attendance = $this->attendanceService->checkIn($request->string('token')->value(), $request->user());
        } catch (InvalidArgumentException|CrossTenantOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('gym.checkin.index')
            ->with('success', "Đã check-in cho hội viên {$attendance->member->member_code}. +10 điểm loyalty.");
    }
}
