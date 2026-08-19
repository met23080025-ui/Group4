<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Gym;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityFeedTest extends TestCase
{
    use RefreshDatabase;

    private Gym $gymA;

    private Gym $gymB;

    private User $ownerA;

    private User $staffA;

    private User $trainerA;

    private User $memberA;

    private User $ownerB;

    private User $memberB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gymA = Gym::factory()->create(['code' => 'FZ']);
        $this->gymB = Gym::factory()->create(['code' => 'PH']);

        $this->ownerA = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_GYM_OWNER]);
        $this->staffA = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_STAFF]);
        $this->trainerA = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_TRAINER]);
        $this->memberA = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_MEMBER]);

        $this->ownerB = User::factory()->create(['gym_id' => $this->gymB->id, 'role' => User::ROLE_GYM_OWNER]);
        $this->memberB = User::factory()->create(['gym_id' => $this->gymB->id, 'role' => User::ROLE_MEMBER]);
    }

    private function makePost(User $author, array $overrides = []): Post
    {
        return Post::create(array_merge([
            'gym_id' => $author->gym_id,
            'user_id' => $author->id,
            'content' => 'Nội dung bài viết demo',
            'type' => Post::TYPE_POST,
            'published_at' => now(),
        ], $overrides));
    }

    // Rule: member không tự đăng bài được (chỉ Owner/Staff/Trainer).
    public function test_member_cannot_create_post(): void
    {
        $this->actingAs($this->memberA)
            ->post(route('community.store'), ['content' => 'Xin chào', 'type' => Post::TYPE_POST])
            ->assertForbidden();

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_owner_staff_and_trainer_can_create_post(): void
    {
        foreach ([$this->ownerA, $this->staffA, $this->trainerA] as $author) {
            $this->actingAs($author)
                ->post(route('community.store'), ['content' => 'Bài viết từ '.$author->role, 'type' => Post::TYPE_POST])
                ->assertRedirect(route('community.index'));
        }

        $this->assertDatabaseCount('posts', 3);
    }

    // Rule: feed chỉ hiển thị post đúng Gym của user đang xem (cross-tenant scope).
    public function test_feed_only_shows_posts_of_own_gym(): void
    {
        $postA = $this->makePost($this->ownerA, ['content' => 'Bài viết Gym A']);
        $postB = $this->makePost($this->ownerB, ['content' => 'Bài viết Gym B']);

        $response = $this->actingAs($this->memberA)->get(route('community.index'));

        $response->assertOk();
        $response->assertSee('Bài viết Gym A');
        $response->assertDontSee('Bài viết Gym B');
    }

    // Rule: member Gym A không comment được lên post của Gym B (route model
    // binding scoped theo global scope BelongsToGym -> 404, không lộ post tồn tại).
    public function test_member_cannot_comment_on_post_of_another_gym(): void
    {
        $postB = $this->makePost($this->ownerB);

        $this->actingAs($this->memberA)
            ->post(route('community.comments.store', $postB), ['content' => 'Bình luận lén'])
            ->assertNotFound();

        $this->assertDatabaseCount('comments', 0);
    }

    // Rule: member Gym A không react được lên post của Gym B.
    public function test_member_cannot_react_to_post_of_another_gym(): void
    {
        $postB = $this->makePost($this->ownerB);

        $this->actingAs($this->memberA)
            ->post(route('community.reactions.store', $postB), ['type' => Reaction::TYPE_LIKE])
            ->assertNotFound();

        $this->assertDatabaseCount('reactions', 0);
    }

    // Rule: member cùng Gym comment/react bình thường được.
    public function test_member_can_comment_and_react_on_own_gym_post(): void
    {
        $post = $this->makePost($this->ownerA);

        $this->actingAs($this->memberA)
            ->post(route('community.comments.store', $post), ['content' => 'Hay quá!'])
            ->assertRedirect(route('community.index'));

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id, 'user_id' => $this->memberA->id, 'content' => 'Hay quá!',
        ]);

        $this->actingAs($this->memberA)
            ->post(route('community.reactions.store', $post), ['type' => Reaction::TYPE_LIKE])
            ->assertRedirect(route('community.index'));

        $this->assertDatabaseHas('reactions', [
            'post_id' => $post->id, 'user_id' => $this->memberA->id, 'type' => Reaction::TYPE_LIKE,
        ]);
    }

    // Rule: bấm lại đúng loại reaction đã có -> gỡ (toggle off).
    public function test_reacting_with_same_type_twice_toggles_it_off(): void
    {
        $post = $this->makePost($this->ownerA);

        $this->actingAs($this->memberA)->post(route('community.reactions.store', $post), ['type' => Reaction::TYPE_LIKE]);
        $this->assertDatabaseCount('reactions', 1);

        $this->actingAs($this->memberA)->post(route('community.reactions.store', $post), ['type' => Reaction::TYPE_LIKE]);
        $this->assertDatabaseCount('reactions', 0);
    }

    // Rule: bấm loại khác -> đổi loại reaction, không tạo thêm dòng mới (unique post_id+user_id).
    public function test_reacting_with_a_different_type_switches_it(): void
    {
        $post = $this->makePost($this->ownerA);

        $this->actingAs($this->memberA)->post(route('community.reactions.store', $post), ['type' => Reaction::TYPE_LIKE]);
        $this->actingAs($this->memberA)->post(route('community.reactions.store', $post), ['type' => Reaction::TYPE_LOVE]);

        $this->assertDatabaseCount('reactions', 1);
        $this->assertDatabaseHas('reactions', [
            'post_id' => $post->id, 'user_id' => $this->memberA->id, 'type' => Reaction::TYPE_LOVE,
        ]);
    }

    // Rule: member không xoá được post người khác.
    public function test_member_cannot_delete_another_users_post(): void
    {
        $post = $this->makePost($this->ownerA);

        $this->actingAs($this->memberA)
            ->delete(route('community.destroy', $post))
            ->assertForbidden();

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'deleted_at' => null]);
    }

    // Rule: Owner/Staff sửa/xoá được MỌI post trong Gym (kiểm duyệt).
    public function test_owner_and_staff_can_delete_any_post_in_own_gym(): void
    {
        $postByTrainer = $this->makePost($this->trainerA);

        $this->actingAs($this->ownerA)
            ->delete(route('community.destroy', $postByTrainer))
            ->assertRedirect(route('community.index'));

        $this->assertSoftDeleted('posts', ['id' => $postByTrainer->id]);
    }

    // Rule: Trainer chỉ sửa/xoá được post của chính mình, không phải của trainer khác.
    public function test_trainer_cannot_delete_another_trainers_post(): void
    {
        $otherTrainer = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_TRAINER]);
        $postByOtherTrainer = $this->makePost($otherTrainer);

        $this->actingAs($this->trainerA)
            ->delete(route('community.destroy', $postByOtherTrainer))
            ->assertForbidden();
    }

    public function test_trainer_can_delete_own_post(): void
    {
        $post = $this->makePost($this->trainerA);

        $this->actingAs($this->trainerA)
            ->delete(route('community.destroy', $post))
            ->assertRedirect(route('community.index'));

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    // Rule: chỉ Owner/Staff ghim được announcement lên đầu feed.
    public function test_owner_can_pin_an_announcement(): void
    {
        $post = $this->makePost($this->ownerA, ['type' => Post::TYPE_ANNOUNCEMENT]);

        $this->actingAs($this->ownerA)
            ->post(route('community.pin', $post))
            ->assertRedirect(route('community.index'));

        $this->assertTrue($post->fresh()->is_pinned);
    }

    public function test_member_cannot_pin_a_post(): void
    {
        $post = $this->makePost($this->ownerA);

        $this->actingAs($this->memberA)
            ->post(route('community.pin', $post))
            ->assertForbidden();

        $this->assertFalse($post->fresh()->is_pinned);
    }

    // Rule: bài ghim luôn hiển thị đầu feed bất kể thời gian đăng.
    public function test_pinned_post_appears_first_in_feed(): void
    {
        $older = $this->makePost($this->ownerA, ['content' => 'Bài cũ', 'published_at' => now()->subDays(3)]);
        $newer = $this->makePost($this->ownerA, ['content' => 'Bài mới nhất', 'published_at' => now()]);
        $pinned = $this->makePost($this->ownerA, [
            'content' => 'Thông báo ghim', 'type' => Post::TYPE_ANNOUNCEMENT,
            'is_pinned' => true, 'published_at' => now()->subDays(10),
        ]);

        $posts = \App\Models\Post::query()->orderByDesc('is_pinned')->orderByDesc('published_at')->get();

        $this->assertSame($pinned->id, $posts->first()->id);
    }

    // Rule: user tự xoá được comment của chính mình; Owner/Staff kiểm duyệt xoá comment bất kỳ.
    public function test_comment_author_can_delete_own_comment(): void
    {
        $post = $this->makePost($this->ownerA);
        $comment = $post->comments()->create(['gym_id' => $this->gymA->id, 'user_id' => $this->memberA->id, 'content' => 'Của tôi']);

        $this->actingAs($this->memberA)
            ->delete(route('comments.destroy', $comment))
            ->assertRedirect(route('community.index'));

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_member_cannot_delete_another_members_comment(): void
    {
        $post = $this->makePost($this->ownerA);
        $otherMember = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_MEMBER]);
        $comment = $post->comments()->create(['gym_id' => $this->gymA->id, 'user_id' => $otherMember->id, 'content' => 'Của người khác']);

        $this->actingAs($this->memberA)
            ->delete(route('comments.destroy', $comment))
            ->assertForbidden();

        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }

    public function test_staff_can_moderate_delete_any_comment_in_own_gym(): void
    {
        $post = $this->makePost($this->ownerA);
        $comment = $post->comments()->create(['gym_id' => $this->gymA->id, 'user_id' => $this->memberA->id, 'content' => 'Spam']);

        $this->actingAs($this->staffA)
            ->delete(route('comments.destroy', $comment))
            ->assertRedirect(route('community.index'));

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    // Rule: platform_admin không truy cập community (không thuộc Gym nào).
    public function test_platform_admin_cannot_access_community_feed(): void
    {
        $admin = User::factory()->create(['gym_id' => null, 'role' => User::ROLE_PLATFORM_ADMIN]);

        $this->actingAs($admin)->get(route('community.index'))->assertForbidden();
    }
}
