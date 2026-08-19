<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\Membership;
use App\Models\Notification;
use App\Models\Package;
use App\Models\Post;
use App\Models\Schedule;
use App\Models\User;
use App\Services\ClassBookingService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private Gym $gymA;

    private Gym $gymB;

    private User $memberA;

    private User $staffA;

    private User $memberB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gymA = Gym::factory()->create(['code' => 'FZ']);
        $this->gymB = Gym::factory()->create(['code' => 'PH']);

        $this->memberA = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_MEMBER]);
        $this->staffA = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_STAFF]);
        $this->memberB = User::factory()->create(['gym_id' => $this->gymB->id, 'role' => User::ROLE_MEMBER]);
    }

    private function notify(User $user, string $type = Notification::TYPE_NEW_ANNOUNCEMENT): Notification
    {
        return app(NotificationService::class)->notify($user, $type, 'Tiêu đề demo', 'Nội dung demo');
    }

    // Rule: user chỉ thấy thông báo của chính mình + đúng Gym.
    public function test_user_only_sees_own_notifications_in_index(): void
    {
        $mine = $this->notify($this->memberA);
        $others = $this->notify($this->staffA);

        $response = $this->actingAs($this->memberA)->get(route('notifications.index'));

        $response->assertOk();
        $response->assertSee('Tiêu đề demo');
        $response->assertViewHas('notifications', function ($notifications) use ($mine, $others) {
            $ids = $notifications->pluck('id')->all();

            return in_array($mine->id, $ids, true) && ! in_array($others->id, $ids, true);
        });
    }

    // Rule: cross-tenant — user Gym khác không đánh dấu đọc được thông báo của user Gym A (404).
    public function test_cross_tenant_user_cannot_mark_another_gyms_notification_as_read(): void
    {
        $notification = $this->notify($this->memberA);

        $this->actingAs($this->memberB)
            ->post(route('notifications.read', $notification))
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    // Rule: cùng Gym nhưng KHÁC user cũng không đánh dấu đọc hộ được nhau (403).
    public function test_user_in_same_gym_cannot_mark_another_users_notification_as_read(): void
    {
        $notification = $this->notify($this->memberA);

        $this->actingAs($this->staffA)
            ->post(route('notifications.read', $notification))
            ->assertForbidden();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_owner_of_notification_can_mark_it_read(): void
    {
        $notification = $this->notify($this->memberA);

        $this->actingAs($this->memberA)
            ->post(route('notifications.read', $notification))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    // Rule: đánh dấu tất cả đã đọc chỉ ảnh hưởng thông báo CỦA CHÍNH MÌNH.
    public function test_mark_all_read_only_affects_own_notifications(): void
    {
        $mine1 = $this->notify($this->memberA);
        $mine2 = $this->notify($this->memberA);
        $others = $this->notify($this->staffA);

        $this->actingAs($this->memberA)->post(route('notifications.read-all'));

        $this->assertNotNull($mine1->fresh()->read_at);
        $this->assertNotNull($mine2->fresh()->read_at);
        $this->assertNull($others->fresh()->read_at);
    }

    // Trigger: đặt lớp thành công -> member nhận thông báo.
    public function test_booking_a_class_notifies_the_member(): void
    {
        $member = $this->makeMemberWithActiveMembership($this->gymA);
        $trainer = \App\Models\Trainer::factory()->create(['gym_id' => $this->gymA->id]);
        $schedule = Schedule::factory()->create([
            'gym_id' => $this->gymA->id, 'trainer_id' => $trainer->id,
            'class_date' => now()->addDay()->toDateString(), 'capacity' => 10,
        ]);

        app(ClassBookingService::class)->book($member, $schedule);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $member->user_id,
            'type' => Notification::TYPE_CLASS_BOOKED,
        ]);
    }

    // Trigger: huỷ lớp -> member nhận thông báo huỷ.
    public function test_cancelling_a_booking_notifies_the_member(): void
    {
        $member = $this->makeMemberWithActiveMembership($this->gymA);
        $trainer = \App\Models\Trainer::factory()->create(['gym_id' => $this->gymA->id]);
        $schedule = Schedule::factory()->create([
            'gym_id' => $this->gymA->id, 'trainer_id' => $trainer->id,
            'class_date' => now()->addDay()->toDateString(), 'capacity' => 10,
        ]);
        $booking = app(ClassBookingService::class)->book($member, $schedule);

        app(ClassBookingService::class)->cancel($booking);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $member->user_id,
            'type' => Notification::TYPE_CLASS_CANCELLED,
        ]);
    }

    // Trigger: comment mới -> báo cho tác giả bài viết (trừ tự comment vào bài của mình).
    public function test_new_comment_notifies_post_author_but_not_when_commenting_on_own_post(): void
    {
        $post = Post::create([
            'gym_id' => $this->gymA->id, 'user_id' => $this->staffA->id,
            'content' => 'Bài viết', 'type' => Post::TYPE_POST, 'published_at' => now(),
        ]);

        $this->actingAs($this->memberA)->post(route('community.comments.store', $post), ['content' => 'Hay!']);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->staffA->id,
            'type' => Notification::TYPE_NEW_COMMENT,
        ]);

        $this->assertDatabaseCount('notifications', 1);

        // Tác giả tự comment vào bài của mình -> không tự thông báo cho chính mình.
        $this->actingAs($this->staffA)->post(route('community.comments.store', $post), ['content' => 'Cảm ơn mọi người']);

        $this->assertDatabaseCount('notifications', 1);
    }

    // Trigger: announcement mới -> broadcast cho mọi user khác trong Gym, không tự báo cho tác giả.
    public function test_new_announcement_notifies_all_other_gym_users(): void
    {
        $ownerA = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_GYM_OWNER]);

        $this->actingAs($ownerA)->post(route('community.store'), [
            'content' => 'Gym nghỉ lễ', 'type' => Post::TYPE_ANNOUNCEMENT,
        ]);

        // 2 user khác trong Gym A (memberA, staffA) nhận được, ownerA (tác giả) và memberB (Gym khác) thì không.
        $this->assertDatabaseHas('notifications', ['user_id' => $this->memberA->id, 'type' => Notification::TYPE_NEW_ANNOUNCEMENT]);
        $this->assertDatabaseHas('notifications', ['user_id' => $this->staffA->id, 'type' => Notification::TYPE_NEW_ANNOUNCEMENT]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $ownerA->id]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $this->memberB->id]);
    }

    // Trigger: membership sắp hết hạn -> chỉ thông báo membership trong khoảng ngày, không lặp lại trong cùng ngày.
    public function test_notify_expiring_memberships_is_scoped_and_idempotent_same_day(): void
    {
        $expiringSoon = $this->makeMemberWithActiveMembership($this->gymA, endDate: now()->addDays(2)->toDateString());
        $expiringLate = $this->makeMemberWithActiveMembership($this->gymA, endDate: now()->addDays(30)->toDateString());

        $count = app(NotificationService::class)->notifyExpiringMemberships(3);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $expiringSoon->user_id, 'type' => Notification::TYPE_MEMBERSHIP_EXPIRING,
        ]);
        $this->assertDatabaseMissing('notifications', ['user_id' => $expiringLate->user_id]);

        // Chạy lại trong cùng ngày -> không tạo thêm thông báo trùng.
        $secondRun = app(NotificationService::class)->notifyExpiringMemberships(3);
        $this->assertSame(0, $secondRun);
        $this->assertSame(1, Notification::where('user_id', $expiringSoon->user_id)->count());
    }

    private function makeMemberWithActiveMembership(Gym $gym, ?string $endDate = null): \App\Models\Member
    {
        $user = User::factory()->create(['gym_id' => $gym->id, 'role' => User::ROLE_MEMBER]);
        $member = \App\Models\Member::create([
            'gym_id' => $gym->id, 'user_id' => $user->id,
            'member_code' => 'MB-'.$user->id, 'status' => \App\Models\Member::STATUS_ACTIVE,
        ]);
        $package = Package::factory()->create(['gym_id' => $gym->id]);

        Membership::create([
            'gym_id' => $gym->id, 'member_id' => $member->id, 'package_id' => $package->id,
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => $endDate ?? now()->addDays(25)->toDateString(),
            'original_price' => $package->price, 'discount_amount' => 0, 'final_price' => $package->price,
            'status' => Membership::STATUS_ACTIVE,
        ]);

        return $member;
    }
}
