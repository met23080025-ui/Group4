<?php

namespace App\Http\Controllers\Gym;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\AssignTrainerRequest;
use App\Http\Requests\Member\StoreMemberRequest;
use App\Http\Requests\Member\UpdateMemberRequest;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Trainer;
use App\Services\MemberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function __construct(private readonly MemberService $memberService) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Member::class);

        $members = $this->baseQuery($request)->paginate(15)->withQueryString();

        return view('gym.members.index', [
            'members' => $members,
            'filters' => $request->only(['search', 'status', 'joined_from', 'joined_to', 'sort', 'direction']),
        ]);
    }

    public function trashed(Request $request): View
    {
        $this->authorize('viewAny', Member::class);

        $members = Member::onlyTrashed()
            ->with('user')
            ->orderByDesc('deleted_at')
            ->paginate(15)
            ->withQueryString();

        return view('gym.members.trashed', ['members' => $members]);
    }

    public function create(): View
    {
        $this->authorize('create', Member::class);

        return view('gym.members.create');
    }

    public function store(StoreMemberRequest $request): RedirectResponse
    {
        $gym = Gym::findOrFail($request->user()->gym_id);

        $member = $this->memberService->create($gym, $request->validated());

        return redirect()
            ->route('gym.members.show', $member)
            ->with('success', "Đã tạo hội viên {$member->member_code}.");
    }

    public function show(Member $member): View
    {
        $this->authorize('view', $member);

        $member->load(['user', 'trainer.user']);

        return view('gym.members.show', [
            'member' => $member,
            'currentMembership' => $member->currentMembership(),
            'latestBodyMeasurement' => $member->bodyMeasurements()->latest('measured_at')->first(),
            'trainers' => Trainer::query()->where('is_active', true)->with('user')->orderBy('id')->get(),
        ]);
    }

    public function edit(Member $member): View
    {
        $this->authorize('update', $member);

        $member->load('user');

        return view('gym.members.edit', ['member' => $member]);
    }

    public function update(UpdateMemberRequest $request, Member $member): RedirectResponse
    {
        $this->memberService->update($member, $request->validated());

        return redirect()
            ->route('gym.members.show', $member)
            ->with('success', 'Đã cập nhật thông tin hội viên.');
    }

    /**
     * Gán/gỡ PT phụ trách chính (Khối 6) — tách khỏi update() thường vì đây
     * là 1 thao tác nhanh, riêng biệt (dropdown chọn trainer), không đi qua
     * toàn bộ form sửa hồ sơ member.
     */
    public function assignTrainer(AssignTrainerRequest $request, Member $member): RedirectResponse
    {
        $member->update(['trainer_id' => $request->validated('trainer_id')]);

        return redirect()
            ->route('gym.members.show', $member)
            ->with('success', $request->validated('trainer_id')
                ? 'Đã gán PT phụ trách cho hội viên.'
                : 'Đã gỡ PT phụ trách của hội viên.');
    }

    public function destroy(Member $member): RedirectResponse
    {
        $this->authorize('delete', $member);

        $member->delete();

        return redirect()
            ->route('gym.members.index')
            ->with('success', "Đã vô hiệu hóa hội viên {$member->member_code}.");
    }

    public function restore(int $member): RedirectResponse
    {
        // withTrashed() chỉ bỏ SoftDeletingScope — global scope 'gym' (BelongsToGym)
        // vẫn áp dụng nên vẫn 404 nếu member này thuộc Gym khác.
        $memberModel = Member::withTrashed()->findOrFail($member);

        $this->authorize('restore', $memberModel);

        $memberModel->restore();

        return redirect()
            ->route('gym.members.index')
            ->with('success', "Đã khôi phục hội viên {$memberModel->member_code}.");
    }

    private function baseQuery(Request $request)
    {
        $query = Member::query()
            ->join('users', 'users.id', '=', 'members.user_id')
            ->select('members.*')
            ->with('user');

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('members.member_code', 'like', "%{$search}%")
                    ->orWhere('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%")
                    ->orWhere('users.phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->string('status')->value()) {
            if (in_array($status, Member::STATUSES, true)) {
                $query->where('members.status', $status);
            }
        }

        if ($joinedFrom = $request->date('joined_from')) {
            $query->whereDate('members.joined_at', '>=', $joinedFrom);
        }

        if ($joinedTo = $request->date('joined_to')) {
            $query->whereDate('members.joined_at', '<=', $joinedTo);
        }

        $sortable = [
            'name' => 'users.name',
            'joined_at' => 'members.joined_at',
        ];
        $sort = $sortable[$request->string('sort')->value()] ?? 'members.created_at';
        $direction = $request->string('direction')->value() === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction);
    }
}
