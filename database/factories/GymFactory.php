<?php

namespace Database\Factories;

use App\Models\Gym;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Gym>
 */
class GymFactory extends Factory
{
    protected $model = Gym::class;

    public function definition(): array
    {
        $name = fake()->company().' Gym';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 9999),
            'code' => strtoupper(fake()->unique()->lexify('??')),
            'address' => fake()->address(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'description' => fake()->sentence(12),
            'opening_time' => '06:00',
            'closing_time' => '22:00',
            'is_active' => true,
        ];
    }
}
