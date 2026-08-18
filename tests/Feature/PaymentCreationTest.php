<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentCreationTest extends TestCase
{
    use RefreshDatabase;

    private Gym $gymA;

    private Gym $gymB;

    private Member $memberA;

    private Membership $membershipA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gymA = Gym::factory()->create(['code' => 'FZ']);
        $this->gymB = Gym::factory()->create(['code' => 'PH']);

        $this->memberA = $this->makeActiveMember($this->gymA, 'FZ-0001');

        $package = Package::factory()->create([
            'gym_id' => $this->gymA->id,
            'price' => 500000,
            'duration_days' => 30,
            'is_active' => true,
        ]);

        $this->membershipA = app(MembershipService::class)->create($this->memberA, $package, null);
    }

    private function makeActiveMember(Gym $gym, string $code): Member
    {
        $user = User::factory()->create(['gym_id' => $gym->id, 'role' => User::ROLE_MEMBER]);

        return Member::create([
            'gym_id' => $gym->id,
            'user_id' => $user->id,
            'member_code' => $code,
            'status' => Member::STATUS_ACTIVE,
        ]);
    }

    public function test_payment_is_created_pending_with_correct_amount_and_code_format(): void
    {
        $payment = app(PaymentService::class)->create($this->membershipA);

        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
        $this->assertSame($this->membershipA->final_price, $payment->amount);
        $this->assertSame($this->membershipA->id, $payment->membership_id);
        $this->assertSame($this->memberA->id, $payment->member_id);
        $this->assertMatchesRegularExpression('/^PAY-FZ-\d{8}-0001$/', $payment->transaction_code);
        $this->assertNotEmpty($payment->qr_payload);
        $this->assertStringContainsString('img.vietqr.io', $payment->qr_payload);
        $this->assertStringContainsString($payment->transaction_code, $payment->qr_payload);
    }

    public function test_transaction_codes_are_sequential_and_unique_per_gym(): void
    {
        $package = Package::factory()->create(['gym_id' => $this->gymA->id, 'price' => 200000, 'is_active' => true]);
        $member2 = $this->makeActiveMember($this->gymA, 'FZ-0002');
        $membership2 = app(MembershipService::class)->create($member2, $package, null);

        $payment1 = app(PaymentService::class)->create($this->membershipA);
        $payment2 = app(PaymentService::class)->create($membership2);

        $this->assertNotSame($payment1->transaction_code, $payment2->transaction_code);
        $this->assertStringEndsWith('-0001', $payment1->transaction_code);
        $this->assertStringEndsWith('-0002', $payment2->transaction_code);
    }

    public function test_cannot_create_second_payment_for_same_membership(): void
    {
        app(PaymentService::class)->create($this->membershipA);

        $this->expectException(\InvalidArgumentException::class);

        app(PaymentService::class)->create($this->membershipA->fresh());
    }

    public function test_cannot_create_payment_for_non_pending_membership(): void
    {
        $this->membershipA->update(['status' => Membership::STATUS_CANCELLED]);

        $this->expectException(\InvalidArgumentException::class);

        app(PaymentService::class)->create($this->membershipA);
    }

    public function test_staff_can_view_payment_of_own_gym_via_http(): void
    {
        $payment = app(PaymentService::class)->create($this->membershipA);

        $staffA = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_STAFF]);

        $this->actingAs($staffA)
            ->get(route('gym.payments.show', $payment))
            ->assertOk()
            ->assertSee($payment->transaction_code);
    }

    public function test_cross_tenant_payment_access_returns_404(): void
    {
        $payment = app(PaymentService::class)->create($this->membershipA);

        $ownerB = User::factory()->create(['gym_id' => $this->gymB->id, 'role' => User::ROLE_GYM_OWNER]);

        $this->actingAs($ownerB)
            ->get(route('gym.payments.show', $payment))
            ->assertNotFound();
    }

    public function test_member_can_view_own_payment_but_not_another_members_payment(): void
    {
        $payment = app(PaymentService::class)->create($this->membershipA);

        $this->actingAs($this->memberA->user)
            ->get(route('member.payments.show', $payment))
            ->assertOk();

        $otherMember = $this->makeActiveMember($this->gymA, 'FZ-0003');

        $this->actingAs($otherMember->user)
            ->get(route('member.payments.show', $payment))
            ->assertForbidden();
    }

    public function test_gym_payments_index_only_lists_own_gym_pending_payments(): void
    {
        $paymentA = app(PaymentService::class)->create($this->membershipA);

        $packageB = Package::factory()->create(['gym_id' => $this->gymB->id, 'price' => 300000, 'is_active' => true]);
        $memberB = $this->makeActiveMember($this->gymB, 'PH-0001');
        $membershipB = app(MembershipService::class)->create($memberB, $packageB, null);
        app(PaymentService::class)->create($membershipB);

        $ownerA = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_GYM_OWNER]);

        $response = $this->actingAs($ownerA)->get(route('gym.payments.index'));

        $response->assertOk();
        $response->assertSee($paymentA->transaction_code);
        $response->assertSee($this->memberA->member_code);
        $response->assertDontSee('PH-0001');
    }
}
