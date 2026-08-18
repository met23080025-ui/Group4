<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bằng chứng bảo mật multi-tenant quan trọng nhất của đồ án:
 * role middleware trả 403 đúng chỗ, và route model binding (nhờ global scope
 * BelongsToGym) trả 404 khi truy cập chéo tenant, ngay cả khi user có đúng role.
 *
 * Dùng User::factory() thay vì User::create() trực tiếp: 'email_verified_at'
 * không nằm trong #[Fillable] của User (cố ý, để không cho form nào tự set
 * qua mass-assignment) nên User::create() sẽ âm thầm bỏ qua field này — mọi
 * route ở đây có middleware 'verified' nên user test bắt buộc phải verified.
 * Factory set sẵn 'email_verified_at' => now() và không bị fillable-guard.
 */
class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    private Gym $gymA;

    private Gym $gymB;

    private Member $memberOfGymB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gymA = Gym::create(['name' => 'FitZone Hoan Kiem', 'slug' => 'fitzone-hoan-kiem', 'is_active' => true]);
        $this->gymB = Gym::create(['name' => 'PowerHouse Hanoi', 'slug' => 'powerhouse-hanoi', 'is_active' => true]);

        $userB = User::factory()->create([
            'gym_id' => $this->gymB->id,
            'name' => 'Member B1',
            'email' => 'memberb1@powerhouse.test',
            'role' => User::ROLE_MEMBER,
        ]);

        $this->memberOfGymB = Member::create([
            'gym_id' => $this->gymB->id,
            'user_id' => $userB->id,
            'member_code' => 'PH-0001',
            'status' => Member::STATUS_ACTIVE,
        ]);
    }

    private function ownerOf(Gym $gym): User
    {
        return User::factory()->create([
            'gym_id' => $gym->id,
            'name' => 'Owner of '.$gym->name,
            'email' => 'owner-'.$gym->id.'@test.local',
            'role' => User::ROLE_GYM_OWNER,
        ]);
    }

    // (a) Member gõ /gym/members -> 403 (role middleware, không phải redirect login).
    public function test_member_gets_403_on_gym_members_route(): void
    {
        $member = User::factory()->create([
            'gym_id' => $this->gymA->id,
            'name' => 'Member A1',
            'email' => 'membera1@fitzone.test',
            'role' => User::ROLE_MEMBER,
        ]);

        $response = $this->actingAs($member)->get('/gym/members');

        $response->assertForbidden();
    }

    // (b) Owner Gym A gõ URL resource của Gym B -> 404 nhờ global scope BelongsToGym
    // (route model binding không tìm thấy bản ghi vì đã bị lọc theo gym_id trước đó).
    public function test_owner_of_gym_a_gets_404_when_accessing_member_of_gym_b(): void
    {
        $ownerA = $this->ownerOf($this->gymA);

        $response = $this->actingAs($ownerA)->get('/gym/members/'.$this->memberOfGymB->id);

        $response->assertNotFound();
    }

    // (c) Owner Gym A vào đúng dashboard của mình -> 200.
    public function test_owner_can_access_own_gym_dashboard(): void
    {
        $ownerA = $this->ownerOf($this->gymA);

        $response = $this->actingAs($ownerA)->get('/gym/dashboard');

        $response->assertOk();
    }

    public function test_staff_can_access_gym_members_but_not_gym_dashboard(): void
    {
        $staff = User::factory()->create([
            'gym_id' => $this->gymA->id,
            'name' => 'Staff A1',
            'email' => 'staffa1@fitzone.test',
            'role' => User::ROLE_STAFF,
        ]);

        $this->actingAs($staff)->get('/gym/members')->assertOk();
        $this->actingAs($staff)->get('/gym/dashboard')->assertForbidden();
    }

    public function test_gym_owner_gets_403_on_platform_admin_routes(): void
    {
        $ownerA = $this->ownerOf($this->gymA);

        $response = $this->actingAs($ownerA)->get('/admin');

        $response->assertForbidden();
    }

    public function test_platform_admin_can_access_any_gym_member(): void
    {
        $admin = User::factory()->create([
            'gym_id' => null,
            'name' => 'Platform Admin',
            'email' => 'admin@gymhub.test',
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);

        // Platform admin không thuộc gym nào nên KHÔNG đi qua route /gym/* (role:gym_owner,staff),
        // nhưng Policy vẫn phải cho true qua before() nếu được gọi trực tiếp.
        $this->assertTrue($admin->can('view', $this->memberOfGymB));
        $this->assertTrue($admin->can('viewAny', Member::class));
    }

    public function test_guest_is_redirected_to_login_not_403(): void
    {
        $response = $this->get('/gym/members');

        $response->assertRedirect('/login');
    }
}
