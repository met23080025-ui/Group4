<x-app-layout>
    <x-slot name="header">Gói tập: {{ $package->name }}</x-slot>

    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('gym.packages.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Quay lại danh sách gói tập</a>
        <div class="flex items-center gap-3">
            <a href="{{ route('gym.packages.edit', $package) }}"
                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50">
                Sửa
            </a>
            <form method="POST" action="{{ route('gym.packages.destroy', $package) }}" onsubmit="return confirm('Xóa gói tập này?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-50 text-red-700 text-sm rounded-lg hover:bg-red-100">Xóa</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500">Giá</dt>
                    <dd class="mt-1 text-gray-900 font-semibold">{{ number_format($package->price, 0, ',', '.') }} đ</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Thời hạn</dt>
                    <dd class="mt-1 text-gray-900">{{ $package->duration_days }} ngày</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Số buổi PT kèm</dt>
                    <dd class="mt-1 text-gray-900">{{ $package->pt_sessions }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Trạng thái</dt>
                    <dd class="mt-1 text-gray-900">{{ $package->is_active ? 'Đang mở bán' : 'Ngừng bán' }}</dd>
                </div>
                @if ($package->description)
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500">Mô tả</dt>
                        <dd class="mt-1 text-gray-900 whitespace-pre-line">{{ $package->description }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-3">Khuyến mãi áp dụng cho gói này</h3>

            @forelse ($package->promotions as $promotion)
                <div class="flex items-center justify-between py-2 border-b border-gray-100 text-sm">
                    <div>
                        <span class="font-medium text-gray-900">{{ $promotion->code }}</span>
                        <span class="text-gray-500">
                            ({{ $promotion->discount_type === 'percent' ? $promotion->discount_value.'%' : number_format($promotion->discount_value, 0, ',', '.').'đ' }})
                        </span>
                    </div>
                    <form method="POST" action="{{ route('gym.packages.promotions.detach', [$package, $promotion]) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-600 hover:text-red-800 text-xs">Gỡ</button>
                    </form>
                </div>
            @empty
                <p class="text-sm text-gray-500 mb-3">Chưa gán khuyến mãi nào.</p>
            @endforelse

            @if ($availablePromotions->isNotEmpty())
                <form method="POST" action="{{ route('gym.packages.promotions.attach', $package) }}" class="mt-4 flex items-center gap-2">
                    @csrf
                    <select name="promotion_id" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                        @foreach ($availablePromotions as $promotion)
                            <option value="{{ $promotion->id }}">{{ $promotion->code }} — {{ $promotion->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="px-3 py-2 bg-gray-900 text-white text-xs rounded-lg hover:bg-gray-700 whitespace-nowrap">Gán</button>
                </form>
            @endif
        </div>
    </div>
</x-app-layout>
