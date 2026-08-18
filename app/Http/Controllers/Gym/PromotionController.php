<?php

namespace App\Http\Controllers\Gym;

use App\Http\Controllers\Controller;
use App\Http\Requests\Promotion\StorePromotionRequest;
use App\Http\Requests\Promotion\UpdatePromotionRequest;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PromotionController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Promotion::class);

        $query = Promotion::query();

        if ($search = $request->string('search')->trim()->value()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status')->value() === 'active');
        }

        $promotions = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('gym.promotions.index', [
            'promotions' => $promotions,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Promotion::class);

        return view('gym.promotions.create');
    }

    public function store(StorePromotionRequest $request): RedirectResponse
    {
        $promotion = Promotion::create($request->validated() + [
            'used_count' => 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('gym.promotions.index')->with('success', "Đã tạo khuyến mãi {$promotion->code}.");
    }

    public function edit(Promotion $promotion): View
    {
        $this->authorize('update', $promotion);

        return view('gym.promotions.edit', ['promotion' => $promotion]);
    }

    public function update(UpdatePromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        $promotion->update($request->validated() + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('gym.promotions.index')->with('success', 'Đã cập nhật khuyến mãi.');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $this->authorize('delete', $promotion);

        $promotion->delete();

        return redirect()->route('gym.promotions.index')->with('success', "Đã xóa khuyến mãi {$promotion->code}.");
    }
}
