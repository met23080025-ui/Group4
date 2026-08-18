<?php

namespace Tests\Feature\Auth;

use App\Models\Gym;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        // role/gym_id đặt tường minh: đăng nhập redirect theo role (dashboardPath()),
        // không còn dùng route('dashboard') chung như Breeze mặc định.
        $user = User::factory()->create(['role' => User::ROLE_MEMBER, 'gym_id' => null]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/home');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_user_of_inactive_gym_cannot_login(): void
    {
        $gym = Gym::factory()->create(['is_active' => false]);
        $user = User::factory()->create([
            'role' => User::ROLE_GYM_OWNER,
            'gym_id' => $gym->id,
            'is_active' => true,
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_platform_admin_can_login_even_without_gym(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_PLATFORM_ADMIN, 'gym_id' => null]);

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/admin');
    }

    public function test_last_login_at_is_updated_on_successful_login(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_MEMBER, 'gym_id' => null]);
        $this->assertNull($user->last_login_at);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertNotNull($user->fresh()->last_login_at);
    }
}
