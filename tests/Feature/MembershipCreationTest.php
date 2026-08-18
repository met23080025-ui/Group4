<?php

namespace Tests\Feature;

use App\Exceptions\CrossTenantOperationException;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Package;
use App\Models\Promotion;
use App\Models\User;
use App\Services\MembershipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipCreationTest extends TestCase
{
    use RefreshDatabase;

    private Gym $gymA;

    private Gym $gymB;

    private Member $memberA;

    private Package $packageA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gymA = Gym::factory()->create(['code' => 'FZ']);
        $this->gymB = Gym::factory()->create(['code' => 'PH']);

        $userA = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_MEMBER]);
        $this->memberA = Member::create([
            'gym_id' => $this->gymA->id,
            'user_id' => $userA->id,
            'member_code' => 'FZ-0001',
            'status' => Member::STATUS_ACTIVE,
        ]);

        $this->packageA = Package::factory()->create([
            'gym_id' => $this->gymA->id,
            'price' => 500000,
            'duration_days' => 30,
            'pt_sessions' => 4,
            'is_active' => true,
        ]);
    }

    private function promotionA(array $overrides = []): Promotion
    {
        return Promotion::factory()->create(array_merge([
            'gym_id' => $this->gymA->id,
            'code' => 'SALE'.fake()->unique()->numberBetween(1, 99999),
            'discount_type' => Promotion::DISCOUNT_TYPE_PERCENT,
            'discount_value' => 10,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'usage_limit' => null,
            'used_count' => 0,
            'is_active' => true,
        ], $overrides));
    }

    public function test_membership_created_without_promotion_has_full_price_and_is_pending(): void
    {
        $membership = app(MembershipService::class)->create($this->memberA, $this->packageA, null);

        $this->assertSame('500000.00', $membership->original_price);
        $this->assertSame('0.00', $membership->discount_amount);
        $this->assertSame('500000.00', $membership->final_price);
        $this->assertSame(Membership::STATUS_PENDING, $membership->status);
        $this->assertSame(4, $membership->remaining_pt_sessions);
    }

    public function test_percent_discount_is_calculated_correctly(): void
    {
        $promotion = $this->promotionA(['discount_type' => Promotion::DISCOUNT_TYPE_PERCENT, 'discount_value' => 15]);

        $membership = app(MembershipService::class)->create($this->memberA, $this->packageA, $promotion);

        // 500000 * 15% = 75000
        $this->assertSame('75000.00', $membership->discount_amount);
        $this->assertSame('425000.00', $membership->final_price);
        $this->assertSame(Membership::STATUS_PENDING, $membership->status);
    }

    public function test_fixed_discount_is_calculated_correctly(): void
    {
        $promotion = $this->promotionA(['discount_type' => Promotion::DISCOUNT_TYPE_FIXED, 'discount_value' => 120000]);

        $membership = app(MembershipService::class)->create($this->memberA, $this->packageA, $promotion);

        $this->assertSame('120000.00', $membership->discount_amount);
        $this->assertSame('380000.00', $membership->final_price);
    }

    public function test_fixed_discount_larger_than_price_is_capped_and_final_price_never_negative(): void
    {
        $promotion = $this->promotionA(['discount_type' => Promotion::DISCOUNT_TYPE_FIXED, 'discount_value' => 9999999]);

        $membership = app(MembershipService::class)->create($this->memberA, $this->packageA, $promotion);

        $this->assertSame('500000.00', $membership->discount_amount);
        $this->assertSame('0.00', $membership->final_price);
    }

    public function test_membership_is_never_created_as_active(): void
    {
        $membership = app(MembershipService::class)->create($this->memberA, $this->packageA, null);

        $this->assertNotSame(Membership::STATUS_ACTIVE, $membership->fresh()->status);
        $this->assertDatabaseHas('memberships', ['id' => $membership->id, 'status' => Membership::STATUS_PENDING]);
    }

    public function test_expired_promotion_is_rejected(): void
    {
        $expired = $this->promotionA([
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDays(1)->toDateString(),
        ]);

        $this->expectException(\InvalidArgumentException::class);

        app(MembershipService::class)->create($this->memberA, $this->packageA, $expired);
    }

    public function test_inactive_promotion_is_rejected(): void
    {
        $inactive = $this->promotionA(['is_active' => false]);

        $this->expectException(\InvalidArgumentException::class);

        app(MembershipService::class)->create($this->memberA, $this->packageA, $inactive);
    }

    public function test_promotion_over_usage_limit_is_rejected(): void
    {
        $maxedOut = $this->promotionA(['usage_limit' => 1, 'used_count' => 1]);

        $this->expectException(\InvalidArgumentException::class);

        app(MembershipService::class)->create($this->memberA, $this->packageA, $maxedOut);
    }

    public function test_cross_tenant_package_is_blocked(): void
    {
        $packageB = Package::factory()->create(['gym_id' => $this->gymB->id]);

        $this->expectException(CrossTenantOperationException::class);

        app(MembershipService::class)->create($this->memberA, $packageB, null);
    }

    public function test_cross_tenant_promotion_is_blocked(): void
    {
        $promotionB = Promotion::factory()->create([
            'gym_id' => $this->gymB->id,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'is_active' => true,
        ]);

        $this->expectException(CrossTenantOperationException::class);

        app(MembershipService::class)->create($this->memberA, $this->packageA, $promotionB);
    }

    public function test_cross_tenant_membership_creation_via_http_is_blocked_by_validation(): void
    {
        $ownerA = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_GYM_OWNER]);
        $packageB = Package::factory()->create(['gym_id' => $this->gymB->id, 'is_active' => true]);

        $response = $this->actingAs($ownerA)->post('/gym/memberships', [
            'member_id' => $this->memberA->id,
            'package_id' => $packageB->id,
        ]);

        $response->assertSessionHasErrors('package_id');
        $this->assertDatabaseCount('memberships', 0);
    }
}
