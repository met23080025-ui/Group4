<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Notification;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\MembershipService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class PaymentConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private Gym $gymA;

    private Gym $gymB;

    private Member $memberA;

    private Membership $membershipA;

    private Payment $paymentA;

    private User $staffA;

    private User $ownerB;

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
            'pt_sessions' => 4,
            'is_active' => true,
        ]);

        $this->membershipA = app(MembershipService::class)->create($this->memberA, $package, null);
        $this->paymentA = app(PaymentService::class)->create($this->membershipA);

        $this->staffA = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_STAFF]);
        $this->ownerB = User::factory()->create(['gym_id' => $this->gymB->id, 'role' => User::ROLE_GYM_OWNER]);
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

    public function test_confirming_payment_activates_membership_creates_invoice_and_notification(): void
    {
        $originalStart = $this->membershipA->start_date;
        $originalEnd = $this->membershipA->end_date;

        $confirmed = app(PaymentService::class)->confirm($this->paymentA, $this->staffA, 'đối chiếu sao kê 14:32');

        $this->assertSame(Payment::STATUS_PAID, $confirmed->status);
        $this->assertNotNull($confirmed->paid_at);
        $this->assertSame($this->staffA->id, $confirmed->confirmed_by);
        $this->assertSame('đối chiếu sao kê 14:32', $confirmed->note);

        $membership = $this->membershipA->fresh();
        $this->assertSame(Membership::STATUS_ACTIVE, $membership->status);
        // start/end date và remaining_pt_sessions không bị tính lại khi active hóa.
        $this->assertTrue($originalStart->equalTo($membership->start_date));
        $this->assertTrue($originalEnd->equalTo($membership->end_date));
        $this->assertSame(4, $membership->remaining_pt_sessions);

        $this->assertDatabaseCount('invoices', 1);
        $invoice = Invoice::first();
        $this->assertSame($this->paymentA->id, $invoice->payment_id);
        $this->assertSame($this->memberA->id, $invoice->member_id);
        $this->assertMatchesRegularExpression('/^INV-FZ-\d{8}-0001$/', $invoice->invoice_number);
        $this->assertSame('500000.00', $invoice->subtotal);
        $this->assertSame('0.00', $invoice->discount);
        $this->assertSame('500000.00', $invoice->total);

        $this->assertDatabaseCount('notifications', 1);
        $notification = Notification::first();
        $this->assertSame($this->memberA->user_id, $notification->user_id);
        $this->assertSame(Notification::TYPE_PAYMENT_CONFIRMED, $notification->type);
        $this->assertSame($this->paymentA->id, $notification->data['payment_id']);
        $this->assertSame($membership->id, $notification->data['membership_id']);
        $this->assertSame($invoice->id, $notification->data['invoice_id']);
    }

    /**
     * Chứng minh tính nguyên tử: giả lập ném lỗi giữa transaction (sau khi
     * Payment/Membership đã được ghi trong bộ nhớ transaction, ngay tại bước
     * tạo Invoice) — assert KHÔNG có gì được ghi thật vào DB: Payment vẫn
     * pending, Membership vẫn pending, không có Invoice, không có Notification.
     */
    public function test_transaction_rolls_back_completely_when_an_error_occurs_mid_confirmation(): void
    {
        $this->mock(InvoiceService::class, function ($mock) {
            $mock->shouldReceive('create')->once()->andThrow(new RuntimeException('Lỗi giả lập giữa transaction'));
        });

        $this->expectException(RuntimeException::class);

        try {
            app(PaymentService::class)->confirm($this->paymentA, $this->staffA);
        } finally {
            $this->assertDatabaseHas('payments', [
                'id' => $this->paymentA->id,
                'status' => Payment::STATUS_PENDING,
                'paid_at' => null,
                'confirmed_by' => null,
            ]);
            $this->assertDatabaseHas('memberships', [
                'id' => $this->membershipA->id,
                'status' => Membership::STATUS_PENDING,
            ]);
            $this->assertDatabaseCount('invoices', 0);
            $this->assertDatabaseCount('notifications', 0);
        }
    }

    public function test_cannot_confirm_an_already_paid_payment_twice(): void
    {
        app(PaymentService::class)->confirm($this->paymentA, $this->staffA);

        $this->expectException(\InvalidArgumentException::class);

        try {
            app(PaymentService::class)->confirm($this->paymentA->fresh(), $this->staffA);
        } finally {
            // Lần xác nhận thứ 2 thất bại — không tạo thêm Invoice/Notification.
            $this->assertDatabaseCount('invoices', 1);
            $this->assertDatabaseCount('notifications', 1);
        }
    }

    public function test_staff_confirming_via_http_activates_membership_and_redirects(): void
    {
        $response = $this->actingAs($this->staffA)
            ->post(route('gym.payments.confirm', $this->paymentA));

        $response->assertRedirect(route('gym.payments.show', $this->paymentA));
        $response->assertSessionHas('success');

        $this->assertSame(Payment::STATUS_PAID, $this->paymentA->fresh()->status);
        $this->assertSame(Membership::STATUS_ACTIVE, $this->membershipA->fresh()->status);
    }

    public function test_confirming_twice_via_http_shows_error_and_does_not_duplicate_records(): void
    {
        $this->actingAs($this->staffA)->post(route('gym.payments.confirm', $this->paymentA));

        $response = $this->actingAs($this->staffA)->post(route('gym.payments.confirm', $this->paymentA));

        $response->assertSessionHas('error');
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_member_cannot_confirm_payment_via_http(): void
    {
        $this->actingAs($this->memberA->user)
            ->post(route('gym.payments.confirm', $this->paymentA))
            ->assertForbidden();

        $this->assertSame(Payment::STATUS_PENDING, $this->paymentA->fresh()->status);
    }

    public function test_cross_tenant_confirm_returns_404(): void
    {
        $this->actingAs($this->ownerB)
            ->post(route('gym.payments.confirm', $this->paymentA))
            ->assertNotFound();

        $this->assertSame(Payment::STATUS_PENDING, $this->paymentA->fresh()->status);
    }
}
