<?php

namespace Tests\Feature;

use App\Models\Gym;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantScopeTest extends TestCase
{
    use RefreshDatabase;

    private Gym $gymA;

    private Gym $gymB;

    private Member $memberA1;

    private Member $memberA2;

    private Member $memberB1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gymA = Gym::create([
            'name' => 'FitZone Hoan Kiem', 'slug' => 'fitzone-hoan-kiem', 'is_active' => true,
        ]);
        $this->gymB = Gym::create([
            'name' => 'PowerHouse Hanoi', 'slug' => 'powerhouse-hanoi', 'is_active' => true,
        ]);

        $this->memberA1 = $this->makeMember($this->gymA, 'member-a1@fitzone.test', 'FZ-0001');
        $this->memberA2 = $this->makeMember($this->gymA, 'member-a2@fitzone.test', 'FZ-0002');
        $this->memberB1 = $this->makeMember($this->gymB, 'member-b1@powerhouse.test', 'PH-0001');
    }

    private function makeMember(Gym $gym, string $email, string $memberCode): Member
    {
        $user = User::create([
            'gym_id' => $gym->id,
            'name' => $email,
            'email' => $email,
            'password' => 'password',
            'role' => User::ROLE_MEMBER,
        ]);

        return Member::create([
            'gym_id' => $gym->id,
            'user_id' => $user->id,
            'member_code' => $memberCode,
            'status' => Member::STATUS_ACTIVE,
        ]);
    }

    public function test_user_of_gym_a_only_sees_members_of_gym_a(): void
    {
        $ownerA = User::create([
            'gym_id' => $this->gymA->id,
            'name' => 'Owner FitZone',
            'email' => 'owner@fitzone.test',
            'password' => 'password',
            'role' => User::ROLE_GYM_OWNER,
        ]);

        $this->actingAs($ownerA);

        $members = Member::all();

        $this->assertCount(2, $members);
        $this->assertTrue($members->every(fn (Member $m) => $m->gym_id === $this->gymA->id));
        $this->assertFalse($members->contains('id', $this->memberB1->id));
    }

    public function test_user_of_gym_b_only_sees_members_of_gym_b(): void
    {
        $ownerB = User::create([
            'gym_id' => $this->gymB->id,
            'name' => 'Owner PowerHouse',
            'email' => 'owner@powerhouse.test',
            'password' => 'password',
            'role' => User::ROLE_GYM_OWNER,
        ]);

        $this->actingAs($ownerB);

        $members = Member::all();

        $this->assertCount(1, $members);
        $this->assertSame($this->memberB1->id, $members->first()->id);
    }

    public function test_platform_admin_sees_members_of_all_gyms(): void
    {
        $admin = User::create([
            'gym_id' => null,
            'name' => 'Platform Admin',
            'email' => 'admin@gymhub.test',
            'password' => 'password',
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);

        $this->actingAs($admin);

        $members = Member::all();

        $this->assertCount(3, $members);
    }

    public function test_guest_or_cli_context_is_not_filtered_so_seeders_work(): void
    {
        // Không gọi actingAs(): mô phỏng CLI/seeder — không có tenant context.
        $members = Member::all();

        $this->assertCount(3, $members);
    }

    public function test_cross_tenant_find_returns_null_for_route_model_binding(): void
    {
        $ownerA = User::create([
            'gym_id' => $this->gymA->id,
            'name' => 'Owner FitZone 2',
            'email' => 'owner2@fitzone.test',
            'password' => 'password',
            'role' => User::ROLE_GYM_OWNER,
        ]);

        $this->actingAs($ownerA);

        $found = Member::find($this->memberB1->id);

        $this->assertNull($found);
    }
}
