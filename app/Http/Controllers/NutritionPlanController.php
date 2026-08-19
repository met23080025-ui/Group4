<?php

namespace App\Http\Controllers;

use App\Http\Requests\NutritionPlan\StoreNutritionPlanItemRequest;
use App\Http\Requests\NutritionPlan\StoreNutritionPlanRequest;
use App\Models\Member;
use App\Models\NutritionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Cùng cấu trúc với WorkoutPlanController — xem ghi chú ở đó.
 */
class NutritionPlanController extends Controller
{
    public function index(Member $member): View
    {
        $this->authorize('viewAny', [NutritionPlan::class, $member]);

        $plans = $member->nutritionPlans()->with(['trainer.user', 'items'])->orderByDesc('created_at')->get();

        return view('members.nutrition-plans.index', ['member' => $member, 'plans' => $plans]);
    }

    public function store(StoreNutritionPlanRequest $request, Member $member): RedirectResponse
    {
        $trainer = $request->user()->trainer;

        NutritionPlan::create($request->validated() + [
            'gym_id' => $member->gym_id,
            'member_id' => $member->id,
            'trainer_id' => $trainer?->id,
            'is_active' => true,
        ]);

        return redirect()->route('members.nutrition-plans.index', $member)->with('success', 'Đã tạo kế hoạch dinh dưỡng.');
    }

    public function storeItem(StoreNutritionPlanItemRequest $request, NutritionPlan $nutritionPlan): RedirectResponse
    {
        $nutritionPlan->items()->create($request->validated());

        return redirect()
            ->route('members.nutrition-plans.index', $nutritionPlan->member_id)
            ->with('success', 'Đã thêm bữa ăn vào kế hoạch.');
    }
}
