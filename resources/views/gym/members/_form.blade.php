@php
    $isEdit = isset($member);
    $user = $isEdit ? $member->user : null;
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <div>
        <x-input-label for="name" value="Họ và tên" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
            :value="old('name', $user->name ?? '')" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="email" value="Email" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
            :value="old('email', $user->email ?? '')" required />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="phone" value="Số điện thoại" />
        <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full"
            :value="old('phone', $user->phone ?? '')" />
        <x-input-error :messages="$errors->get('phone')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="date_of_birth" value="Ngày sinh" />
        <x-text-input id="date_of_birth" name="date_of_birth" type="date" class="mt-1 block w-full"
            :value="old('date_of_birth', optional($member->date_of_birth ?? null)->toDateString())" />
        <x-input-error :messages="$errors->get('date_of_birth')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="gender" value="Giới tính" />
        <select id="gender" name="gender" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">-- Không rõ --</option>
            <option value="male" @selected(old('gender', $member->gender ?? '') === 'male')>Nam</option>
            <option value="female" @selected(old('gender', $member->gender ?? '') === 'female')>Nữ</option>
            <option value="other" @selected(old('gender', $member->gender ?? '') === 'other')>Khác</option>
        </select>
        <x-input-error :messages="$errors->get('gender')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="joined_at" value="Ngày tham gia" />
        <x-text-input id="joined_at" name="joined_at" type="date" class="mt-1 block w-full"
            :value="old('joined_at', optional($member->joined_at ?? null)->toDateString())" />
        <x-input-error :messages="$errors->get('joined_at')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="height" value="Chiều cao (cm)" />
        <x-text-input id="height" name="height" type="number" step="0.01" class="mt-1 block w-full"
            :value="old('height', $member->height ?? '')" />
        <x-input-error :messages="$errors->get('height')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="weight" value="Cân nặng (kg)" />
        <x-text-input id="weight" name="weight" type="number" step="0.01" class="mt-1 block w-full"
            :value="old('weight', $member->weight ?? '')" />
        <x-input-error :messages="$errors->get('weight')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="emergency_contact" value="Liên hệ khẩn cấp" />
        <x-text-input id="emergency_contact" name="emergency_contact" type="text" class="mt-1 block w-full"
            :value="old('emergency_contact', $member->emergency_contact ?? '')" />
        <x-input-error :messages="$errors->get('emergency_contact')" class="mt-2" />
    </div>

    @if ($isEdit)
        <div>
            <x-input-label for="status" value="Trạng thái" />
            <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                <option value="active" @selected(old('status', $member->status) === 'active')>Đang hoạt động</option>
                <option value="expired" @selected(old('status', $member->status) === 'expired')>Hết hạn</option>
                <option value="blocked" @selected(old('status', $member->status) === 'blocked')>Bị chặn</option>
            </select>
            <x-input-error :messages="$errors->get('status')" class="mt-2" />
        </div>
    @endif

    <div class="sm:col-span-2">
        <x-input-label for="address" value="Địa chỉ" />
        <x-text-input id="address" name="address" type="text" class="mt-1 block w-full"
            :value="old('address', $member->address ?? '')" />
        <x-input-error :messages="$errors->get('address')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="notes" value="Ghi chú" />
        <textarea id="notes" name="notes" rows="3"
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('notes', $member->notes ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('notes')" class="mt-2" />
    </div>
</div>
