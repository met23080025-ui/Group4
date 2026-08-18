<?php

namespace Tests\Feature\Auth;

use App\Models\Gym;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register_by_selecting_an_active_gym(): void
    {
        $gym = Gym::factory()->create(['is_active' => true]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'gym_id' => $gym->id,
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/home');

        $user = User::where('email', 'test@example.com')->first();
        $this->assertSame(User::ROLE_MEMBER, $user->role);
        $this->assertSame($gym->id, $user->gym_id);
    }

    public function test_registration_requires_an_active_gym(): void
    {
        $inactiveGym = Gym::factory()->create(['is_active' => false]);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test2@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'gym_id' => $inactiveGym->id,
        ]);

        $response->assertSessionHasErrors('gym_id');
        $this->assertGuest();
    }
}
