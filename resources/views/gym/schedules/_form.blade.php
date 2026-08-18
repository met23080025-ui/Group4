@php
    $isEdit = isset($schedule);
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
    <div class="sm:col-span-2">
        <x-input-label for="title" value="Tiêu đề" />
        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
            :value="old('title', $schedule->title ?? '')" required autofocus />
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label for="description" value="Mô tả" />
        <textarea id="description" name="description" rows="3"
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $schedule->description ?? '') }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="trainer_id" value="Trainer phụ trách" />
        <select id="trainer_id" name="trainer_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
            <option value="">-- Không có --</option>
            @foreach ($trainers as $trainer)
                <option value="{{ $trainer->id }}" @selected((int) old('trainer_id', $schedule->trainer_id ?? '') === $trainer->id)>
                    {{ $trainer->user->name }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('trainer_id')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="capacity" value="Sức chứa (capacity)" />
        <x-text-input id="capacity" name="capacity" type="number" min="1" class="mt-1 block w-full"
            :value="old('capacity', $schedule->capacity ?? '')" required />
        <x-input-error :messages="$errors->get('capacity')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="class_date" value="Ngày diễn ra" />
        <x-text-input id="class_date" name="class_date" type="date" class="mt-1 block w-full"
            :value="old('class_date', isset($schedule) ? $schedule->class_date->toDateString() : '')" required />
        <x-input-error :messages="$errors->get('class_date')" class="mt-2" />
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <x-input-label for="start_time" value="Giờ bắt đầu" />
            <x-text-input id="start_time" name="start_time" type="time" class="mt-1 block w-full"
                :value="old('start_time', isset($schedule) ? $schedule->start_time->format('H:i') : '')" required />
            <x-input-error :messages="$errors->get('start_time')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="end_time" value="Giờ kết thúc" />
            <x-text-input id="end_time" name="end_time" type="time" class="mt-1 block w-full"
                :value="old('end_time', isset($schedule) ? $schedule->end_time->format('H:i') : '')" required />
            <x-input-error :messages="$errors->get('end_time')" class="mt-2" />
        </div>
    </div>

    <div class="flex items-center gap-2 mt-6">
        <input type="checkbox" id="is_pt_session" name="is_pt_session" value="1"
            @checked(old('is_pt_session', $schedule->is_pt_session ?? false))
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
        <label for="is_pt_session" class="text-sm text-gray-700">Buổi PT 1-kèm-1 (trừ remaining_pt_sessions)</label>
    </div>
</div>
