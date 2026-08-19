<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Gym;
use App\Models\LoyaltyPointTransaction;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Notification;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\InvoiceService;
use App\Services\MembershipService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Bằng chứng demo mạnh nhất (Khối 7): chạy TRỌN workflow mục 26 trong 1 test
 * duy nhất, dùng đúng các Service thật (không mock) — chọn gói → tạo
 * membership pending → tạo payment + QR → Staff xác nhận thanh toán → check-in
 * bằng QR ngay sau đó. Mỗi bước assert xong mới sang bước kế để nếu đỏ, biết
 * NGAY bước nào hỏng.
 */
class CoreWorkflowEndToEndTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_membership_payment_invoice_checkin_workflow(): void
    {
        Storage::fake('local');

        // --- Chuẩn bị: 1 Gym, 1 Member, 1 Package, 1 Staff xác nhận thanh toán. ---
        $gym = Gym::factory()->create(['code' => 'FZ']);

        $memberUser = User::factory()->create(['gym_id' => $gym->id, 'role' => User::ROLE_MEMBER]);
        $member = Member::create([
            'gym_id' => $gym->id,
            'user_id' => $memberUser->id,
            'member_code' => 'FZ-0001',
            'status' => Member::STATUS_ACTIVE,
        ]);

        $package = Package::factory()->create([
            'gym_id' => $gym->id,
            'name' => 'Gói 1 tháng',
            'price' => 500000,
            'duration_days' => 30,
            'pt_sessions' => 4,
            'is_active' => true,
        ]);

        $staff = User::factory()->create(['gym_id' => $gym->id, 'role' => User::ROLE_STAFF]);

        // --- Bước 1: chọn member + gói -> tạo Membership (mục 26, giai đoạn 1). ---
        $membership = app(MembershipService::class)->create($member, $package, null, [
            'start_date' => now()->toDateString(),
        ]);

        $this->assertSame(Membership::STATUS_PENDING, $membership->status);
        $this->assertSame('500000.00', $membership->final_price);

        // --- Bước 2: tạo Payment (pending) + QR VietQR cho Membership vừa tạo. ---
        $payment = app(PaymentService::class)->create($membership);

        $this->assertSame(Payment::STATUS_PENDING, $payment->status);
        $this->assertNotEmpty($payment->qr_payload);
        $this->assertSame('500000.00', $payment->amount);

        // --- Bước 3: Staff xác nhận đã nhận tiền -> atomic (Payment/Membership/
        // Invoice/Notification cùng 1 transaction, xem PaymentService::confirm()). ---
        $confirmedPayment = app(PaymentService::class)->confirm($payment, $staff, 'Đối chiếu sao kê MBBank lúc demo');

        $this->assertSame(Payment::STATUS_PAID, $confirmedPayment->status);
        $this->assertNotNull($confirmedPayment->paid_at);
        $this->assertSame($staff->id, $confirmedPayment->confirmed_by);

        $activatedMembership = $membership->fresh();
        $this->assertSame(Membership::STATUS_ACTIVE, $activatedMembership->status);

        // --- Bước 4: assert Invoice tồn tại + PDF file có thật trên disk. ---
        $invoice = $confirmedPayment->invoice()->firstOrFail();
        $this->assertSame($member->id, $invoice->member_id);
        $this->assertEquals(500000, (float) $invoice->total);

        $pdfPath = app(InvoiceService::class)->ensureStored($invoice);
        Storage::disk('local')->assertExists($pdfPath);
        $this->assertGreaterThan(0, Storage::disk('local')->size($pdfPath));

        // --- Bước 5: assert Notification đã gửi cho member. ---
        $this->assertDatabaseHas('notifications', [
            'user_id' => $memberUser->id,
            'type' => Notification::TYPE_PAYMENT_CONFIRMED,
        ]);

        // --- Bước 6: member check-in bằng QR ngay sau khi kích hoạt membership
        // (mục 6) -> Attendance ghi nhận + điểm loyalty cộng tự động (mục 17). ---
        $token = app(AttendanceService::class)->tokenFor($member);
        $attendance = app(AttendanceService::class)->checkIn($token, $staff);

        $this->assertSame($gym->id, $attendance->gym_id);
        $this->assertSame($member->id, $attendance->member_id);
        $this->assertSame(Attendance::SOURCE_QR, $attendance->source);
        $this->assertSame(now()->toDateString(), $attendance->check_in_date->toDateString());

        $this->assertDatabaseHas('loyalty_point_transactions', [
            'gym_id' => $gym->id,
            'member_id' => $member->id,
            'points' => LoyaltyPointTransaction::POINTS_CHECK_IN,
            'reason' => LoyaltyPointTransaction::REASON_CHECK_IN,
            'reference_type' => Attendance::class,
            'reference_id' => $attendance->id,
            'balance_after' => LoyaltyPointTransaction::POINTS_CHECK_IN,
        ]);

        // --- Toàn cảnh cuối workflow: đúng 1 bản ghi mỗi loại, không tạo thừa/thiếu. ---
        $this->assertDatabaseCount('memberships', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('invoices', 1);
        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseCount('loyalty_point_transactions', 1);
    }
}
