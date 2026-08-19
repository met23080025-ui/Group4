<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\Member;
use App\Models\Schedule;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainerDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_trainer_dashboard_shows_today_upcoming_assigned_members_and_sessions_taught(): void
    {
        $gym = Gym::factory()->create(['code' => 'FZ']);
        $trainerUser = User::factory()->create(['gym_id' => $gym->id, 'role' => User::ROLE_TRAINER]);
        $trainer = Trainer::create(['gym_id' => $gym->id, 'user_id' => $trainerUser->id, 'is_active' => true]);

        $memberUser = User::factory()->create(['gym_id' => $gym->id, 'role' => User::ROLE_MEMBER]);
        Member::create([
            'gym_id' => $gym->id, 'user_id' => $memberUser->id, 'trainer_id' => $trainer->id,
            'member_code' => 'FZ-0001', 'status' => Member::STATUS_ACTIVE,
        ]);

        // Buổi hôm nay.
        Schedule::factory()->create([
            'gym_id' => $gym->id, 'trainer_id' => $trainer->id,
            'class_date' => now()->toDateString(), 'start_time' => '09:00', 'end_time' => '10:00',
        ]);
        // Buổi sắp tới (ngày mai).
        Schedule::factory()->create([
            'gym_id' => $gym->id, 'trainer_id' => $trainer->id,
            'class_date' => now()->addDay()->toDateString(), 'start_time' => '09:00', 'end_time' => '10:00',
        ]);
        // Buổi đã qua (đã dạy), không bị huỷ.
        Schedule::factory()->create([
            'gym_id' => $gym->id, 'trainer_id' => $trainer->id,
            'class_date' => now()->subDays(3)->toDateString(), 'start_time' => '09:00', 'end_time' => '10:00',
        ]);
        // Buổi đã qua nhưng bị huỷ -> KHÔNG tính vào "đã dạy".
        Schedule::factory()->create([
            'gym_id' => $gym->id, 'trainer_id' => $trainer->id,
            'class_date' => now()->subDays(2)->toDateString(), 'start_time' => '09:00', 'end_time' => '10:00',
            'status' => Schedule::STATUS_CANCELLED,
        ]);

        $response = $this->actingAs($trainerUser)->get(route('trainer.dashboard'));

        $response->assertOk();
        $response->assertViewHas('todaySchedules', fn ($schedules) => $schedules->count() === 1);
        $response->assertViewHas('upcomingSchedules', fn ($schedules) => $schedules->count() === 1);
        $response->assertViewHas('assignedMembers', fn ($members) => $members->count() === 1);
        $response->assertViewHas('sessionsTaughtCount', 1);
    }

    // Trainer chưa có hồ sơ (Trainer null) -> không crash, fallback placeholder an toàn.
    public function test_trainer_dashboard_is_safe_when_trainer_profile_missing(): void
    {
        $gym = Gym::factory()->create(['code' => 'FZ']);
        $trainerUser = User::factory()->create(['gym_id' => $gym->id, 'role' => User::ROLE_TRAINER]);

        $this->actingAs($trainerUser)->get(route('trainer.dashboard'))->assertOk();
    }
}
