<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberManagementTest extends TestCase
{
    use RefreshDatabase;

    private Gym $gymA;

    private Gym $gymB;

    private User $ownerA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gymA = Gym::factory()->create(['name' => 'FitZone Hoan Kiem', 'code' => 'FZ']);
        $this->gymB = Gym::factory()->create(['name' => 'PowerHouse Hanoi', 'code' => 'PH']);

        $this->ownerA = User::factory()->create([
            'gym_id' => $this->gymA->id,
            'role' => User::ROLE_GYM_OWNER,
        ]);
    }

    private function createMemberFor(Gym $gym, string $code, array $overrides = []): Member
    {
        $user = User::factory()->create(array_merge([
            'gym_id' => $gym->id,
            'role' => User::ROLE_MEMBER,
        ], $overrides['user'] ?? []));

        return Member::create(array_merge([
            'gym_id' => $gym->id,
            'user_id' => $user->id,
            'member_code' => $code,
            'status' => Member::STATUS_ACTIVE,
            'joined_at' => now()->toDateString(),
        ], $overrides['member'] ?? []));
    }

    public function test_creating_a_member_generates_sequential_code_per_gym(): void
    {
        $response = $this->actingAs($this->ownerA)->post('/gym/members', [
            'name' => 'Nguyen Van A',
            'email' => 'nguyenvana@fitzone.test',
            'phone' => '0900000001',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('members', ['gym_id' => $this->gymA->id, 'member_code' => 'FZ-0001']);

        // Tạo tiếp 1 member nữa -> phải tiếp tục đúng số thứ tự, không trùng.
        $this->actingAs($this->ownerA)->post('/gym/members', [
            'name' => 'Nguyen Van B',
            'email' => 'nguyenvanb@fitzone.test',
        ]);

        $this->assertDatabaseHas('members', ['gym_id' => $this->gymA->id, 'member_code' => 'FZ-0002']);

        $user = User::where('email', 'nguyenvana@fitzone.test')->first();
        $this->assertSame(User::ROLE_MEMBER, $user->role);
        $this->assertSame($this->gymA->id, $user->gym_id);
    }

    public function test_member_code_does_not_collide_when_gym_already_has_members(): void
    {
        $this->createMemberFor($this->gymA, 'FZ-0001');
        $this->createMemberFor($this->gymA, 'FZ-0002');

        $this->actingAs($this->ownerA)->post('/gym/members', [
            'name' => 'Nguyen Van C',
            'email' => 'nguyenvanc@fitzone.test',
        ]);

        $this->assertDatabaseHas('members', ['gym_id' => $this->gymA->id, 'member_code' => 'FZ-0003']);
    }

    public function test_index_search_filters_by_name_email_code_and_phone(): void
    {
        $this->createMemberFor($this->gymA, 'FZ-0001', ['user' => ['name' => 'Tran Thi Mai', 'email' => 'mai@fitzone.test', 'phone' => '0911111111']]);
        $this->createMemberFor($this->gymA, 'FZ-0002', ['user' => ['name' => 'Le Van Hung', 'email' => 'hung@fitzone.test', 'phone' => '0922222222']]);

        $response = $this->actingAs($this->ownerA)->get('/gym/members?search=Mai');
        $response->assertOk();
        $response->assertSee('Tran Thi Mai');
        $response->assertDontSee('Le Van Hung');

        $response = $this->actingAs($this->ownerA)->get('/gym/members?search=FZ-0002');
        $response->assertSee('Le Van Hung');
        $response->assertDontSee('Tran Thi Mai');

        $response = $this->actingAs($this->ownerA)->get('/gym/members?search=0911111111');
        $response->assertSee('Tran Thi Mai');
        $response->assertDontSee('Le Van Hung');
    }

    public function test_index_filters_by_status(): void
    {
        $this->createMemberFor($this->gymA, 'FZ-0001', ['user' => ['name' => 'Active Member'], 'member' => ['status' => Member::STATUS_ACTIVE]]);
        $this->createMemberFor($this->gymA, 'FZ-0002', ['user' => ['name' => 'Blocked Member'], 'member' => ['status' => Member::STATUS_BLOCKED]]);

        $response = $this->actingAs($this->ownerA)->get('/gym/members?status=blocked');

        $response->assertSee('Blocked Member');
        $response->assertDontSee('Active Member');
    }

    public function test_search_does_not_leak_members_from_other_gym(): void
    {
        $this->createMemberFor($this->gymA, 'FZ-0001', ['user' => ['name' => 'Member Of A']]);
        $this->createMemberFor($this->gymB, 'PH-0001', ['user' => ['name' => 'Member Of B']]);

        $response = $this->actingAs($this->ownerA)->get('/gym/members');

        $response->assertSee('Member Of A');
        $response->assertDontSee('Member Of B');
    }

    public function test_owner_can_soft_delete_then_restore_a_member(): void
    {
        $member = $this->createMemberFor($this->gymA, 'FZ-0001');

        $this->actingAs($this->ownerA)->delete("/gym/members/{$member->id}")->assertRedirect('/gym/members');
        $this->assertSoftDeleted('members', ['id' => $member->id]);

        // Member đã bị xóa mềm -> không còn xuất hiện trong danh sách chính.
        // (request riêng để không dính flash message "Đã vô hiệu hóa hội viên FZ-0001" của lần xóa)
        $this->actingAs($this->ownerA)->get('/gym/members')->assertOk();
        $this->actingAs($this->ownerA)
            ->get('/gym/members')
            ->assertSee('Chưa có hội viên nào khớp bộ lọc.');

        // Nhưng có trong Thùng rác.
        $this->actingAs($this->ownerA)->get('/gym/members/trashed')->assertSee($member->member_code);

        $this->actingAs($this->ownerA)->post("/gym/members/{$member->id}/restore")->assertRedirect('/gym/members');
        $this->assertDatabaseHas('members', ['id' => $member->id, 'deleted_at' => null]);
    }

    public function test_editing_member_of_another_gym_returns_404(): void
    {
        $memberOfB = $this->createMemberFor($this->gymB, 'PH-0001');

        $this->actingAs($this->ownerA)->get("/gym/members/{$memberOfB->id}/edit")->assertNotFound();

        $this->actingAs($this->ownerA)->put("/gym/members/{$memberOfB->id}", [
            'name' => 'Hacked Name',
            'email' => 'hacked@test.local',
            'status' => Member::STATUS_ACTIVE,
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'hacked@test.local']);
    }

    public function test_member_role_cannot_manage_members(): void
    {
        $memberUser = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_MEMBER]);

        $this->actingAs($memberUser)->get('/gym/members')->assertForbidden();
    }
}
