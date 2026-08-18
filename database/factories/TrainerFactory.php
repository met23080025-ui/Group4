<?php

namespace Database\Factories;

use App\Models\Gym;
use App\Models\Trainer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Trainer>
 */
class TrainerFactory extends Factory
{
    protected $model = Trainer::class;

    public function definition(): array
    {
        return [
            'gym_id' => Gym::factory(),
            'user_id' => User::factory()->state(['role' => User::ROLE_TRAINER]),
            'specialization' => fake()->randomElement([
                'Gym cơ bản', 'Yoga', 'Cardio', 'Boxing', 'Calisthenics', 'Dinh dưỡng thể hình',
            ]),
            'bio' => fake()->sentence(15),
            'rating_avg' => fake()->randomFloat(2, 3.5, 5),
            'is_active' => true,
        ];
    }
}
