<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Gym;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\EquipmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentTest extends TestCase
{
    use RefreshDatabase;

    private Gym $gymA;

    private Gym $gymB;

    private User $ownerA;

    private User $staffA;

    private User $ownerB;

    private User $memberA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gymA = Gym::factory()->create(['code' => 'FZ']);
        $this->gymB = Gym::factory()->create(['code' => 'PH']);

        $this->ownerA = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_GYM_OWNER]);
        $this->staffA = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_STAFF]);
        $this->ownerB = User::factory()->create(['gym_id' => $this->gymB->id, 'role' => User::ROLE_GYM_OWNER]);
        $this->memberA = User::factory()->create(['gym_id' => $this->gymA->id, 'role' => User::ROLE_MEMBER]);
    }

    private function makeEquipment(Gym $gym, array $overrides = []): Equipment
    {
        return Equipment::create(array_merge([
            'gym_id' => $gym->id,
            'name' => 'Máy chạy bộ',
            'category' => 'Cardio',
            'status' => Equipment::STATUS_ACTIVE,
        ], $overrides));
    }

    // Rule: Owner/Staff tạo thiết bị được, Member không tạo được.
    public function test_owner_and_staff_can_create_equipment(): void
    {
        foreach ([$this->ownerA, $this->staffA] as $actor) {
            $this->actingAs($actor)
                ->post(route('gym.equipment.store'), [
                    'name' => 'Máy tập tạ', 'status' => Equipment::STATUS_ACTIVE,
                    'maintenance_interval_days' => 90,
                ])
                ->assertRedirect();
        }

        $this->assertDatabaseCount('equipment', 2);
    }

    public function test_member_cannot_create_equipment(): void
    {
        $this->actingAs($this->memberA)
            ->post(route('gym.equipment.store'), ['name' => 'Máy lén', 'status' => Equipment::STATUS_ACTIVE])
            ->assertForbidden();

        $this->assertDatabaseCount('equipment', 0);
    }

    // Rule: danh sách thiết bị chỉ hiện đúng Gym của user (scope gym_id).
    public function test_equipment_index_is_scoped_to_own_gym(): void
    {
        $this->makeEquipment($this->gymA, ['name' => 'Máy Gym A']);
        $this->makeEquipment($this->gymB, ['name' => 'Máy Gym B']);

        $response = $this->actingAs($this->ownerA)->get(route('gym.equipment.index'));

        $response->assertOk();
        $response->assertSee('Máy Gym A');
        $response->assertDontSee('Máy Gym B');
    }

    // Rule: cross-tenant — owner Gym A không xem/sửa/xoá được thiết bị Gym B (404).
    public function test_cross_tenant_equipment_access_returns_404(): void
    {
        $equipmentB = $this->makeEquipment($this->gymB);

        $this->actingAs($this->ownerA)->get(route('gym.equipment.show', $equipmentB))->assertNotFound();
        $this->actingAs($this->ownerA)->get(route('gym.equipment.edit', $equipmentB))->assertNotFound();
        $this->actingAs($this->ownerA)
            ->put(route('gym.equipment.update', $equipmentB), ['name' => 'Đổi tên', 'status' => Equipment::STATUS_ACTIVE])
            ->assertNotFound();
        $this->actingAs($this->ownerA)->delete(route('gym.equipment.destroy', $equipmentB))->assertNotFound();

        $this->assertDatabaseHas('equipment', ['id' => $equipmentB->id, 'deleted_at' => null]);
    }

    public function test_owner_can_update_and_delete_own_equipment(): void
    {
        $equipment = $this->makeEquipment($this->gymA);

        $this->actingAs($this->ownerA)
            ->put(route('gym.equipment.update', $equipment), [
                'name' => 'Máy chạy bộ Pro', 'status' => Equipment::STATUS_MAINTENANCE,
            ])
            ->assertRedirect(route('gym.equipment.show', $equipment));

        $this->assertSame('Máy chạy bộ Pro', $equipment->fresh()->name);

        $this->actingAs($this->ownerA)->delete(route('gym.equipment.destroy', $equipment))->assertRedirect();
        $this->assertSoftDeleted('equipment', ['id' => $equipment->id]);
    }

    // Rule: ghi nhận bảo trì -> tạo maintenance_records + tự cập nhật last/next_maintenance_at.
    public function test_recording_maintenance_updates_equipment_schedule(): void
    {
        $equipment = $this->makeEquipment($this->gymA, ['maintenance_interval_days' => 30]);

        $this->actingAs($this->ownerA)
            ->post(route('gym.equipment.maintenance.store', $equipment), [
                'performed_at' => now()->toDateString(),
                'description' => 'Bôi trơn dây curoa',
                'cost' => 150000,
            ])
            ->assertRedirect(route('gym.equipment.show', $equipment));

        $this->assertDatabaseHas('maintenance_records', [
            'equipment_id' => $equipment->id, 'performed_by' => $this->ownerA->id, 'cost' => 150000,
        ]);

        $fresh = $equipment->fresh();
        $this->assertSame(now()->toDateString(), $fresh->last_maintenance_at->toDateString());
        $this->assertSame(now()->addDays(30)->toDateString(), $fresh->next_maintenance_at->toDateString());
    }

    // Rule: cross-tenant — không ghi nhận bảo trì hộ thiết bị Gym khác (404).
    public function test_cannot_record_maintenance_for_another_gyms_equipment(): void
    {
        $equipmentB = $this->makeEquipment($this->gymB);

        $this->actingAs($this->ownerA)
            ->post(route('gym.equipment.maintenance.store', $equipmentB), ['performed_at' => now()->toDateString()])
            ->assertNotFound();

        $this->assertDatabaseCount('maintenance_records', 0);
    }

    // Rule: dashboard đếm ĐÚNG số thiết bị sắp đến hạn (trong N ngày) + đã quá hạn, không tính thiết bị còn xa hoặc chưa từng đặt lịch.
    public function test_dashboard_counts_equipment_due_for_maintenance_correctly(): void
    {
        $this->makeEquipment($this->gymA, ['name' => 'Sắp đến hạn', 'next_maintenance_at' => now()->addDays(5)->toDateString()]);
        $this->makeEquipment($this->gymA, ['name' => 'Đã quá hạn', 'next_maintenance_at' => now()->subDays(2)->toDateString()]);
        $this->makeEquipment($this->gymA, ['name' => 'Còn xa', 'next_maintenance_at' => now()->addDays(60)->toDateString()]);
        $this->makeEquipment($this->gymA, ['name' => 'Chưa có lịch', 'next_maintenance_at' => null]);

        $count = app(DashboardService::class)->equipmentDueForMaintenanceCount(14);

        $this->assertSame(2, $count);
    }

    // Rule: cảnh báo trên dashboard Owner/Staff chỉ tính thiết bị Gym mình (scope).
    public function test_dashboard_equipment_warning_does_not_leak_another_gym(): void
    {
        $this->makeEquipment($this->gymA, ['next_maintenance_at' => now()->addDays(3)->toDateString()]);
        $this->makeEquipment($this->gymB, ['next_maintenance_at' => now()->addDays(3)->toDateString()]);
        $this->makeEquipment($this->gymB, ['next_maintenance_at' => now()->addDays(1)->toDateString()]);

        $ownerResponse = $this->actingAs($this->ownerA)->get(route('gym.dashboard'));
        $ownerResponse->assertViewHas('equipment_due_for_maintenance', 1);

        $staffResponse = $this->actingAs($this->staffA)->get(route('staff.dashboard'));
        $staffResponse->assertViewHas('equipment_due_for_maintenance', 1);

        $ownerBResponse = $this->actingAs($this->ownerB)->get(route('gym.dashboard'));
        $ownerBResponse->assertViewHas('equipment_due_for_maintenance', 2);
    }

    // Rule: recordMaintenance() qua Service (không HTTP) vẫn tính đúng khi không có chu kỳ bảo trì -> next_maintenance_at = null.
    public function test_recording_maintenance_without_interval_leaves_next_maintenance_null(): void
    {
        $equipment = $this->makeEquipment($this->gymA);

        app(EquipmentService::class)->recordMaintenance($equipment, $this->ownerA, [
            'performed_at' => now()->toDateString(),
        ]);

        $this->assertNull($equipment->fresh()->next_maintenance_at);
        $this->assertNotNull($equipment->fresh()->last_maintenance_at);
    }
}
