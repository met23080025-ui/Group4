<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Package;
use App\Models\Post;
use App\Models\Review;
use App\Models\Schedule;
use App\Models\Trainer;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Bằng chứng bảo mật multi-tenant quan trọng nhất (mục 27, Khối 5 Ngày 3):
 * Owner Gym A đăng nhập, cố truy cập MỌI loại resource thuộc Gym B (member,
 * payment, invoice, schedule, post, review, equipment) — tất cả phải bị
 * chặn (403/404), không có ngoại lệ. Mỗi resource type là 1 test riêng để
 * biết chính xác loại nào hỏng nếu có test đỏ.
 *
 * Cơ chế chặn thực tế (không phải test tự bịa ra hành vi): mọi model liên
 * quan dùng trait BelongsToGym -> route model binding tự trả 404 khi ID
 * thuộc Gym khác (global scope 'gym' lọc theo gym_id của user đang đăng
 * nhập). Không có Policy nào ở đây phải tự so sánh gym_id thủ công vì lớp
 * global scope đã chặn TRƯỚC KHI Policy kịp chạy.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Gym $gymA;

    private Gym $gymB;

    private User $ownerA;

    private User $staffB;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->gymA = Gym::factory()->create(['code' => 'FZ']);
        $this->gymB = Gym::factory()->create(['code' => 'PH']);

        $this->ownerA = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_GYM_OWNER]);
        $this->staffB = User::factory()->create(['gym_id' => $this->gymB->id, 'role' => User::ROLE_STAFF]);
    }

    private function makeMemberB(): Member
    {
        $user = User::factory()->create(['gym_id' => $this->gymB->id, 'role' => User::ROLE_MEMBER]);

        return Member::create([
            'gym_id' => $this->gymB->id, 'user_id' => $user->id,
            'member_code' => 'PH-0001', 'status' => Member::STATUS_ACTIVE,
        ]);
    }

    // 1. Member: Owner Gym A không xem/sửa/xoá được member Gym B.
    public function test_cannot_access_another_gyms_member(): void
    {
        $memberB = $this->makeMemberB();

        $this->actingAs($this->ownerA)->get(route('gym.members.show', $memberB))->assertNotFound();
        $this->actingAs($this->ownerA)->get(route('gym.members.edit', $memberB))->assertNotFound();
        $this->actingAs($this->ownerA)
            ->put(route('gym.members.update', $memberB), ['name' => 'x', 'email' => 'x@x.com', 'status' => Member::STATUS_ACTIVE])
            ->assertNotFound();
        $this->actingAs($this->ownerA)->delete(route('gym.members.destroy', $memberB))->assertNotFound();
    }

    // 2 & 3. Payment + Invoice: Owner Gym A không xem/xác nhận thanh toán, không tải hóa đơn của Gym B.
    public function test_cannot_access_another_gyms_payment_and_invoice(): void
    {
        $memberB = $this->makeMemberB();
        $package = Package::factory()->create(['gym_id' => $this->gymB->id, 'price' => 500000, 'duration_days' => 30]);
        $membership = app(MembershipService::class)->create($memberB, $package, null);
        $payment = app(PaymentService::class)->create($membership);
        $confirmedPayment = app(PaymentService::class)->confirm($payment, $this->staffB);
        $invoice = $confirmedPayment->invoice()->firstOrFail();

        $this->actingAs($this->ownerA)->get(route('gym.payments.show', $confirmedPayment))->assertNotFound();
        $this->actingAs($this->ownerA)->post(route('gym.payments.confirm', $confirmedPayment))->assertNotFound();
        $this->actingAs($this->ownerA)->get(route('gym.invoices.download', $invoice))->assertNotFound();
    }

    // 4. Schedule: Owner Gym A không xem/sửa/xoá lịch tập Gym B.
    public function test_cannot_access_another_gyms_schedule(): void
    {
        $trainerUserB = User::factory()->create(['gym_id' => $this->gymB->id, 'role' => User::ROLE_TRAINER]);
        $trainerB = Trainer::create(['gym_id' => $this->gymB->id, 'user_id' => $trainerUserB->id, 'is_active' => true]);
        $scheduleB = Schedule::factory()->create(['gym_id' => $this->gymB->id, 'trainer_id' => $trainerB->id]);

        $this->actingAs($this->ownerA)->get(route('gym.schedules.show', $scheduleB))->assertNotFound();
        $this->actingAs($this->ownerA)->get(route('gym.schedules.edit', $scheduleB))->assertNotFound();
        $this->actingAs($this->ownerA)
            ->put(route('gym.schedules.update', $scheduleB), ['title' => 'x', 'class_date' => now()->addDay()->toDateString(), 'start_time' => '10:00', 'end_time' => '11:00', 'capacity' => 5, 'status' => Schedule::STATUS_SCHEDULED])
            ->assertNotFound();
        $this->actingAs($this->ownerA)->delete(route('gym.schedules.destroy', $scheduleB))->assertNotFound();
    }

    // 5. Post: Owner Gym A không sửa/xoá/ghim bài viết Gym B.
    public function test_cannot_access_another_gyms_post(): void
    {
        $postB = Post::create([
            'gym_id' => $this->gymB->id, 'user_id' => $this->staffB->id,
            'content' => 'Bài viết Gym B', 'type' => Post::TYPE_POST, 'published_at' => now(),
        ]);

        $this->actingAs($this->ownerA)
            ->put(route('community.update', $postB), ['content' => 'Sửa lén', 'type' => Post::TYPE_POST])
            ->assertNotFound();
        $this->actingAs($this->ownerA)->post(route('community.pin', $postB))->assertNotFound();
        $this->actingAs($this->ownerA)->delete(route('community.destroy', $postB))->assertNotFound();
    }

    // 6. Review: Owner Gym A không kiểm duyệt (ẩn/hiện) review Gym B.
    public function test_cannot_access_another_gyms_review(): void
    {
        $memberB = $this->makeMemberB();
        $reviewB = Review::create([
            'gym_id' => $this->gymB->id, 'member_id' => $memberB->id, 'rating' => 5, 'is_visible' => true,
        ]);

        $this->actingAs($this->ownerA)->post(route('reviews.toggle-visibility', $reviewB))->assertNotFound();
        $this->assertTrue($reviewB->fresh()->is_visible);
    }

    // 7. Equipment: Owner Gym A không xem/sửa/xoá thiết bị Gym B.
    public function test_cannot_access_another_gyms_equipment(): void
    {
        $equipmentB = Equipment::create([
            'gym_id' => $this->gymB->id, 'name' => 'Máy Gym B', 'status' => Equipment::STATUS_ACTIVE,
        ]);

        $this->actingAs($this->ownerA)->get(route('gym.equipment.show', $equipmentB))->assertNotFound();
        $this->actingAs($this->ownerA)->get(route('gym.equipment.edit', $equipmentB))->assertNotFound();
        $this->actingAs($this->ownerA)
            ->put(route('gym.equipment.update', $equipmentB), ['name' => 'x', 'status' => Equipment::STATUS_ACTIVE])
            ->assertNotFound();
        $this->actingAs($this->ownerA)->delete(route('gym.equipment.destroy', $equipmentB))->assertNotFound();
    }

    // Xác nhận KHÔNG có bản ghi nào bị thay đổi/xoá qua các nỗ lực truy cập chéo ở trên.
    public function test_no_cross_tenant_write_ever_succeeds(): void
    {
        $memberB = $this->makeMemberB();
        $equipmentB = Equipment::create(['gym_id' => $this->gymB->id, 'name' => 'Máy Gym B', 'status' => Equipment::STATUS_ACTIVE]);

        $this->actingAs($this->ownerA)->delete(route('gym.members.destroy', $memberB));
        $this->actingAs($this->ownerA)->delete(route('gym.equipment.destroy', $equipmentB));

        $this->assertDatabaseHas('members', ['id' => $memberB->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('equipment', ['id' => $equipmentB->id, 'deleted_at' => null]);
    }
}
