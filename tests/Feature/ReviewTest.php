<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\Member;
use App\Models\Review;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private Gym $gymA;

    private Gym $gymB;

    private User $memberA;

    private Member $memberProfileA;

    private User $ownerA;

    private Trainer $trainerA;

    private Trainer $trainerA2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gymA = Gym::factory()->create(['code' => 'FZ']);
        $this->gymB = Gym::factory()->create(['code' => 'PH']);

        $this->memberA = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_MEMBER]);
        $this->memberProfileA = Member::create([
            'gym_id' => $this->gymA->id, 'user_id' => $this->memberA->id,
            'member_code' => 'FZ-0001', 'status' => Member::STATUS_ACTIVE,
        ]);

        $this->ownerA = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_GYM_OWNER]);

        $trainerUserA = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_TRAINER]);
        $this->trainerA = Trainer::create(['gym_id' => $this->gymA->id, 'user_id' => $trainerUserA->id, 'is_active' => true]);

        $trainerUserA2 = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_TRAINER]);
        $this->trainerA2 = Trainer::create(['gym_id' => $this->gymA->id, 'user_id' => $trainerUserA2->id, 'is_active' => true]);
    }

    // Rule: member review Gym (không chọn trainer).
    public function test_member_can_review_the_gym(): void
    {
        $this->actingAs($this->memberA)
            ->post(route('reviews.store'), ['rating' => 5, 'comment' => 'Gym rất sạch sẽ'])
            ->assertRedirect(route('reviews.index'));

        $this->assertDatabaseHas('reviews', [
            'member_id' => $this->memberProfileA->id, 'trainer_id' => null, 'rating' => 5,
        ]);
    }

    // Rule: member review 1 Trainer cụ thể.
    public function test_member_can_review_a_trainer(): void
    {
        $this->actingAs($this->memberA)
            ->post(route('reviews.store'), ['trainer_id' => $this->trainerA->id, 'rating' => 4, 'comment' => 'PT nhiệt tình'])
            ->assertRedirect(route('reviews.index'));

        $this->assertDatabaseHas('reviews', [
            'member_id' => $this->memberProfileA->id, 'trainer_id' => $this->trainerA->id, 'rating' => 4,
        ]);
    }

    // Rule: rating phải trong khoảng 1-5.
    public function test_rating_must_be_between_1_and_5(): void
    {
        $this->actingAs($this->memberA)
            ->post(route('reviews.store'), ['rating' => 0])
            ->assertSessionHasErrors('rating');

        $this->actingAs($this->memberA)
            ->post(route('reviews.store'), ['rating' => 6])
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('reviews', 0);
    }

    // Rule: chỉ Member viết review được — Owner/Staff/Trainer không tự review.
    public function test_only_member_can_create_a_review(): void
    {
        $this->actingAs($this->ownerA)
            ->post(route('reviews.store'), ['rating' => 5])
            ->assertForbidden();

        $this->assertDatabaseCount('reviews', 0);
    }

    // Rule: cross-tenant — không review được trainer thuộc Gym khác.
    public function test_cannot_review_a_trainer_from_another_gym(): void
    {
        $trainerUserB = User::factory()->create(['gym_id' => $this->gymB->id, 'role' => User::ROLE_TRAINER]);
        $trainerB = Trainer::create(['gym_id' => $this->gymB->id, 'user_id' => $trainerUserB->id, 'is_active' => true]);

        $this->actingAs($this->memberA)
            ->post(route('reviews.store'), ['trainer_id' => $trainerB->id, 'rating' => 5])
            ->assertSessionHasErrors('trainer_id');

        $this->assertDatabaseCount('reviews', 0);
    }

    // Rule: Owner ẩn được review không phù hợp, và hiện lại được.
    public function test_owner_can_hide_and_unhide_a_review(): void
    {
        $review = Review::create([
            'gym_id' => $this->gymA->id, 'member_id' => $this->memberProfileA->id,
            'rating' => 1, 'comment' => 'Spam', 'is_visible' => true,
        ]);

        $this->actingAs($this->ownerA)
            ->post(route('reviews.toggle-visibility', $review))
            ->assertRedirect(route('reviews.index'));
        $this->assertFalse($review->fresh()->is_visible);

        $this->actingAs($this->ownerA)
            ->post(route('reviews.toggle-visibility', $review))
            ->assertRedirect(route('reviews.index'));
        $this->assertTrue($review->fresh()->is_visible);
    }

    // Rule: Member không tự kiểm duyệt (ẩn) review được.
    public function test_member_cannot_moderate_a_review(): void
    {
        $review = Review::create([
            'gym_id' => $this->gymA->id, 'member_id' => $this->memberProfileA->id,
            'rating' => 5, 'is_visible' => true,
        ]);

        $this->actingAs($this->memberA)
            ->post(route('reviews.toggle-visibility', $review))
            ->assertForbidden();

        $this->assertTrue($review->fresh()->is_visible);
    }

    // Rule: cross-tenant — Owner Gym khác không kiểm duyệt được review Gym A (404).
    public function test_cross_tenant_review_moderation_returns_404(): void
    {
        $review = Review::create([
            'gym_id' => $this->gymA->id, 'member_id' => $this->memberProfileA->id,
            'rating' => 5, 'is_visible' => true,
        ]);
        $ownerB = User::factory()->create(['gym_id' => $this->gymB->id, 'role' => User::ROLE_GYM_OWNER]);

        $this->actingAs($ownerB)
            ->post(route('reviews.toggle-visibility', $review))
            ->assertNotFound();
    }

    // Rule: Trainer chỉ thấy review VỀ MÌNH, không thấy review của trainer khác hay review chung Gym.
    public function test_trainer_only_sees_reviews_about_self(): void
    {
        Review::create(['gym_id' => $this->gymA->id, 'member_id' => $this->memberProfileA->id, 'trainer_id' => $this->trainerA->id, 'rating' => 5, 'comment' => 'Về trainerA', 'is_visible' => true]);
        Review::create(['gym_id' => $this->gymA->id, 'member_id' => $this->memberProfileA->id, 'trainer_id' => $this->trainerA2->id, 'rating' => 4, 'comment' => 'Về trainerA2', 'is_visible' => true]);
        Review::create(['gym_id' => $this->gymA->id, 'member_id' => $this->memberProfileA->id, 'trainer_id' => null, 'rating' => 3, 'comment' => 'Về Gym chung', 'is_visible' => true]);

        $response = $this->actingAs($this->trainerA->user)->get(route('reviews.index'));

        $response->assertOk();
        $response->assertSee('Về trainerA', false);
        $response->assertDontSee('Về trainerA2', false);
        $response->assertDontSee('Về Gym chung', false);
    }

    // Rule: review bị ẩn không xuất hiện trong danh sách công khai của Member, nhưng vẫn thấy được với Owner/Staff.
    public function test_hidden_review_is_excluded_from_public_list_but_visible_to_moderators(): void
    {
        $review = Review::create([
            'gym_id' => $this->gymA->id, 'member_id' => $this->memberProfileA->id,
            'rating' => 1, 'comment' => 'Nội dung không phù hợp', 'is_visible' => false,
        ]);

        $otherMember = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_MEMBER]);
        $memberResponse = $this->actingAs($otherMember)->get(route('reviews.index'));
        $memberResponse->assertDontSee('Nội dung không phù hợp', false);

        $ownerResponse = $this->actingAs($this->ownerA)->get(route('reviews.index'));
        $ownerResponse->assertSee('Nội dung không phù hợp', false);
    }
}
