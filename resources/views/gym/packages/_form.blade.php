@php
    $isEdit = isset($package);
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <div class="sm:col-span-2">
        <x-input-label for="name" value="Tên gói" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
            :value="old('name', $package->name ?? '')" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="description" value="Mô tả" />
        <textarea id="description" name="description" rows="3"
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $package->description ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="price" value="Giá (VNĐ)" />
        <x-text-input id="price" name="price" type="number" step="1000" min="0" class="mt-1 block w-full"
            :value="old('price', $package->price ?? '')" required />
        <x-input-error :messages="$errors->get('price')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="duration_days" value="Thời hạn (ngày)" />
        <x-text-input id="duration_days" name="duration_days" type="number" min="1" class="mt-1 block w-full"
            :value="old('duration_days', $package->duration_days ?? '')" required />
        <x-input-error :messages="$errors->get('duration_days')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="pt_sessions" value="Số buổi PT kèm theo" />
        <x-text-input id="pt_sessions" name="pt_sessions" type="number" min="0" class="mt-1 block w-full"
            :value="old('pt_sessions', $package->pt_sessions ?? 0)" required />
        <x-input-error :messages="$errors->get('pt_sessions')" class="mt-2" />
    </div>

    <div class="flex items-center gap-2 mt-6">
        <input type="checkbox" id="is_active" name="is_active" value="1"
            @checked(old('is_active', $package->is_active ?? true))
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
        <label for="is_active" class="text-sm text-gray-700">Đang mở bán</label>
    </div>
</div>
