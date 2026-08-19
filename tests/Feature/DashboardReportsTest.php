<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\Member;
use App\Models\Package;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DashboardReportsTest extends TestCase
{
    use RefreshDatabase;

    private Gym $gymA;

    private Gym $gymB;

    private User $ownerA;

    private User $staffA;

    private User $ownerB;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->gymA = Gym::factory()->create(['code' => 'FZ']);
        $this->gymB = Gym::factory()->create(['code' => 'PH']);

        $this->ownerA = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_GYM_OWNER]);
        $this->staffA = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_STAFF]);
        $this->ownerB = User::factory()->create(['gym_id' => $this->gymB->id, 'role' => User::ROLE_GYM_OWNER]);
    }

    private function makeMember(Gym $gym): Member
    {
        $user = User::factory()->create(['gym_id' => $gym->id, 'role' => User::ROLE_MEMBER]);

        return Member::create([
            'gym_id' => $gym->id, 'user_id' => $user->id,
            'member_code' => 'MB-'.$user->id, 'status' => Member::STATUS_ACTIVE,
        ]);
    }

    /**
     * Chạy trọn workflow tạo doanh thu THẬT (membership pending -> payment ->
     * confirm -> invoice), giống CoreWorkflowEndToEndTest, để Reports có dữ
     * liệu thật để tính thay vì insert thẳng vào bảng invoices.
     */
    private function payForMembership(Gym $gym, Member $member, User $staff, float $price, string $packageName = 'Gói tháng'): void
    {
        $package = Package::factory()->create(['gym_id' => $gym->id, 'name' => $packageName, 'price' => $price, 'duration_days' => 30]);
        $membership = app(MembershipService::class)->create($member, $package, null);
        $payment = app(PaymentService::class)->create($membership);
        app(PaymentService::class)->confirm($payment, $staff);
    }

    // Rule: Owner thấy đúng số liệu Gym mình (member, doanh thu tháng này...).
    public function test_owner_dashboard_shows_correctly_scoped_stats(): void
    {
        $member = $this->makeMember($this->gymA);
        $this->payForMembership($this->gymA, $member, $this->staffA, 500000);

        $response = $this->actingAs($this->ownerA)->get(route('gym.dashboard'));

        $response->assertOk();
        $response->assertViewHas('total_members', 1);
        $response->assertViewHas('revenue_this_month', 500000.0);
    }

    // Rule (bắt buộc): Owner Gym A KHÔNG thấy doanh thu/số liệu Gym B.
    public function test_owner_dashboard_does_not_leak_another_gyms_revenue(): void
    {
        $memberA = $this->makeMember($this->gymA);
        $this->payForMembership($this->gymA, $memberA, $this->staffA, 300000);

        $ownerBStaff = User::factory()->create(['gym_id' => $this->gymB->id, 'role' => User::ROLE_STAFF]);
        $memberB = $this->makeMember($this->gymB);
        $this->payForMembership($this->gymB, $memberB, $ownerBStaff, 9999999);

        $response = $this->actingAs($this->ownerA)->get(route('gym.dashboard'));

        $response->assertViewHas('total_members', 1);
        $response->assertViewHas('revenue_this_month', 300000.0);
    }

    public function test_staff_dashboard_shows_subset_stats(): void
    {
        $response = $this->actingAs($this->staffA)->get(route('staff.dashboard'));

        $response->assertOk();
        $response->assertViewHasAll(['checkins_today', 'pending_payments', 'expiring_memberships', 'upcoming_schedules']);
    }

    public function test_member_dashboard_shows_current_membership_and_days_remaining(): void
    {
        $member = $this->makeMember($this->gymA);
        $this->payForMembership($this->gymA, $member, $this->staffA, 500000);

        $response = $this->actingAs($member->user)->get(route('member.home'));

        $response->assertOk();
        $response->assertViewHas('stats', function ($stats) {
            return $stats['current_membership'] !== null
                && $stats['days_remaining'] >= 29 && $stats['days_remaining'] <= 30;
        });
    }

    public function test_member_dashboard_is_safe_without_member_profile(): void
    {
        $user = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_MEMBER]);

        $this->actingAs($user)->get(route('member.home'))->assertOk();
    }

    // Rule: platform_admin thấy tổng hợp toàn nền tảng (không bị lọc theo 1 Gym).
    public function test_platform_admin_dashboard_shows_aggregate_totals_across_all_gyms(): void
    {
        $admin = User::factory()->create(['gym_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('total_gyms', 2);
        // 2 owner + 1 staff đã tạo ở setUp = 3 user, kể cả platform_admin vừa tạo = 4.
        $response->assertViewHas('total_users', 4);
    }

    public function test_admin_gyms_index_lists_all_gyms_with_counts(): void
    {
        $admin = User::factory()->create(['gym_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);
        $this->makeMember($this->gymA);

        $response = $this->actingAs($admin)->get(route('admin.gyms.index'));

        $response->assertOk();
        $response->assertSee($this->gymA->name);
        $response->assertSee($this->gymB->name);
    }

    public function test_admin_can_toggle_gym_active_status(): void
    {
        $admin = User::factory()->create(['gym_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);
        $this->assertTrue($this->gymA->is_active);

        $this->actingAs($admin)
            ->post(route('admin.gyms.toggle-active', $this->gymA))
            ->assertRedirect(route('admin.gyms.index'));

        $this->assertFalse($this->gymA->fresh()->is_active);

        $this->actingAs($admin)->post(route('admin.gyms.toggle-active', $this->gymA));
        $this->assertTrue($this->gymA->fresh()->is_active);
    }

    // Rule: chỉ Platform Admin quản lý Gym — Owner không truy cập được (403).
    public function test_non_admin_cannot_access_gym_management(): void
    {
        $this->actingAs($this->ownerA)->get(route('admin.gyms.index'))->assertForbidden();
        $this->actingAs($this->ownerA)->post(route('admin.gyms.toggle-active', $this->gymA))->assertForbidden();
    }

    // Rule (bắt buộc): Reports doanh thu chỉ tính đúng Gym của Owner đang xem.
    public function test_revenue_report_is_scoped_to_own_gym_only(): void
    {
        $memberA = $this->makeMember($this->gymA);
        $this->payForMembership($this->gymA, $memberA, $this->staffA, 500000);

        $staffB = User::factory()->create(['gym_id' => $this->gymB->id, 'role' => User::ROLE_STAFF]);
        $memberB = $this->makeMember($this->gymB);
        $this->payForMembership($this->gymB, $memberB, $staffB, 9999999);

        $response = $this->actingAs($this->ownerA)->get(route('gym.reports.revenue'));

        $response->assertOk();
        $response->assertViewHas('report', function ($report) {
            return $report['total'] === 500000.0 && $report['invoice_count'] === 1;
        });
    }

    public function test_revenue_report_groups_by_month_and_package(): void
    {
        $member = $this->makeMember($this->gymA);
        $this->payForMembership($this->gymA, $member, $this->staffA, 400000, 'Gói Cardio');

        $response = $this->actingAs($this->ownerA)->get(route('gym.reports.revenue'));

        $response->assertViewHas('report', function ($report) {
            $thisMonth = now()->format('Y-m');

            return ($report['by_month'][$thisMonth] ?? null) === 400000.0
                && ($report['by_package']['Gói Cardio'] ?? null) === 400000.0;
        });
    }

    // Rule: filter theo khoảng ngày loại đúng hóa đơn ngoài phạm vi.
    public function test_revenue_report_filters_by_date_range_excludes_out_of_range_invoices(): void
    {
        $member = $this->makeMember($this->gymA);
        $this->payForMembership($this->gymA, $member, $this->staffA, 500000);

        $response = $this->actingAs($this->ownerA)->get(route('gym.reports.revenue', [
            'from' => now()->addDays(1)->toDateString(),
            'to' => now()->addDays(10)->toDateString(),
        ]));

        $response->assertViewHas('report', fn ($report) => $report['total'] === 0.0 && $report['invoice_count'] === 0);
    }

    // Rule: chỉ Owner xem báo cáo doanh thu — Staff không truy cập được (403).
    public function test_staff_cannot_access_revenue_report(): void
    {
        $this->actingAs($this->staffA)->get(route('gym.reports.revenue'))->assertForbidden();
    }
}
