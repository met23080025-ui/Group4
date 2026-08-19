<?php

namespace App\Http\Controllers;

use App\Http\Requests\BodyMeasurement\StoreBodyMeasurementRequest;
use App\Models\BodyMeasurement;
use App\Models\Member;
use App\Services\BodyMeasurementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Không đặt trong namespace Gym\ vì dùng chung cho Owner/Staff/Trainer/
 * Member — BodyMeasurementPolicy phân định đúng quyền theo từng vai trò
 * (giống PaymentController). Route model binding {member} đã tự trả 404 nếu
 * member thuộc Gym khác (global scope BelongsToGym) — cross-trainer thì bị
 * Policy chặn (403).
 */
class BodyMeasurementController extends Controller
{
    public function __construct(private readonly BodyMeasurementService $bodyMeasurementService) {}

    public function index(Member $member): View
    {
        $this->authorize('viewAny', [BodyMeasurement::class, $member]);

        $member->load('user');
        $measurements = $member->bodyMeasurements()->orderByDesc('measured_at')->orderByDesc('id')->get();

        return view('members.measurements.index', ['member' => $member, 'measurements' => $measurements]);
    }

    public function store(StoreBodyMeasurementRequest $request, Member $member): RedirectResponse
    {
        $this->bodyMeasurementService->record($member, $request->user(), $request->validated());

        return redirect()->route('members.measurements.index', $member)->with('success', 'Đã lưu chỉ số cơ thể.');
    }
}
