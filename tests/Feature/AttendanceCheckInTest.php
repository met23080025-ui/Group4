<?php

namespace Tests\Feature;

use App\Exceptions\CrossTenantOperationException;
use App\Models\Attendance;
use App\Models\Gym;
use App\Models\LoyaltyPointTransaction;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Package;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class AttendanceCheckInTest extends TestCase
{
    use RefreshDatabase;

    private Gym $gymA;

    private Gym $gymB;

    private Member $memberA;

    private User $staffA;

    private User $staffB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gymA = Gym::factory()->create(['code' => 'FZ']);
        $this->gymB = Gym::factory()->create(['code' => 'PH']);

        $this->memberA = $this->makeMember($this->gymA, 'FZ-0001');
        $this->staffA = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_STAFF]);
        $this->staffB = User::factory()->create(['gym_id' => $this->gymB->id, 'role' => User::ROLE_STAFF]);
    }

    private function makeMember(Gym $gym, string $code, string $status = Member::STATUS_ACTIVE): Member
    {
        $user = User::factory()->create(['gym_id' => $gym->id, 'role' => User::ROLE_MEMBER]);

        return Member::create([
            'gym_id' => $gym->id,
            'user_id' => $user->id,
            'member_code' => $code,
            'status' => $status,
        ]);
    }

    private function activeMembership(Member $member, array $overrides = []): Membership
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
            'status' => Membership::STATUS_ACTIVE,
        ], $overrides));
    }

    private function tokenFor(Member $member): string
    {
        return app(AttendanceService::class)->tokenFor($member);
    }

    // Rule: token QR không phải id trần — phải chứa chữ ký HMAC hợp lệ.
    public function test_qr_token_is_not_the_raw_member_id(): void
    {
        $token = $this->tokenFor($this->memberA);

        $this->assertNotEquals((string) $this->memberA->id, $token);
        $this->assertNotEquals(base64_encode((string) $this->memberA->id), $token);

        $decoded = base64_decode($token, true);
        [$idPart, $signature] = explode('|', $decoded, 2);
        $this->assertSame((string) $this->memberA->id, $idPart);
        // Chữ ký HMAC-SHA256 hex dài 64 ký tự, không phải rỗng/ID lặp lại.
        $this->assertSame(64, strlen($signature));
    }

    // Rule: token bị giả mạo (chữ ký sai) -> chặn.
    public function test_tampered_token_is_rejected(): void
    {
        $token = $this->tokenFor($this->memberA);
        $decoded = base64_decode($token, true);
        [$idPart] = explode('|', $decoded, 2);
        $forged = base64_encode($idPart.'|'.str_repeat('0', 64));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('không hợp lệ');

        app(AttendanceService::class)->checkIn($forged, $this->staffA);
    }

    // Rule: member đang bị khóa -> chặn.
    public function test_blocked_member_cannot_check_in(): void
    {
        $blocked = $this->makeMember($this->gymA, 'FZ-0002', Member::STATUS_BLOCKED);
        $this->activeMembership($blocked);
        $token = $this->tokenFor($blocked);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('bị khóa');

        app(AttendanceService::class)->checkIn($token, $this->staffA);
    }

    // Rule: membership hết hạn (hoặc chưa từng có) -> chặn.
    public function test_member_with_no_active_membership_cannot_check_in(): void
    {
        $token = $this->tokenFor($this->memberA);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('gói tập đang hoạt động');

        app(AttendanceService::class)->checkIn($token, $this->staffA);
    }

    public function test_member_with_expired_membership_cannot_check_in(): void
    {
        $this->activeMembership($this->memberA, [
            'start_date' => now()->subDays(60)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
        ]);
        $token = $this->tokenFor($this->memberA);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('gói tập đang hoạt động');

        app(AttendanceService::class)->checkIn($token, $this->staffA);
    }

    // Rule: check-in hợp lệ -> tạo Attendance đúng Gym, cộng +10 điểm loyalty.
    public function test_valid_check_in_creates_attendance_in_correct_gym_and_awards_loyalty_points(): void
    {
        $this->activeMembership($this->memberA);
        $token = $this->tokenFor($this->memberA);

        $attendance = app(AttendanceService::class)->checkIn($token, $this->staffA);

        $this->assertSame($this->gymA->id, $attendance->gym_id);
        $this->assertSame($this->memberA->id, $attendance->member_id);
        $this->assertSame(Attendance::SOURCE_QR, $attendance->source);
        $this->assertSame(now()->toDateString(), $attendance->check_in_date->toDateString());

        $this->assertDatabaseHas('loyalty_point_transactions', [
            'gym_id' => $this->gymA->id,
            'member_id' => $this->memberA->id,
            'points' => LoyaltyPointTransaction::POINTS_CHECK_IN,
            'reason' => LoyaltyPointTransaction::REASON_CHECK_IN,
            'reference_type' => Attendance::class,
            'reference_id' => $attendance->id,
            'balance_after' => 10,
        ]);
    }

    // Regression: membership bắt đầu ĐÚNG HÔM NAY vẫn phải hợp lệ để check-in
    // (bug: so sánh chuỗi start_date thay vì whereDate từng làm rớt case này
    // trên SQLite, phát hiện qua CoreWorkflowEndToEndTest — đã sửa).
    public function test_member_with_membership_starting_today_can_check_in(): void
    {
        $this->activeMembership($this->memberA, [
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
        ]);
        $token = $this->tokenFor($this->memberA);

        $attendance = app(AttendanceService::class)->checkIn($token, $this->staffA);

        $this->assertSame($this->memberA->id, $attendance->member_id);
    }

    // Rule: không check-in trùng trong cùng 1 ngày (unique gym_id+member_id+check_in_date).
    public function test_cannot_check_in_twice_on_the_same_day(): void
    {
        $this->activeMembership($this->memberA);
        $token = $this->tokenFor($this->memberA);

        app(AttendanceService::class)->checkIn($token, $this->staffA);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('đã check-in hôm nay rồi');

        app(AttendanceService::class)->checkIn($token, $this->staffA);

        $this->assertSame(1, Attendance::where('member_id', $this->memberA->id)->count());
    }

    // Rule: token member Gym A quét ở Gym B -> chặn.
    public function test_cross_tenant_check_in_is_blocked_at_service_layer(): void
    {
        $this->activeMembership($this->memberA);
        $token = $this->tokenFor($this->memberA);

        $this->expectException(CrossTenantOperationException::class);

        app(AttendanceService::class)->checkIn($token, $this->staffB);
    }

    public function test_cross_tenant_check_in_via_http_is_blocked_and_creates_no_attendance(): void
    {
        $this->activeMembership($this->memberA);
        $token = $this->tokenFor($this->memberA);

        $this->actingAs($this->staffB)
            ->post('/gym/checkin', ['token' => $token])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('attendances', 0);
    }

    // HTTP: luồng check-in thành công đầy đủ qua Staff.
    public function test_staff_can_check_in_member_via_http(): void
    {
        $this->activeMembership($this->memberA);
        $token = $this->tokenFor($this->memberA);

        $this->actingAs($this->staffA)
            ->post('/gym/checkin', ['token' => $token])
            ->assertRedirect(route('gym.checkin.index'));

        $this->assertDatabaseHas('attendances', [
            'gym_id' => $this->gymA->id,
            'member_id' => $this->memberA->id,
        ]);
    }

    // HTTP: Member không tự check-in hộ mình qua route Staff (403).
    public function test_member_cannot_check_in_via_http(): void
    {
        $this->activeMembership($this->memberA);
        $token = $this->tokenFor($this->memberA);

        $this->actingAs($this->memberA->user)
            ->post('/gym/checkin', ['token' => $token])
            ->assertForbidden();
    }

    // HTTP: Member xem trang QR của chính mình, token không lộ SĐT/tên.
    public function test_member_can_view_own_qr_page_without_leaking_pii_in_token(): void
    {
        $this->actingAs($this->memberA->user)->get('/qr')->assertOk();

        $token = $this->tokenFor($this->memberA);
        $decoded = base64_decode($token, true);
        $this->assertStringNotContainsString($this->memberA->user->name, $decoded);
    }
}
