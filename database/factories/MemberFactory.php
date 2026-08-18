<?php

namespace Database\Factories;

use App\Models\Gym;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            // Mặc định hữu ích khi dùng factory độc lập (vd. test); trong DatabaseSeeder
            // luôn override gym_id/user_id tường minh vì seeder chạy CLI, không có
            // auth()->user() để BelongsToGym tự gán.
            'gym_id' => Gym::factory(),
            'user_id' => User::factory()->state(['role' => User::ROLE_MEMBER]),
            'member_code' => strtoupper(fake()->unique()->bothify('MB-####')),
            'date_of_birth' => fake()->dateTimeBetween('-45 years', '-16 years'),
            'gender' => fake()->randomElement(['male', 'female']),
            'address' => fake()->address(),
            'emergency_contact' => fake()->phoneNumber(),
            'height' => fake()->randomFloat(2, 150, 190),
            'weight' => fake()->randomFloat(2, 45, 95),
            'status' => Member::STATUS_ACTIVE,
            'joined_at' => fake()->dateTimeBetween('-2 years', 'now'),
        ];
    }
}
