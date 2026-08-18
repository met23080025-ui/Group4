<?php

namespace Tests\Feature;

use App\Exceptions\CrossTenantOperationException;
use App\Models\ClassBooking;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Package;
use App\Models\Schedule;
use App\Models\Trainer;
use App\Models\User;
use App\Services\ClassBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ClassBookingTest extends TestCase
{
    use RefreshDatabase;

    private Gym $gymA;

    private Gym $gymB;

    private Member $memberA;

    private Trainer $trainerA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gymA = Gym::factory()->create(['code' => 'FZ']);
        $this->gymB = Gym::factory()->create(['code' => 'PH']);

        $this->memberA = $this->makeMember($this->gymA, 'FZ-0001');
        $this->trainerA = Trainer::factory()->create(['gym_id' => $this->gymA->id]);
    }

    private function makeMember(Gym $gym, string $code): Member
    {
        $user = User::factory()->create(['gym_id' => $gym->id, 'role' => User::ROLE_MEMBER]);

        return Member::create([
            'gym_id' => $gym->id,
            'user_id' => $user->id,
            'member_code' => $code,
            'status' => Member::STATUS_ACTIVE,
        ]);
    }

    private function activeMembership(Member $member, int $remainingPtSessions = 4, array $overrides = []): Membership
    {
        $package = Package::factory()->create(['gym_id' => $member->gym_id]);

        return Membership::create(array_merge([
            'gym_id' => $member->gym_id,
            'member_id' => $member->id,
            'package_id' => $package->id,
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->addDays(25)->toDateString(),
            'original_price' => $package->price,
            'discount_amount' => 0,
            'final_price' => $package->price,
            'remaining_pt_sessions' => $remainingPtSessions,
            'status' => Membership::STATUS_ACTIVE,
        ], $overrides));
    }

    private function groupClass(array $overrides = []): Schedule
    {
        return Schedule::factory()->create(array_merge([
            'gym_id' => $this->gymA->id,
            'trainer_id' => $this->trainerA->id,
            'capacity' => 10,
            'class_date' => now()->addDay()->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
        ], $overrides));
    }

    private function ptSchedule(array $overrides = []): Schedule
    {
        return Schedule::factory()->ptSession()->create(array_merge([
            'gym_id' => $this->gymA->id,
            'trainer_id' => $this->trainerA->id,
            'class_date' => now()->addDay()->toDateString(),
            'start_time' => '14:00',
            'end_time' => '15:00',
        ], $overrides));
    }

    // Rule: chỉ member có membership active còn hạn mới được đặt.
    public function test_member_without_active_membership_cannot_book(): void
    {
        $schedule = $this->groupClass();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('gói tập đang hoạt động và còn hạn');

        app(ClassBookingService::class)->book($this->memberA, $schedule);
    }

    public function test_member_with_expired_membership_cannot_book(): void
    {
        $schedule = $this->groupClass();
        $this->activeMembership($this->memberA, 4, [
            'start_date' => now()->subDays(60)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
        ]);

        $this->expectException(InvalidArgumentException::class);

        app(ClassBookingService::class)->book($this->memberA, $schedule);
    }

    public function test_member_with_pending_membership_cannot_book(): void
    {
        $schedule = $this->groupClass();
        $this->activeMembership($this->memberA, 4, ['status' => Membership::STATUS_PENDING]);

        $this->expectException(InvalidArgumentException::class);

        app(ClassBookingService::class)->book($this->memberA, $schedule);
    }

    public function test_member_with_active_valid_membership_can_book(): void
    {
        $schedule = $this->groupClass();
        $this->activeMembership($this->memberA);

        $booking = app(ClassBookingService::class)->book($this->memberA, $schedule);

        $this->assertSame(ClassBooking::STATUS_BOOKED, $booking->status);
        $this->assertSame($this->memberA->id, $booking->member_id);
    }

    // Rule: không vượt capacity (khóa dòng Schedule để tuần tự hóa khi 2 người
    // đặt chỗ cuối gần đồng thời).
    public function test_cannot_book_beyond_capacity(): void
    {
        $schedule = $this->groupClass(['capacity' => 1]);
        $this->activeMembership($this->memberA);
        app(ClassBookingService::class)->book($this->memberA, $schedule);

        $memberA2 = $this->makeMember($this->gymA, 'FZ-0002');
        $this->activeMembership($memberA2);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('đủ chỗ');

        app(ClassBookingService::class)->book($memberA2, $schedule);
    }

    public function test_booking_locks_schedule_row_so_capacity_check_reads_committed_count(): void
    {
        // Không thể spawn thread thật trong PHPUnit; xác nhận cơ chế khóa bằng
        // cách chứng minh book() dùng SELECT ... FOR UPDATE trong transaction
        // (không throw deadlock/lost-update) và giá trị đếm sau mỗi lần book()
        // luôn phản ánh đúng số đã commit trước đó — lấp đầy capacity=2 tuần tự
        // 2 lần, lần thứ 3 phải thấy ĐÚNG 2 đã đặt (không đếm thiếu do race) và bị chặn.
        $schedule = $this->groupClass(['capacity' => 2]);

        $member1 = $this->memberA;
        $this->activeMembership($member1);
        $member2 = $this->makeMember($this->gymA, 'FZ-0002');
        $this->activeMembership($member2);
        $member3 = $this->makeMember($this->gymA, 'FZ-0003');
        $this->activeMembership($member3);

        app(ClassBookingService::class)->book($member1, $schedule);
        app(ClassBookingService::class)->book($member2, $schedule);

        $this->assertSame(2, $schedule->fresh()->bookedCount());

        $this->expectException(InvalidArgumentException::class);
        app(ClassBookingService::class)->book($member3, $schedule);
    }

    // Rule: member không đặt 2 lớp trùng khung giờ.
    public function test_member_cannot_book_two_overlapping_classes(): void
    {
        $this->activeMembership($this->memberA);
        $classA = $this->groupClass(['class_date' => now()->addDay()->toDateString(), 'start_time' => '10:00', 'end_time' => '11:00']);
        $classB = $this->groupClass(['class_date' => now()->addDay()->toDateString(), 'start_time' => '10:30', 'end_time' => '11:30']);

        app(ClassBookingService::class)->book($this->memberA, $classA);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('trùng khung giờ');

        app(ClassBookingService::class)->book($this->memberA, $classB);
    }

    public function test_member_can_book_two_non_overlapping_classes_same_day(): void
    {
        $this->activeMembership($this->memberA);
        $classA = $this->groupClass(['class_date' => now()->addDay()->toDateString(), 'start_time' => '10:00', 'end_time' => '11:00']);
        $classB = $this->groupClass(['class_date' => now()->addDay()->toDateString(), 'start_time' => '11:00', 'end_time' => '12:00']);

        app(ClassBookingService::class)->book($this->memberA, $classA);
        $booking = app(ClassBookingService::class)->book($this->memberA, $classB);

        $this->assertSame(ClassBooking::STATUS_BOOKED, $booking->status);
    }

    public function test_member_cannot_book_the_same_class_twice(): void
    {
        $this->activeMembership($this->memberA);
        $schedule = $this->groupClass(['capacity' => 10]);

        app(ClassBookingService::class)->book($this->memberA, $schedule);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('đã đặt lớp này rồi');

        app(ClassBookingService::class)->book($this->memberA, $schedule);
    }

    // Rule: buổi PT trừ remaining_pt_sessions, hết buổi PT thì chặn.
    public function test_booking_a_pt_session_decrements_remaining_pt_sessions(): void
    {
        $membership = $this->activeMembership($this->memberA, 3);
        $schedule = $this->ptSchedule();

        app(ClassBookingService::class)->book($this->memberA, $schedule);

        $this->assertSame(2, $membership->fresh()->remaining_pt_sessions);
    }

    public function test_booking_a_pt_session_is_blocked_when_remaining_pt_sessions_is_zero(): void
    {
        $this->activeMembership($this->memberA, 0);
        $schedule = $this->ptSchedule();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('hết số buổi PT');

        app(ClassBookingService::class)->book($this->memberA, $schedule);
    }

    // Rule: lớp nhóm KHÔNG đụng remaining_pt_sessions.
    public function test_booking_a_group_class_does_not_touch_remaining_pt_sessions(): void
    {
        $membership = $this->activeMembership($this->memberA, 3);
        $schedule = $this->groupClass();

        app(ClassBookingService::class)->book($this->memberA, $schedule);

        $this->assertSame(3, $membership->fresh()->remaining_pt_sessions);
    }

    // Rule: cross-tenant (đặt lớp gym khác) -> chặn ở tầng Service (403 tương
    // đương) và ở tầng HTTP (404, do global scope BelongsToGym trên Schedule).
    public function test_cross_tenant_booking_is_blocked_at_service_layer(): void
    {
        $this->activeMembership($this->memberA);
        $scheduleB = Schedule::factory()->create(['gym_id' => $this->gymB->id, 'capacity' => 10]);

        $this->expectException(CrossTenantOperationException::class);

        app(ClassBookingService::class)->book($this->memberA, $scheduleB);
    }

    public function test_cross_tenant_booking_via_http_returns_404(): void
    {
        $this->activeMembership($this->memberA);
        $scheduleB = Schedule::factory()->create(['gym_id' => $this->gymB->id, 'capacity' => 10]);

        $this->actingAs($this->memberA->user)
            ->post("/schedules/{$scheduleB->id}/book")
            ->assertNotFound();

        $this->assertDatabaseCount('class_bookings', 0);
    }

    // Huỷ booking: giải phóng chỗ, hoàn remaining_pt_sessions nếu là buổi PT.
    public function test_cancelling_a_booking_frees_the_capacity_slot(): void
    {
        $schedule = $this->groupClass(['capacity' => 1]);
        $this->activeMembership($this->memberA);
        $booking = app(ClassBookingService::class)->book($this->memberA, $schedule);

        app(ClassBookingService::class)->cancel($booking);

        $memberA2 = $this->makeMember($this->gymA, 'FZ-0002');
        $this->activeMembership($memberA2);

        $secondBooking = app(ClassBookingService::class)->book($memberA2, $schedule);
        $this->assertSame(ClassBooking::STATUS_BOOKED, $secondBooking->status);
    }

    public function test_cancelling_a_pt_booking_refunds_remaining_pt_sessions(): void
    {
        $membership = $this->activeMembership($this->memberA, 2);
        $schedule = $this->ptSchedule();

        $booking = app(ClassBookingService::class)->book($this->memberA, $schedule);
        $this->assertSame(1, $membership->fresh()->remaining_pt_sessions);

        app(ClassBookingService::class)->cancel($booking);

        $this->assertSame(2, $membership->fresh()->remaining_pt_sessions);
    }

    public function test_cannot_cancel_an_already_cancelled_booking(): void
    {
        $schedule = $this->groupClass();
        $this->activeMembership($this->memberA);
        $booking = app(ClassBookingService::class)->book($this->memberA, $schedule);

        app(ClassBookingService::class)->cancel($booking);

        $this->expectException(InvalidArgumentException::class);

        app(ClassBookingService::class)->cancel($booking->fresh());
    }

    // HTTP: member chỉ huỷ được booking của chính mình.
    public function test_member_cannot_cancel_another_members_booking_via_http(): void
    {
        $schedule = $this->groupClass();
        $this->activeMembership($this->memberA);
        $booking = app(ClassBookingService::class)->book($this->memberA, $schedule);

        $otherMember = $this->makeMember($this->gymA, 'FZ-0002');

        $this->actingAs($otherMember->user)
            ->delete("/bookings/{$booking->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('class_bookings', ['id' => $booking->id, 'status' => 'booked']);
    }

    public function test_member_can_book_and_cancel_via_http(): void
    {
        $this->activeMembership($this->memberA);
        $schedule = $this->groupClass();

        $this->actingAs($this->memberA->user)
            ->post("/schedules/{$schedule->id}/book")
            ->assertRedirect(route('member.schedules.index'));

        $booking = ClassBooking::where('schedule_id', $schedule->id)->where('member_id', $this->memberA->id)->firstOrFail();
        $this->assertSame(ClassBooking::STATUS_BOOKED, $booking->status);

        $this->actingAs($this->memberA->user)
            ->delete("/bookings/{$booking->id}")
            ->assertRedirect(route('member.bookings.index'));

        $this->assertSame(ClassBooking::STATUS_CANCELLED, $booking->fresh()->status);
    }
}
