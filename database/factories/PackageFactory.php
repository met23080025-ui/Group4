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

        return array_merge($this->attributesForDuration($duration), [
            'gym_id' => Gym::factory(),
            'price' => round($pricePerMonth * $duration / 30, -3),
            'pt_sessions' => fake()->numberBetween(0, 12),
            'is_active' => true,
        ]);
    }

    /**
     * Ép duration_days/name/description đi cùng nhau. Dùng ở nơi cần
     * duration_days cụ thể (vd DatabaseSeeder) — tránh lặp lại bug seed data
     * cũ: ->create(['duration_days' => 180]) đè duration_days SAU khi
     * definition() đã tự random $duration cho 'name' riêng, làm 'name' lệch
     * khỏi duration_days thật (vd "Gói 3 tháng" nhưng duration_days=180).
     */
    public function withDuration(int $days): static
    {
        return $this->state(fn () => $this->attributesForDuration($days));
    }

    private function attributesForDuration(int $days): array
    {
        return [
            'name' => match ($days) {
                30 => 'Gói 1 tháng',
                90 => 'Gói 3 tháng',
                180 => 'Gói 6 tháng',
                365 => 'Gói 12 tháng',
                default => 'Gói '.intdiv($days, 30).' tháng',
            },
            'description' => 'Gói tập '.intdiv($days, 30).' tháng, tự do tập luyện tại phòng gym.',
            'duration_days' => $days,
        ];
    }
}
