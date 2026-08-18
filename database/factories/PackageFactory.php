<?php

namespace Database\Factories;

use App\Models\Gym;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    protected $model = Package::class;

    public function definition(): array
    {
        $duration = fake()->randomElement([30, 90, 180, 365]);
        $pricePerMonth = fake()->numberBetween(400000, 900000);

        return [
            'gym_id' => Gym::factory(),
            'name' => match ($duration) {
                30 => 'Gói 1 tháng',
                90 => 'Gói 3 tháng',
                180 => 'Gói 6 tháng',
                default => 'Gói 12 tháng',
            },
            'description' => 'Gói tập '.intdiv($duration, 30).' tháng, tự do tập luyện tại phòng gym.',
            'price' => round($pricePerMonth * $duration / 30, -3),
            'duration_days' => $duration,
            'pt_sessions' => fake()->numberBetween(0, 12),
            'is_active' => true,
        ];
    }
}
