<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkoutPlan\StoreWorkoutPlanItemRequest;
use App\Http\Requests\WorkoutPlan\StoreWorkoutPlanRequest;
use App\Models\Member;
use App\Models\WorkoutPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Khung CRUD tối thiểu (Khối 6, mục 16) — tạo plan + thêm item, đủ khớp
 * schema/diagram. Không có update/destroy plan/item ở khối này (COULD nếu
 * còn thời gian). Dùng chung cho Owner/Staff/Trainer(đã phân công)/Member
 * (chỉ xem) — WorkoutPlanPolicy phân định quyền.
 */
class WorkoutPlanController extends Controller
{
    public function index(Member $member): View
    {
        $this->authorize('viewAny', [WorkoutPlan::class, $member]);

        $plans = $member->workoutPlans()->with(['trainer.user', 'items'])->orderByDesc('created_at')->get();

        return view('members.workout-plans.index', ['member' => $member, 'plans' => $plans]);
    }

    public function store(StoreWorkoutPlanRequest $request, Member $member): RedirectResponse
    {
        $trainer = $request->user()->trainer;

        WorkoutPlan::create($request->validated() + [
            'gym_id' => $member->gym_id,
            'member_id' => $member->id,
            'trainer_id' => $trainer?->id,
            'is_active' => true,
        ]);

        return redirect()->route('members.workout-plans.index', $member)->with('success', 'Đã tạo kế hoạch tập.');
    }

    public function storeItem(StoreWorkoutPlanItemRequest $request, WorkoutPlan $workoutPlan): RedirectResponse
    {
        $workoutPlan->items()->create($request->validated());

        return redirect()
            ->route('members.workout-plans.index', $workoutPlan->member_id)
            ->with('success', 'Đã thêm bài tập vào kế hoạch.');
    }
}
