<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\Member;
use App\Models\NutritionPlan;
use App\Models\Trainer;
use App\Models\User;
use App\Models\WorkoutPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Khung CRUD tối thiểu (mục 16) — chỉ test đường đi chính (tạo plan + thêm
 * item) và 1 rule chặn cross-trainer trên item, đã có TrainerAssignmentTest
 * bao phủ đầy đủ phần phân quyền viewAny/create theo member.
 */
class WorkoutNutritionPlanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Trainer::factory()/Member::factory() mặc định tạo User riêng KHÔNG
     * đồng bộ gym_id — nếu actingAs() bằng user đó, global scope
     * BelongsToGym lọc nhầm sang Gym khác và trả 404 sai. Tạo tường minh.
     */
    private function makeTrainer(Gym $gym): Trainer
    {
        $user = User::factory()->create(['gym_id' => $gym->id, 'role' => User::ROLE_TRAINER]);

        return Trainer::create(['gym_id' => $gym->id, 'user_id' => $user->id, 'is_active' => true]);
    }

    private function makeMember(Gym $gym, ?int $trainerId = null): Member
    {
        $user = User::factory()->create(['gym_id' => $gym->id, 'role' => User::ROLE_MEMBER]);

        return Member::create([
            'gym_id' => $gym->id,
            'user_id' => $user->id,
            'trainer_id' => $trainerId,
            'member_code' => 'MB-'.$user->id,
            'status' => Member::STATUS_ACTIVE,
        ]);
    }

    public function test_trainer_creates_workout_plan_with_item_for_assigned_member(): void
    {
        $gym = Gym::factory()->create(['code' => 'FZ']);
        $trainer = $this->makeTrainer($gym);
        $member = $this->makeMember($gym, $trainer->id);

        $this->actingAs($trainer->user)
            ->post(route('members.workout-plans.store', $member), ['title' => 'Tăng cơ 8 tuần'])
            ->assertRedirect();

        $plan = WorkoutPlan::where('member_id', $member->id)->firstOrFail();
        $this->assertSame($trainer->id, $plan->trainer_id);

        $this->actingAs($trainer->user)
            ->post(route('workout-plans.items.store', $plan), [
                'exercise_name' => 'Squat', 'sets' => 4, 'reps' => 10,
            ])
            ->assertRedirect(route('members.workout-plans.index', $member));

        $this->assertDatabaseHas('workout_plan_items', [
            'workout_plan_id' => $plan->id,
            'exercise_name' => 'Squat',
            'sets' => 4,
            'reps' => 10,
        ]);
    }

    public function test_trainer_creates_nutrition_plan_with_item_for_assigned_member(): void
    {
        $gym = Gym::factory()->create(['code' => 'FZ']);
        $trainer = $this->makeTrainer($gym);
        $member = $this->makeMember($gym, $trainer->id);

        $this->actingAs($trainer->user)
            ->post(route('members.nutrition-plans.store', $member), ['title' => 'Ăn kiêng low-carb'])
            ->assertRedirect();

        $plan = NutritionPlan::where('member_id', $member->id)->firstOrFail();

        $this->actingAs($trainer->user)
            ->post(route('nutrition-plans.items.store', $plan), [
                'meal_name' => 'Bữa sáng', 'food' => 'Ức gà + khoai lang', 'calories' => 450,
            ])
            ->assertRedirect(route('members.nutrition-plans.index', $member));

        $this->assertDatabaseHas('nutrition_plan_items', [
            'nutrition_plan_id' => $plan->id,
            'meal_name' => 'Bữa sáng',
            'food' => 'Ức gà + khoai lang',
        ]);
    }

    // Rule: trainer khác (chưa được phân công member này) không thêm được item.
    public function test_unassigned_trainer_cannot_add_item_to_plan(): void
    {
        $gym = Gym::factory()->create(['code' => 'FZ']);
        $trainerOwner = $this->makeTrainer($gym);
        $otherTrainer = $this->makeTrainer($gym);
        $member = $this->makeMember($gym, $trainerOwner->id);

        $plan = WorkoutPlan::create([
            'gym_id' => $gym->id,
            'member_id' => $member->id,
            'trainer_id' => $trainerOwner->id,
            'title' => 'Kế hoạch riêng',
            'is_active' => true,
        ]);

        $this->actingAs($otherTrainer->user)
            ->post(route('workout-plans.items.store', $plan), ['exercise_name' => 'Deadlift'])
            ->assertForbidden();

        $this->assertDatabaseCount('workout_plan_items', 0);
    }

    // Member chỉ xem được, không tự tạo plan cho chính mình.
    public function test_member_cannot_create_own_workout_plan(): void
    {
        $gym = Gym::factory()->create(['code' => 'FZ']);
        $member = $this->makeMember($gym);

        $this->actingAs($member->user)
            ->post(route('members.workout-plans.store', $member), ['title' => 'Tự lập kế hoạch'])
            ->assertForbidden();

        $this->assertDatabaseCount('workout_plans', 0);
    }
}
