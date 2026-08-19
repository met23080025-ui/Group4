<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\Member;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rule (Khối 6): Trainer chỉ xem/sửa được học viên ĐÃ ĐƯỢC PHÂN CÔNG cho
 * chính mình, trong đúng Gym của mình — cả cross-trainer (cùng Gym, khác PT
 * phụ trách) lẫn cross-tenant (khác Gym) đều phải bị chặn. Dùng trang
 * body-measurements (dùng chung Owner/Staff/Trainer/Member) làm đại diện vì
 * BodyMeasurementPolicy/WorkoutPlanPolicy/NutritionPlanPolicy đều áp cùng
 * 1 quy tắc canAccess()/canManage().
 */
class TrainerAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private Gym $gymA;

    private Gym $gymB;

    private Trainer $trainerA1;

    private Trainer $trainerA2;

    private Trainer $trainerB;

    private Member $assignedMember;

    private Member $unassignedMemberSameGym;

    private Member $memberInOtherGym;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gymA = Gym::factory()->create(['code' => 'FZ']);
        $this->gymB = Gym::factory()->create(['code' => 'PH']);

        $this->trainerA1 = $this->makeTrainer($this->gymA);
        $this->trainerA2 = $this->makeTrainer($this->gymA);
        $this->trainerB = $this->makeTrainer($this->gymB);

        $this->assignedMember = $this->makeMember($this->gymA, $this->trainerA1->id);
        $this->unassignedMemberSameGym = $this->makeMember($this->gymA, $this->trainerA2->id);
        $this->memberInOtherGym = $this->makeMember($this->gymB, $this->trainerB->id);
    }

    /**
     * Trainer::factory()/Member::factory() mặc định tạo User riêng KHÔNG
     * đồng bộ gym_id với gym_id của chính Trainer/Member (User::factory()
     * không tự set gym_id) — nếu actingAs() bằng user đó, global scope
     * BelongsToGym sẽ lọc nhầm sang Gym khác (null) và trả 404 sai. Tạo User
     * tường minh trước rồi gán gym_id khớp nhau, giống pattern makeMember()
     * đã dùng ở ClassBookingTest.
     */
    private function makeTrainer(Gym $gym): Trainer
    {
        $user = User::factory()->create(['gym_id' => $gym->id, 'role' => User::ROLE_TRAINER]);

        return Trainer::create([
            'gym_id' => $gym->id,
            'user_id' => $user->id,
            'is_active' => true,
        ]);
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

    // Rule: trainer xem được học viên đã được phân công cho mình.
    public function test_trainer_can_view_assigned_member(): void
    {
        $this->actingAs($this->trainerA1->user)
            ->get(route('members.measurements.index', $this->assignedMember))
            ->assertOk();

        $this->actingAs($this->trainerA1->user)
            ->get(route('members.workout-plans.index', $this->assignedMember))
            ->assertOk();

        $this->actingAs($this->trainerA1->user)
            ->get(route('members.nutrition-plans.index', $this->assignedMember))
            ->assertOk();
    }

    // Rule: cross-trainer (cùng Gym, khác PT phụ trách) -> 403.
    public function test_trainer_cannot_view_unassigned_member_in_same_gym(): void
    {
        $this->actingAs($this->trainerA1->user)
            ->get(route('members.measurements.index', $this->unassignedMemberSameGym))
            ->assertForbidden();

        $this->actingAs($this->trainerA1->user)
            ->get(route('members.workout-plans.index', $this->unassignedMemberSameGym))
            ->assertForbidden();

        $this->actingAs($this->trainerA1->user)
            ->get(route('members.nutrition-plans.index', $this->unassignedMemberSameGym))
            ->assertForbidden();
    }

    // Rule: cross-tenant (khác Gym) -> 404 (route model binding, global scope BelongsToGym).
    public function test_trainer_cannot_view_member_in_another_gym(): void
    {
        $this->actingAs($this->trainerA1->user)
            ->get(route('members.measurements.index', $this->memberInOtherGym))
            ->assertNotFound();

        $this->actingAs($this->trainerA1->user)
            ->get(route('members.workout-plans.index', $this->memberInOtherGym))
            ->assertNotFound();
    }

    // Rule: trainer thêm được body measurement / workout plan cho học viên đã phân công.
    public function test_trainer_can_record_data_for_assigned_member(): void
    {
        $this->actingAs($this->trainerA1->user)
            ->post(route('members.measurements.store', $this->assignedMember), [
                'height' => 175, 'weight' => 70,
            ])
            ->assertRedirect(route('members.measurements.index', $this->assignedMember));

        $this->assertDatabaseHas('body_measurements', [
            'member_id' => $this->assignedMember->id,
            'gym_id' => $this->gymA->id,
        ]);

        $this->actingAs($this->trainerA1->user)
            ->post(route('members.workout-plans.store', $this->assignedMember), ['title' => 'Kế hoạch tuần 1'])
            ->assertRedirect(route('members.workout-plans.index', $this->assignedMember));

        $this->assertDatabaseHas('workout_plans', [
            'member_id' => $this->assignedMember->id,
            'trainer_id' => $this->trainerA1->id,
        ]);
    }

    // Rule: trainer KHÔNG thêm được dữ liệu cho học viên chưa phân công cho mình (cross-trainer).
    public function test_trainer_cannot_record_data_for_unassigned_member_in_same_gym(): void
    {
        $this->actingAs($this->trainerA1->user)
            ->post(route('members.measurements.store', $this->unassignedMemberSameGym), [
                'height' => 175, 'weight' => 70,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('body_measurements', 0);

        $this->actingAs($this->trainerA1->user)
            ->post(route('members.workout-plans.store', $this->unassignedMemberSameGym), ['title' => 'Kế hoạch lén lút'])
            ->assertForbidden();

        $this->assertDatabaseCount('workout_plans', 0);
    }

    // Rule: trainer KHÔNG thêm được dữ liệu cho học viên Gym khác (cross-tenant) -> 404.
    public function test_trainer_cannot_record_data_for_member_in_another_gym(): void
    {
        $this->actingAs($this->trainerA1->user)
            ->post(route('members.measurements.store', $this->memberInOtherGym), [
                'height' => 175, 'weight' => 70,
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('body_measurements', 0);
    }

    // Sanity: Owner/Staff không bị giới hạn theo trainer_id — xem được mọi member cùng Gym.
    public function test_owner_and_staff_can_view_any_member_measurements_in_own_gym(): void
    {
        $owner = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_GYM_OWNER]);
        $staff = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_STAFF]);

        $this->actingAs($owner)
            ->get(route('members.measurements.index', $this->unassignedMemberSameGym))
            ->assertOk();

        $this->actingAs($staff)
            ->get(route('members.measurements.index', $this->unassignedMemberSameGym))
            ->assertOk();
    }

    // Sanity: Owner/Staff Gym A vẫn bị chặn cross-tenant (404) như mọi resource khác.
    public function test_owner_cannot_view_member_in_another_gym(): void
    {
        $owner = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_GYM_OWNER]);

        $this->actingAs($owner)
            ->get(route('members.measurements.index', $this->memberInOtherGym))
            ->assertNotFound();
    }

    // Member chỉ xem được chính mình, không xem được người khác.
    public function test_member_can_view_own_measurements_but_not_others(): void
    {
        $this->actingAs($this->assignedMember->user)
            ->get(route('members.measurements.index', $this->assignedMember))
            ->assertOk();

        $this->actingAs($this->assignedMember->user)
            ->get(route('members.measurements.index', $this->unassignedMemberSameGym))
            ->assertForbidden();
    }

    // Gán PT phụ trách: Owner/Staff gán trainer cùng Gym cho member.
    public function test_owner_can_assign_trainer_to_member(): void
    {
        $owner = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_GYM_OWNER]);

        $this->actingAs($owner)
            ->post(route('gym.members.assign-trainer', $this->unassignedMemberSameGym), [
                'trainer_id' => $this->trainerA1->id,
            ])
            ->assertRedirect(route('gym.members.show', $this->unassignedMemberSameGym));

        $this->assertSame($this->trainerA1->id, $this->unassignedMemberSameGym->fresh()->trainer_id);
    }

    // Gán PT phụ trách: không cho gán trainer thuộc Gym khác (chặn ở tầng validate).
    public function test_cannot_assign_trainer_from_another_gym(): void
    {
        $owner = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_GYM_OWNER]);

        $this->actingAs($owner)
            ->post(route('gym.members.assign-trainer', $this->assignedMember), [
                'trainer_id' => $this->trainerB->id,
            ])
            ->assertSessionHasErrors('trainer_id');

        $this->assertSame($this->trainerA1->id, $this->assignedMember->fresh()->trainer_id);
    }

    // Gỡ PT phụ trách: gửi trainer_id rỗng -> trainer_id thành null.
    public function test_owner_can_unassign_trainer(): void
    {
        $owner = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_GYM_OWNER]);

        $this->actingAs($owner)
            ->post(route('gym.members.assign-trainer', $this->assignedMember), ['trainer_id' => ''])
            ->assertRedirect(route('gym.members.show', $this->assignedMember));

        $this->assertNull($this->assignedMember->fresh()->trainer_id);
    }
}
