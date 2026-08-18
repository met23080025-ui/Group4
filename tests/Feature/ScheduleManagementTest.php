<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\Schedule;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    private Gym $gymA;

    private Gym $gymB;

    private User $ownerA;

    private User $memberA;

    private Trainer $trainerA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gymA = Gym::factory()->create(['code' => 'FZ']);
        $this->gymB = Gym::factory()->create(['code' => 'PH']);

        $this->ownerA = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_GYM_OWNER]);
        $this->memberA = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_MEMBER]);

        $this->trainerA = Trainer::factory()->create(['gym_id' => $this->gymA->id]);
    }

    public function test_owner_can_create_a_class_schedule(): void
    {
        $response = $this->actingAs($this->ownerA)->post('/gym/schedules', [
            'trainer_id' => $this->trainerA->id,
            'title' => 'Yoga buổi sáng',
            'class_date' => now()->addDay()->toDateString(),
            'start_time' => '07:00',
            'end_time' => '08:00',
            'capacity' => 20,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('schedules', [
            'gym_id' => $this->gymA->id,
            'title' => 'Yoga buổi sáng',
            'capacity' => 20,
            'is_pt_session' => 0,
        ]);
    }

    public function test_owner_can_create_a_pt_session_schedule(): void
    {
        $response = $this->actingAs($this->ownerA)->post('/gym/schedules', [
            'trainer_id' => $this->trainerA->id,
            'title' => 'Buổi PT với Trainer A',
            'class_date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:00',
            'capacity' => 1,
            'is_pt_session' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('schedules', [
            'gym_id' => $this->gymA->id,
            'title' => 'Buổi PT với Trainer A',
            'is_pt_session' => 1,
        ]);
    }

    public function test_member_cannot_create_schedule(): void
    {
        $this->actingAs($this->memberA)->post('/gym/schedules', [
            'title' => 'Yoga',
            'class_date' => now()->addDay()->toDateString(),
            'start_time' => '07:00',
            'end_time' => '08:00',
            'capacity' => 20,
        ])->assertForbidden();

        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_owner_can_update_and_delete_own_schedule(): void
    {
        $schedule = Schedule::factory()->create(['gym_id' => $this->gymA->id, 'trainer_id' => $this->trainerA->id]);

        $this->actingAs($this->ownerA)->put("/gym/schedules/{$schedule->id}", [
            'trainer_id' => $this->trainerA->id,
            'title' => 'Đã đổi tên lớp',
            'class_date' => $schedule->class_date->toDateString(),
            'start_time' => $schedule->start_time->format('H:i'),
            'end_time' => $schedule->end_time->format('H:i'),
            'capacity' => $schedule->capacity,
        ])->assertRedirect();

        $this->assertDatabaseHas('schedules', ['id' => $schedule->id, 'title' => 'Đã đổi tên lớp']);

        $this->actingAs($this->ownerA)->delete("/gym/schedules/{$schedule->id}")->assertRedirect();
        $this->assertSoftDeleted('schedules', ['id' => $schedule->id]);
    }

    public function test_capacity_cannot_be_reduced_below_current_booked_count(): void
    {
        $schedule = Schedule::factory()->create([
            'gym_id' => $this->gymA->id,
            'trainer_id' => $this->trainerA->id,
            'capacity' => 5,
        ]);
        $schedule->classBookings()->create([
            'gym_id' => $this->gymA->id,
            'member_id' => $this->makeMemberInGymA()->id,
            'status' => 'booked',
            'booked_at' => now(),
        ]);
        $schedule->classBookings()->create([
            'gym_id' => $this->gymA->id,
            'member_id' => $this->makeMemberInGymA()->id,
            'status' => 'booked',
            'booked_at' => now(),
        ]);

        $response = $this->actingAs($this->ownerA)->put("/gym/schedules/{$schedule->id}", [
            'trainer_id' => $this->trainerA->id,
            'title' => $schedule->title,
            'class_date' => $schedule->class_date->toDateString(),
            'start_time' => $schedule->start_time->format('H:i'),
            'end_time' => $schedule->end_time->format('H:i'),
            'capacity' => 1,
        ]);

        $response->assertSessionHasErrors('capacity');
        $this->assertDatabaseHas('schedules', ['id' => $schedule->id, 'capacity' => 5]);
    }

    // Chỉ cần một hàng members hợp lệ để gán member_id cho booking test dữ liệu
    // thô ở trên — không đụng tới ClassBookingService/Membership ở test này.
    private function makeMemberInGymA(): \App\Models\Member
    {
        $user = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_MEMBER]);

        return \App\Models\Member::create([
            'gym_id' => $this->gymA->id,
            'user_id' => $user->id,
            'member_code' => 'FZ-'.fake()->unique()->numberBetween(1000, 9999),
            'status' => 'active',
        ]);
    }

    public function test_cross_tenant_schedule_access_returns_404(): void
    {
        $scheduleB = Schedule::factory()->create(['gym_id' => $this->gymB->id]);

        $this->actingAs($this->ownerA)->get("/gym/schedules/{$scheduleB->id}")->assertNotFound();
        $this->actingAs($this->ownerA)->get("/gym/schedules/{$scheduleB->id}/edit")->assertNotFound();
        $this->actingAs($this->ownerA)->put("/gym/schedules/{$scheduleB->id}", [
            'title' => 'Hacked',
            'class_date' => now()->addDay()->toDateString(),
            'start_time' => '07:00',
            'end_time' => '08:00',
            'capacity' => 10,
        ])->assertNotFound();
        $this->actingAs($this->ownerA)->delete("/gym/schedules/{$scheduleB->id}")->assertNotFound();

        $this->assertDatabaseHas('schedules', ['id' => $scheduleB->id, 'title' => $scheduleB->title]);
    }
}
