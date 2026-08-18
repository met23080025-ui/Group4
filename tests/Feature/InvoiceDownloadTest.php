<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\Invoice;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Package;
use App\Models\Payment;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\MembershipService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoiceDownloadTest extends TestCase
{
    use RefreshDatabase;

    private Gym $gymA;

    private Gym $gymB;

    private Member $memberA;

    private Invoice $invoiceA;

    private User $staffA;

    private User $ownerB;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->gymA = Gym::factory()->create(['code' => 'FZ']);
        $this->gymB = Gym::factory()->create(['code' => 'PH']);

        $this->memberA = $this->makeActiveMember($this->gymA, 'FZ-0001');

        $package = Package::factory()->create([
            'gym_id' => $this->gymA->id,
            'name' => 'Gói thể hình 1 tháng',
            'price' => 500000,
            'duration_days' => 30,
            'pt_sessions' => 4,
            'is_active' => true,
        ]);

        $membership = app(MembershipService::class)->create($this->memberA, $package, null);
        $payment = app(PaymentService::class)->create($membership);

        $this->staffA = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_STAFF]);
        $this->ownerB = User::factory()->create(['gym_id' => $this->gymB->id, 'role' => User::ROLE_GYM_OWNER]);

        $confirmed = app(PaymentService::class)->confirm($payment, $this->staffA);
        $this->invoiceA = $confirmed->invoice()->firstOrFail();
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

    public function test_generated_pdf_is_a_real_file_with_content(): void
    {
        $path = app(InvoiceService::class)->ensureStored($this->invoiceA);

        Storage::disk('local')->assertExists($path);
        $this->assertGreaterThan(0, Storage::disk('local')->size($path));
        $this->assertSame($path, $this->invoiceA->fresh()->pdf_path);
    }

    public function test_reusing_download_does_not_regenerate_file(): void
    {
        $service = app(InvoiceService::class);

        $path = $service->ensureStored($this->invoiceA);
        $firstModifiedAt = Storage::disk('local')->lastModified($path);

        $again = $service->ensureStored($this->invoiceA->fresh());

        $this->assertSame($path, $again);
        $this->assertSame($firstModifiedAt, Storage::disk('local')->lastModified($path));
    }

    public function test_staff_can_download_invoice_of_own_gym(): void
    {
        $response = $this->actingAs($this->staffA)
            ->get(route('gym.invoices.download', $this->invoiceA));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');

        Storage::disk('local')->assertExists($this->invoiceA->fresh()->pdf_path);
    }

    public function test_member_can_download_own_invoice(): void
    {
        $response = $this->actingAs($this->memberA->user)
            ->get(route('member.invoices.download', $this->invoiceA));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_member_cannot_download_another_members_invoice(): void
    {
        $otherMember = $this->makeActiveMember($this->gymA, 'FZ-0002');

        $this->actingAs($otherMember->user)
            ->get(route('member.invoices.download', $this->invoiceA))
            ->assertForbidden();
    }

    public function test_cross_tenant_download_returns_404(): void
    {
        $this->actingAs($this->ownerB)
            ->get(route('gym.invoices.download', $this->invoiceA))
            ->assertNotFound();
    }
}
