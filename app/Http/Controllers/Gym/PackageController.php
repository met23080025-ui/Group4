<?php

namespace App\Http\Controllers\Gym;

use App\Http\Controllers\Controller;
use App\Http\Requests\Package\StorePackageRequest;
use App\Http\Requests\Package\UpdatePackageRequest;
use App\Models\Package;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PackageController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Package::class);

        $query = Package::query();

        if ($search = $request->string('search')->trim()->value()) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($minPrice = $request->input('min_price')) {
            $query->where('price', '>=', $minPrice);
        }

        if ($maxPrice = $request->input('max_price')) {
            $query->where('price', '<=', $maxPrice);
        }

        if ($duration = $request->input('duration_days')) {
            $query->where('duration_days', $duration);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->string('status')->value() === 'active');
        }

        $direction = $request->string('direction')->value() === 'desc' ? 'desc' : 'asc';
        $query->orderBy('price', $direction);

        $packages = $query->paginate(15)->withQueryString();

        return view('gym.packages.index', [
            'packages' => $packages,
            'filters' => $request->only(['search', 'min_price', 'max_price', 'duration_days', 'status', 'direction']),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Package::class);

        return view('gym.packages.create');
    }

    public function store(StorePackageRequest $request): RedirectResponse
    {
        $package = Package::create($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        return redirect()->route('gym.packages.show', $package)->with('success', "Đã tạo gói tập {$package->name}.");
    }

    public function show(Package $package): View
    {
        $this->authorize('view', $package);

        $package->load('promotions');

        $availablePromotions = Promotion::query()
            ->whereDoesntHave('packages', fn ($q) => $q->where('packages.id', $package->id))
            ->orderBy('name')
            ->get();

        return view('gym.packages.show', [
            'package' => $package,
            'availablePromotions' => $availablePromotions,
        ]);
    }

    public function edit(Package $package): View
    {
        $this->authorize('update', $package);

        return view('gym.packages.edit', ['package' => $package]);
    }

    public function update(UpdatePackageRequest $request, Package $package): RedirectResponse
    {
        $package->update($request->validated() + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('gym.packages.show', $package)->with('success', 'Đã cập nhật gói tập.');
    }

    public function destroy(Package $package): RedirectResponse
    {
        $this->authorize('delete', $package);

        $package->delete();

        return redirect()->route('gym.packages.index')->with('success', "Đã xóa gói tập {$package->name}.");
    }

    public function attachPromotion(Request $request, Package $package): RedirectResponse
    {
        $this->authorize('update', $package);

        $request->validate([
            'promotion_id' => ['required', 'integer', 'exists:promotions,id'],
        ]);

        $promotion = Promotion::findOrFail($request->integer('promotion_id'));

        if ($promotion->gym_id !== $package->gym_id) {
            abort(403, 'Khuyến mãi không thuộc cùng Gym với gói tập.');
        }

        $package->promotions()->syncWithoutDetaching([$promotion->id]);

        return redirect()->route('gym.packages.show', $package)->with('success', "Đã gán khuyến mãi {$promotion->code} vào gói.");
    }

    public function detachPromotion(Package $package, Promotion $promotion): RedirectResponse
    {
        $this->authorize('update', $package);

        $package->promotions()->detach($promotion->id);

        return redirect()->route('gym.packages.show', $package)->with('success', "Đã gỡ khuyến mãi {$promotion->code} khỏi gói.");
    }
}
