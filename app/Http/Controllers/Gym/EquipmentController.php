<?php

namespace App\Http\Controllers\Gym;

use App\Http\Controllers\Controller;
use App\Http\Requests\Equipment\StoreEquipmentRequest;
use App\Http\Requests\Equipment\UpdateEquipmentRequest;
use App\Http\Requests\MaintenanceRecord\StoreMaintenanceRecordRequest;
use App\Models\Equipment;
use App\Services\EquipmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class EquipmentController extends Controller
{
    public function __construct(private readonly EquipmentService $equipmentService) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Equipment::class);

        $query = Equipment::query()->orderBy('name');

        if ($status = $request->string('status')->value()) {
            if (in_array($status, Equipment::STATUSES, true)) {
                $query->where('status', $status);
            }
        }

        $equipment = $query->paginate(15)->withQueryString();

        return view('gym.equipment.index', [
            'equipment' => $equipment,
            'filters' => $request->only(['status']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Equipment::class);

        return view('gym.equipment.create');
    }

    public function store(StoreEquipmentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Nếu đã có lần bảo trì gần nhất + chu kỳ -> tính sẵn lịch kế tiếp,
        // cùng công thức với EquipmentService::recordMaintenance().
        if (! empty($data['last_maintenance_at']) && ! empty($data['maintenance_interval_days'])) {
            $data['next_maintenance_at'] = Carbon::parse($data['last_maintenance_at'])
                ->addDays((int) $data['maintenance_interval_days'])
                ->toDateString();
        }

        $equipment = Equipment::create($data);

        return redirect()->route('gym.equipment.show', $equipment)->with('success', "Đã thêm thiết bị {$equipment->name}.");
    }

    public function show(Equipment $equipment): View
    {
        $this->authorize('view', $equipment);

        $equipment->load(['maintenanceRecords' => fn ($q) => $q->orderByDesc('performed_at')->with('performedBy')]);

        return view('gym.equipment.show', ['equipment' => $equipment]);
    }

    public function edit(Equipment $equipment): View
    {
        $this->authorize('update', $equipment);

        return view('gym.equipment.edit', ['equipment' => $equipment]);
    }

    public function update(UpdateEquipmentRequest $request, Equipment $equipment): RedirectResponse
    {
        $equipment->update($request->validated());

        return redirect()->route('gym.equipment.show', $equipment)->with('success', 'Đã cập nhật thiết bị.');
    }

    public function destroy(Equipment $equipment): RedirectResponse
    {
        $this->authorize('delete', $equipment);

        $equipment->delete();

        return redirect()->route('gym.equipment.index')->with('success', "Đã xoá thiết bị {$equipment->name}.");
    }

    public function storeMaintenance(StoreMaintenanceRecordRequest $request, Equipment $equipment): RedirectResponse
    {
        $this->equipmentService->recordMaintenance($equipment, $request->user(), $request->validated());

        return redirect()->route('gym.equipment.show', $equipment)->with('success', 'Đã ghi nhận bảo trì.');
    }
}
