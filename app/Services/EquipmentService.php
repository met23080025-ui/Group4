<?php

namespace App\Services;

use App\Models\Equipment;
use App\Models\MaintenanceRecord;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ghi nhận 1 lần bảo trì (Khối 4, Ngày 3): tạo MaintenanceRecord + tự cập
 * nhật last_maintenance_at/next_maintenance_at trên Equipment — gộp vào
 * Service (không để rải rác ở Controller) vì đây là nghiệp vụ 2 bước phải
 * luôn đi cùng nhau.
 */
class EquipmentService
{
    public function recordMaintenance(Equipment $equipment, User $performedBy, array $data): MaintenanceRecord
    {
        return DB::transaction(function () use ($equipment, $performedBy, $data) {
            $record = $equipment->maintenanceRecords()->create([
                'gym_id' => $equipment->gym_id,
                'performed_by' => $performedBy->id,
                'performed_at' => $data['performed_at'],
                'description' => $data['description'] ?? null,
                'cost' => $data['cost'] ?? null,
            ]);

            $nextMaintenanceAt = $equipment->maintenance_interval_days
                ? Carbon::parse($data['performed_at'])->addDays($equipment->maintenance_interval_days)->toDateString()
                : null;

            $equipment->update([
                'last_maintenance_at' => $data['performed_at'],
                'next_maintenance_at' => $nextMaintenanceAt,
            ]);

            return $record;
        });
    }
}
