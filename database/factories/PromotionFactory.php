<?php

namespace Database\Factories;

use App\Models\Gym;
use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

    public function definition(): array
    {
        $type = fake()->randomElement([Promotion::DISCOUNT_TYPE_PERCENT, Promotion::DISCOUNT_TYPE_FIXED]);

        return [
            'gym_id' => Gym::factory(),
            'code' => strtoupper(fake()->unique()->bothify('PROMO##??')),
            'name' => fake()->randomElement([
                'Khuyến mãi khai trương', 'Ưu đãi mùa hè', 'Giảm giá hội viên mới', 'Ưu đãi sinh nhật Gym',
            ]),
            'discount_type' => $type,
            'discount_value' => $type === Promotion::DISCOUNT_TYPE_PERCENT
                ? fake()->numberBetween(5, 30)
                : fake()->numberBetween(50000, 300000),
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(60)->toDateString(),
            'usage_limit' => fake()->numberBetween(20, 100),
            'used_count' => 0,
            'is_active' => true,
        ];
    }
}
