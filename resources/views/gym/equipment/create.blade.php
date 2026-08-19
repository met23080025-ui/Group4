<x-app-layout>
    <x-slot name="header">Thêm thiết bị</x-slot>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-xl">
        <form method="POST" action="{{ route('gym.equipment.store') }}" class="space-y-4 text-sm">
            @csrf
            <div>
                <label class="block text-gray-600 mb-1">Tên thiết bị</label>
                <input type="text" name="name" value="{{ old('name') }}" required class="w-full rounded-md border-gray-300 text-sm">
                @error('name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-gray-600 mb-1">Danh mục</label>
                <input type="text" name="category" value="{{ old('category') }}" placeholder="Vd: Cardio, Tạ, Máy tập..." class="w-full rounded-md border-gray-300 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-600 mb-1">Ngày mua</label>
                    <input type="date" name="purchase_date" value="{{ old('purchase_date') }}" class="w-full rounded-md border-gray-300 text-sm">
                </div>
                <div>
                    <label class="block text-gray-600 mb-1">Trạng thái</label>
                    <select name="status" class="w-full rounded-md border-gray-300 text-sm">
                        <option value="active">Đang dùng</option>
                        <option value="maintenance">Đang bảo trì</option>
                        <option value="retired">Ngừng dùng</option>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-600 mb-1">Chu kỳ bảo trì (ngày)</label>
                    <input type="number" name="maintenance_interval_days" value="{{ old('maintenance_interval_days') }}" min="1" class="w-full rounded-md border-gray-300 text-sm">
                    @error('maintenance_interval_days')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-gray-600 mb-1">Lần bảo trì gần nhất</label>
                    <input type="date" name="last_maintenance_at" value="{{ old('last_maintenance_at') }}" class="w-full rounded-md border-gray-300 text-sm">
                </div>
            </div>
            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-white text-sm font-medium hover:bg-indigo-700">Lưu</button>
        </form>
    </div>
</x-app-layout>
