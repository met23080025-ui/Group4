<?php

namespace App\Http\Controllers;

use App\Exceptions\CrossTenantOperationException;
use App\Models\ClassBooking;
use App\Models\Schedule;
use App\Services\ClassBookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

class ClassBookingController extends Controller
{
    public function __construct(private readonly ClassBookingService $classBookingService) {}

    /**
     * Member: xem lớp sắp diễn ra của gym mình (chưa diễn ra, chưa bị huỷ).
     */
    public function index(): View
    {
        $this->authorize('viewAny', Schedule::class);

        $schedules = Schedule::query()
            ->where('status', Schedule::STATUS_SCHEDULED)
            ->where('class_date', '>=', now()->toDateString())
            ->withCount(['classBookings as booked_count' => fn ($q) => $q->where('status', ClassBooking::STATUS_BOOKED)])
            ->with('trainer.user')
            ->orderBy('class_date')->orderBy('start_time')
            ->paginate(15);

        $member = auth()->user()->member;

        $myBookedScheduleIds = $member
            ? $member->classBookings()->where('status', ClassBooking::STATUS_BOOKED)->pluck('schedule_id')->all()
            : [];

        return view('member.schedules.index', [
            'schedules' => $schedules,
            'myBookedScheduleIds' => $myBookedScheduleIds,
        ]);
    }

    public function store(Schedule $schedule): RedirectResponse
    {
        $this->authorize('create', ClassBooking::class);

        $member = auth()->user()->member;
        abort_if(! $member, 403, 'Tài khoản chưa có hồ sơ hội viên.');

        try {
            $this->classBookingService->book($member, $schedule);
        } catch (InvalidArgumentException|CrossTenantOperationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('member.schedules.index')->with('success', "Đã đặt lớp {$schedule->title}.");
    }

    /**
     * Member: danh sách booking của chính mình (để huỷ).
     */
    public function mine(): View
    {
        $member = auth()->user()->member;

        $bookings = $member
            ? $member->classBookings()->with('schedule.trainer.user')->orderByDesc('created_at')->paginate(15)
            : ClassBooking::query()->whereRaw('1 = 0')->paginate(15);

        return view('member.bookings.index', ['bookings' => $bookings]);
    }

    public function destroy(ClassBooking $booking): RedirectResponse
    {
        $this->authorize('delete', $booking);

        try {
            $this->classBookingService->cancel($booking);
        } catch (InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('member.bookings.index')->with('success', 'Đã huỷ đặt chỗ.');
    }
}
