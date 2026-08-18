<?php

namespace Database\Factories;

use App\Models\Gym;
use App\Models\Schedule;
use App\Models\Trainer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        $start = fake()->randomElement(['06:00', '07:00', '08:00', '17:00', '18:00', '19:00', '20:00']);
        $end = Carbon::createFromFormat('H:i', $start)->addMinutes(60)->format('H:i');

        return [
            'gym_id' => Gym::factory(),
            'trainer_id' => Trainer::factory(),
            'title' => fake()->randomElement(['Yoga', 'Zumba', 'HIIT', 'Cardio Blast', 'Boxing cơ bản']),
            'description' => fake()->sentence(12),
            'class_date' => fake()->dateTimeBetween('+1 day', '+14 days')->format('Y-m-d'),
            'start_time' => $start,
            'end_time' => $end,
            'capacity' => fake()->numberBetween(10, 20),
            'status' => Schedule::STATUS_SCHEDULED,
            'is_pt_session' => false,
        ];
    }

    /**
     * Buổi tập 1-kèm-1 với PT: capacity nhỏ (mặc định 1), trừ
     * remaining_pt_sessions của membership khi member đặt chỗ (Khối 4).
     */
    public function ptSession(int $capacity = 1): static
    {
        return $this->state(fn () => [
            'title' => 'Buổi PT cá nhân',
            'description' => 'Buổi tập riêng 1-kèm-1 với PT.',
            'capacity' => $capacity,
            'is_pt_session' => true,
        ]);
    }
}
