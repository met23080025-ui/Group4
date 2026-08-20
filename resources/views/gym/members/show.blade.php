<x-app-layout>
    <x-slot name="header">Hội viên {{ $member->member_code }}</x-slot>

    <div class="mb-5 flex items-center justify-between">
        <a href="{{ route('gym.members.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-600 hover:text-gray-900">&larr; Quay lại danh sách hội viên</a>
        <div class="flex items-center gap-3">
            <a href="{{ route('gym.members.edit', $member) }}"
                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50">
                Sửa
            </a>
            <form method="POST" action="{{ route('gym.members.destroy', $member) }}"
                onsubmit="return confirm('Vô hiệu hóa hội viên này?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-50 text-red-700 text-sm rounded-lg hover:bg-red-100">
                    Vô hiệu hóa
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 mb-6 flex flex-wrap items-center gap-5">
        <div class="h-16 w-16 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xl font-semibold shrink-0">
            {{ mb_strtoupper(mb_substr($member->user->name, 0, 1)) }}
        </div>
        <div class="min-w-0">
            <div class="text-lg font-semibold text-gray-900">{{ $member->user->name }}</div>
            <div class="text-sm text-gray-500">{{ $member->user->email }}</div>
        </div>
        <div class="ml-auto flex items-center gap-2">
            <x-status-badge :status="$member->status" class="text-sm px-3 py-1.5" />
            @if ($currentMembership)
                @php $daysRemaining = now()->startOfDay()->diffInDays($currentMembership->end_date, false); @endphp
                <x-status-badge
                    :status="$daysRemaining <= 7 ? 'pending' : 'active'"
                    :label="$daysRemaining >= 0 ? 'Còn ' . $daysRemaining . ' ngày' : 'Đã hết hạn'"
                    class="text-sm px-3 py-1.5"
                />
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Thông tin cá nhân</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500">Họ tên</dt>
                    <dd class="mt-1 text-gray-900">{{ $member->user->name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Email</dt>
                    <dd class="mt-1 text-gray-900">{{ $member->user->email }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Số điện thoại</dt>
                    <dd class="mt-1 text-gray-900">{{ $member->user->phone ?? 'Chưa cập nhật' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Ngày sinh</dt>
                    <dd class="mt-1 text-gray-900">{{ optional($member->date_of_birth)->format('d/m/Y') ?? 'Chưa cập nhật' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Giới tính</dt>
                    <dd class="mt-1 text-gray-900">{{ $member->gender ?? 'Chưa cập nhật' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Ngày tham gia</dt>
                    <dd class="mt-1 text-gray-900">{{ optional($member->joined_at)->format('d/m/Y') ?? 'Chưa cập nhật' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Chiều cao / Cân nặng</dt>
                    <dd class="mt-1 text-gray-900">{{ $member->height ?? 'Chưa cập nhật' }} cm / {{ $member->weight ?? 'Chưa cập nhật' }} kg</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Liên hệ khẩn cấp</dt>
                    <dd class="mt-1 text-gray-900">{{ $member->emergency_contact ?? 'Chưa cập nhật' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-gray-500">Địa chỉ</dt>
                    <dd class="mt-1 text-gray-900">{{ $member->address ?? 'Chưa cập nhật' }}</dd>
                </div>
                @if ($member->notes)
                    <div class="sm:col-span-2">
                        <dt class="text-gray-500">Ghi chú</dt>
                        <dd class="mt-1 text-gray-900 whitespace-pre-line">{{ $member->notes }}</dd>
                    </div>
                @endif
            </dl>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <x-icon name="user-circle" class="w-5 h-5 text-gray-400" /> PT phụ trách
                </h3>
                <p class="text-sm text-gray-700 mb-3">
                    {{ $member->trainer?->user->name ?? 'Chưa gán PT.' }}
                </p>
                <form method="POST" action="{{ route('gym.members.assign-trainer', $member) }}" class="flex items-center gap-2">
                    @csrf
                    <select name="trainer_id" class="rounded-md border-gray-300 text-sm flex-1">
                        <option value="">-- Không gán --</option>
                        @foreach ($trainers as $trainer)
                            <option value="{{ $trainer->id }}" @selected($member->trainer_id === $trainer->id)>{{ $trainer->user->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-md bg-white border border-gray-300 px-3 py-2 text-gray-700 text-sm hover:bg-gray-50">Gán</button>
                </form>
                <div class="mt-4 flex flex-col gap-1.5 text-sm">
                    <a href="{{ route('members.measurements.index', $member) }}" class="text-indigo-600 hover:text-indigo-900 flex items-center gap-1.5"><x-icon name="scale" class="w-4 h-4" /> Chỉ số cơ thể</a>
                    <a href="{{ route('members.workout-plans.index', $member) }}" class="text-indigo-600 hover:text-indigo-900 flex items-center gap-1.5"><x-icon name="clipboard-list" class="w-4 h-4" /> Kế hoạch tập</a>
                    <a href="{{ route('members.nutrition-plans.index', $member) }}" class="text-indigo-600 hover:text-indigo-900 flex items-center gap-1.5"><x-icon name="beaker" class="w-4 h-4" /> Kế hoạch dinh dưỡng</a>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <x-icon name="credit-card" class="w-5 h-5 text-gray-400" /> Membership hiện tại
                </h3>
                @if ($currentMembership)
                    <p class="text-sm text-gray-700">
                        Gói: <strong>{{ $currentMembership->package?->name }}</strong><br>
                        Hết hạn: {{ optional($currentMembership->end_date)->format('d/m/Y') }}
                    </p>
                @else
                    <p class="text-sm text-gray-500 mb-3">Hội viên chưa đăng ký gói tập nào.</p>
                    <a href="{{ route('gym.memberships.create') }}" class="text-sm text-indigo-600 font-medium hover:text-indigo-800">+ Đăng ký gói tập &rarr;</a>
                @endif
            </div>

            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2">
                    <x-icon name="scale" class="w-5 h-5 text-gray-400" /> Chỉ số cơ thể gần nhất
                </h3>
                @if ($latestBodyMeasurement)
                    <p class="text-sm text-gray-700">
                        Cân nặng {{ $latestBodyMeasurement->weight }} kg, BMI {{ $latestBodyMeasurement->bmi }}<br>
                        Đo ngày: {{ optional($latestBodyMeasurement->measured_at)->format('d/m/Y') }}
                    </p>
                @else
                    <p class="text-sm text-gray-500">Chưa có dữ liệu đo chỉ số cơ thể.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
