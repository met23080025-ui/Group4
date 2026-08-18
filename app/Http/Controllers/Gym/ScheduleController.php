<?php

namespace App\Http\Controllers\Gym;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\StoreScheduleRequest;
use App\Http\Requests\Schedule\UpdateScheduleRequest;
use App\Models\Schedule;
use App\Models\Trainer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Schedule::class);

        $query = Schedule::query()->with('trainer.user')->orderBy('class_date')->orderBy('start_time');

        if ($request->filled('class_date')) {
            $query->whereDate('class_date', $request->date('class_date'));
        }

        $schedules = $query->paginate(15)->withQueryString();

        return view('gym.schedules.index', [
            'schedules' => $schedules,
            'filters' => $request->only(['class_date']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Schedule::class);

        return view('gym.schedules.create', ['trainers' => $this->activeTrainers()]);
    }

    public function store(StoreScheduleRequest $request): RedirectResponse
    {
        $schedule = Schedule::create(
            $request->validated() + ['status' => Schedule::STATUS_SCHEDULED, 'is_pt_session' => $request->boolean('is_pt_session')]
        );

        return redirect()->route('gym.schedules.show', $schedule)->with('success', "Đã tạo lớp {$schedule->title}.");
    }

    public function show(Schedule $schedule): View
    {
        $this->authorize('view', $schedule);

        $schedule->load(['trainer.user', 'classBookings' => function ($query) {
            $query->where('status', 'booked')->with('member.user');
        }]);

        return view('gym.schedules.show', ['schedule' => $schedule]);
    }

    public function edit(Schedule $schedule): View
    {
        $this->authorize('update', $schedule);

        return view('gym.schedules.edit', ['schedule' => $schedule, 'trainers' => $this->activeTrainers()]);
    }

    public function update(UpdateScheduleRequest $request, Schedule $schedule): RedirectResponse
    {
        $schedule->update(
            $request->validated() + ['is_pt_session' => $request->boolean('is_pt_session')]
        );

        return redirect()->route('gym.schedules.show', $schedule)->with('success', 'Đã cập nhật lớp.');
    }

    public function destroy(Schedule $schedule): RedirectResponse
    {
        $this->authorize('delete', $schedule);

        $schedule->delete();

        return redirect()->route('gym.schedules.index')->with('success', "Đã xóa lớp {$schedule->title}.");
    }

    private function activeTrainers()
    {
        return Trainer::query()->with('user')->where('is_active', true)->orderBy('id')->get();
    }
}
