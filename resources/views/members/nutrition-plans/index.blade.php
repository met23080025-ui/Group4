<x-app-layout>
    <x-slot name="header">Kế hoạch dinh dưỡng — {{ $member->member_code }}</x-slot>

    @if (session('success'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    @can('create', [\App\Models\NutritionPlan::class, $member])
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
            <h3 class="text-base font-semibold text-gray-900 mb-3">Tạo kế hoạch dinh dưỡng mới</h3>
            <form method="POST" action="{{ route('members.nutrition-plans.store', $member) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-sm items-end">
                @csrf
                <div class="sm:col-span-2">
                    <label class="block text-gray-600 mb-1">Tiêu đề</label>
                    <input type="text" name="title" required class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-gray-600 mb-1">Bắt đầu</label>
                    <input type="date" name="start_date" class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-gray-600 mb-1">Kết thúc</label>
                    <input type="date" name="end_date" class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div class="sm:col-span-4">
                    <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700">Tạo kế hoạch</button>
                </div>
            </form>
        </div>
    @endcan

    <div class="space-y-6">
        @forelse ($plans as $plan)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
                <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <span class="text-sm font-semibold text-gray-900">{{ $plan->title }}</span>
                        <span class="text-xs text-gray-500 ml-2">PT: {{ $plan->trainer?->user->name ?? '—' }}</span>
                    </div>
                    <span class="text-xs text-gray-500">
                        {{ optional($plan->start_date)->format('d/m/Y') }} – {{ optional($plan->end_date)->format('d/m/Y') }}
                    </span>
                </div>

                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium text-gray-500">Bữa ăn</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-500">Món</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-500">Calo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($plan->items as $item)
                            <tr>
                                <td class="px-4 py-2 text-gray-900">{{ $item->meal_name }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ $item->food }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ $item->calories ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-4 text-center text-gray-500">Chưa có món ăn nào.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                @can('update', $plan)
                    <form method="POST" action="{{ route('nutrition-plans.items.store', $plan) }}" class="px-4 py-3 border-t border-gray-100 grid grid-cols-2 sm:grid-cols-5 gap-2 text-sm items-end">
                        @csrf
                        <input type="text" name="meal_name" placeholder="Tên bữa ăn" required class="rounded-md border-gray-300 text-sm">
                        <input type="text" name="food" placeholder="Món ăn" required class="rounded-md border-gray-300 text-sm">
                        <input type="number" name="calories" placeholder="Calo" class="rounded-md border-gray-300 text-sm">
                        <button type="submit" class="rounded-md bg-white border border-gray-300 px-3 py-2 text-gray-700 text-sm hover:bg-gray-50 sm:col-span-2">Thêm món ăn</button>
                    </form>
                @endcan
            </div>
        @empty
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 text-center text-gray-500 text-sm">
                Chưa có kế hoạch dinh dưỡng nào.
            </div>
        @endforelse
    </div>
</x-app-layout>
