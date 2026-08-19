<?php

namespace App\Services;

use App\Models\BodyMeasurement;
use App\Models\Member;
use App\Models\User;

/**
 * Ghi nhận chỉ số cơ thể + tính BMI (Khối 6, mục 15). Chỉ 1 hành động
 * (record) nên gom vào Service thay vì để thẳng trong Controller — tách
 * biệt phép tính BMI để test trực tiếp, không phải suy luận qua HTTP.
 */
class BodyMeasurementService
{
    public function record(Member $member, User $recordedBy, array $data): BodyMeasurement
    {
        return BodyMeasurement::create([
            'gym_id' => $member->gym_id,
            'member_id' => $member->id,
            'recorded_by' => $recordedBy->id,
            'measured_at' => $data['measured_at'] ?? now()->toDateString(),
            'height' => $data['height'],
            'weight' => $data['weight'],
            'body_fat_percent' => $data['body_fat_percent'] ?? null,
            'muscle_mass' => $data['muscle_mass'] ?? null,
            'bmi' => $this->calculateBmi((float) $data['height'], (float) $data['weight']),
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * BMI = cân nặng(kg) / chiều cao(m)^2. height lưu bằng cm nên phải đổi
     * đơn vị trước khi tính. Form Request đã validate height >= 30 (cm) nên
     * không chia cho 0 ở đây.
     */
    public function calculateBmi(float $heightCm, float $weightKg): float
    {
        $heightM = $heightCm / 100;

        return round($weightKg / ($heightM ** 2), 2);
    }
}
