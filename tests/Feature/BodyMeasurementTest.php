<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\Member;
use App\Models\Trainer;
use App\Models\User;
use App\Services\BodyMeasurementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BodyMeasurementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Trainer::factory()/Member::factory() mặc định tạo User riêng KHÔNG
     * đồng bộ gym_id (User::factory() không tự set gym_id) — nếu actingAs()
     * bằng user đó, global scope BelongsToGym sẽ lọc nhầm sang Gym khác và
     * trả 404 sai. Tạo User tường minh trước rồi gán gym_id khớp nhau, giống
     * pattern makeMember() đã dùng ở ClassBookingTest.
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

    // Rule: BMI = cân nặng(kg) / chiều cao(m)^2, làm tròn 2 chữ số thập phân.
    public function test_bmi_is_calculated_correctly(): void
    {
        $service = app(BodyMeasurementService::class);

        // 70kg / 1.70m^2 = 70 / 2.89 = 24.2214... -> 24.22
        $this->assertSame(24.22, $service->calculateBmi(170, 70));

        // 50kg / 1.60m^2 = 50 / 2.56 = 19.53125 -> 19.53
        $this->assertSame(19.53, $service->calculateBmi(160, 50));

        // 100kg / 1.80m^2 = 100 / 3.24 = 30.8641... -> 30.86
        $this->assertSame(30.86, $service->calculateBmi(180, 100));
    }

    // Rule: record() lưu đúng gym/member và BMI tính sẵn.
    public function test_record_persists_measurement_with_computed_bmi(): void
    {
        $gym = Gym::factory()->create(['code' => 'FZ']);
        $member = $this->makeMember($gym);
        $staff = User::factory()->create(['gym_id' => $gym->id, 'role' => User::ROLE_STAFF]);

        $measurement = app(BodyMeasurementService::class)->record($member, $staff, [
            'height' => 175,
            'weight' => 68,
            'body_fat_percent' => 15.5,
            'muscle_mass' => 55,
        ]);

        $this->assertSame($gym->id, $measurement->gym_id);
        $this->assertSame($member->id, $measurement->member_id);
        $this->assertSame($staff->id, $measurement->recorded_by);
        // 68 / 1.75^2 = 68 / 3.0625 = 22.2040... -> 22.20
        $this->assertEquals(22.20, (float) $measurement->bmi);
    }

    // HTTP: trainer nhập chỉ số cho học viên đã phân công -> lưu đúng, tính đúng BMI.
    public function test_trainer_can_record_body_measurement_via_http_with_correct_bmi(): void
    {
        $gym = Gym::factory()->create(['code' => 'FZ']);
        $trainer = $this->makeTrainer($gym);
        $member = $this->makeMember($gym, $trainer->id);

        $this->actingAs($trainer->user)
            ->post(route('members.measurements.store', $member), [
                'height' => 180,
                'weight' => 90,
            ])
            ->assertRedirect(route('members.measurements.index', $member));

        // 90 / 1.80^2 = 90 / 3.24 = 27.7777... -> 27.78
        $this->assertDatabaseHas('body_measurements', [
            'member_id' => $member->id,
            'height' => 180,
            'weight' => 90,
            'bmi' => 27.78,
        ]);
    }

    // HTTP: member tự nhập chỉ số của chính mình.
    public function test_member_can_record_own_body_measurement_via_http(): void
    {
        $gym = Gym::factory()->create(['code' => 'FZ']);
        $member = $this->makeMember($gym);

        $this->actingAs($member->user)
            ->post(route('members.measurements.store', $member), [
                'height' => 165,
                'weight' => 55,
            ])
            ->assertRedirect(route('members.measurements.index', $member));

        $this->assertDatabaseHas('body_measurements', [
            'member_id' => $member->id,
            'recorded_by' => $member->user->id,
        ]);
    }

    // Validation: height/weight bắt buộc, không cho chia 0 hoặc âm.
    public function test_body_measurement_requires_height_and_weight(): void
    {
        $gym = Gym::factory()->create(['code' => 'FZ']);
        $member = $this->makeMember($gym);

        $this->actingAs($member->user)
            ->post(route('members.measurements.store', $member), [])
            ->assertSessionHasErrors(['height', 'weight']);

        $this->assertDatabaseCount('body_measurements', 0);
    }
}
