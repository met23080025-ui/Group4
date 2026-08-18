<?php

namespace Database\Factories;

use App\Models\Gym;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
{
    protected $model = Staff::class;

    public function definition(): array
    {
        return [
            'gym_id' => Gym::factory(),
            'user_id' => User::factory()->state(['role' => User::ROLE_STAFF]),
            'position' => fake()->randomElement(['Lễ tân', 'Quản lý ca', 'Nhân viên vận hành']),
            'is_active' => true,
        ];
    }
}
