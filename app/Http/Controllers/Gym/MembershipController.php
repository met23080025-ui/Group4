<?php

namespace App\Http\Controllers\Gym;

use App\Http\Controllers\Controller;
use App\Http\Requests\Membership\StoreMembershipRequest;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Package;
use App\Models\Promotion;
use App\Services\MembershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembershipController extends Controller
{
    public function __construct(private readonly MembershipService $membershipService) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Membership::class);

        $query = Membership::query()->with(['member.user', 'package', 'promotion']);

        if ($status = $request->string('status')->value()) {
            if (in_array($status, Membership::STATUSES, true)) {
                $query->where('status', $status);
            }
        }

        $memberships = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('gym.memberships.index', [
            'memberships' => $memberships,
            'filters' => $request->only(['status']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Membership::class);

        return view('gym.memberships.create', [
            'members' => Member::query()->where('status', Member::STATUS_ACTIVE)->with('user')->orderBy('member_code')->get(),
            'packages' => Package::query()->where('is_active', true)->orderBy('name')->get(),
            'promotions' => Promotion::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(StoreMembershipRequest $request): RedirectResponse
    {
        $member = Member::findOrFail($request->integer('member_id'));
        $package = Package::findOrFail($request->integer('package_id'));
        $promotion = $request->filled('promotion_id') ? Promotion::findOrFail($request->integer('promotion_id')) : null;

        try {
            $membership = $this->membershipService->create($member, $package, $promotion, [
                'start_date' => $request->input('start_date'),
            ]);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['promotion_id' => $e->getMessage()]);
        }

        return redirect()
            ->route('gym.memberships.show', $membership)
            ->with('success', "Đã tạo membership cho {$member->member_code}, trạng thái: chờ thanh toán.");
    }

    public function show(Membership $membership): View
    {
        $this->authorize('view', $membership);

        $membership->load(['member.user', 'package', 'promotion']);

        return view('gym.memberships.show', ['membership' => $membership]);
    }
}
