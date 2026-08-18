@php
    $isEdit = isset($promotion);
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <div>
        <x-input-label for="code" value="Mã khuyến mãi" />
        <x-text-input id="code" name="code" type="text" class="mt-1 block w-full"
            :value="old('code', $promotion->code ?? '')" required />
        <x-input-error :messages="$errors->get('code')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="name" value="Tên khuyến mãi" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
            :value="old('name', $promotion->name ?? '')" required />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="discount_type" value="Loại giảm giá" />
        <select id="discount_type" name="discount_type" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="percent" @selected(old('discount_type', $promotion->discount_type ?? 'percent') === 'percent')>Theo phần trăm (%)</option>
            <option value="fixed" @selected(old('discount_type', $promotion->discount_type ?? '') === 'fixed')>Số tiền cố định (đ)</option>
        </select>
        <x-input-error :messages="$errors->get('discount_type')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="discount_value" value="Giá trị giảm" />
        <x-text-input id="discount_value" name="discount_value" type="number" step="0.01" min="0" class="mt-1 block w-full"
            :value="old('discount_value', $promotion->discount_value ?? '')" required />
        <x-input-error :messages="$errors->get('discount_value')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="start_date" value="Ngày bắt đầu" />
        <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full"
            :value="old('start_date', optional($promotion->start_date ?? null)->toDateString())" required />
        <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="end_date" value="Ngày kết thúc" />
        <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full"
            :value="old('end_date', optional($promotion->end_date ?? null)->toDateString())" required />
        <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="usage_limit" value="Giới hạn số lượt dùng (để trống = không giới hạn)" />
        <x-text-input id="usage_limit" name="usage_limit" type="number" min="1" class="mt-1 block w-full"
            :value="old('usage_limit', $promotion->usage_limit ?? '')" />
        <x-input-error :messages="$errors->get('usage_limit')" class="mt-2" />
    </div>

    <div class="flex items-center gap-2 mt-6">
        <input type="checkbox" id="is_active" name="is_active" value="1"
            @checked(old('is_active', $promotion->is_active ?? true))
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
        <label for="is_active" class="text-sm text-gray-700">Đang kích hoạt</label>
    </div>
</div>
