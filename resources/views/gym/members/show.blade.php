<x-app-layout>
    <x-slot name="header">Hội viên {{ $member->member_code }}</x-slot>

    <div class="mb-4 flex items-center justify-between">
        <a href="{{ route('gym.members.index') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Quay lại danh sách hội viên</a>
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

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 shadow-sm p-6">
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
                    <dd class="mt-1 text-gray-900">{{ $member->user->phone ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Ngày sinh</dt>
                    <dd class="mt-1 text-gray-900">{{ optional($member->date_of_birth)->format('d/m/Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Giới tính</dt>
                    <dd class="mt-1 text-gray-900">{{ $member->gender ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Ngày tham gia</dt>
                    <dd class="mt-1 text-gray-900">{{ optional($member->joined_at)->format('d/m/Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Chiều cao / Cân nặng</dt>
                    <dd class="mt-1 text-gray-900">{{ $member->height ?? '—' }} cm / {{ $member->weight ?? '—' }} kg</dd>
                </div>
                <div>
                    <dt class="text-gray-500">Liên hệ khẩn cấp</dt>
                    <dd class="mt-1 text-gray-900">{{ $member->emergency_contact ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-gray-500">Địa chỉ</dt>
                    <dd class="mt-1 text-gray-900">{{ $member->address ?? '—' }}</dd>
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
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-3">Trạng thái</h3>
                <span @class([
                    'px-2 py-0.5 rounded-full text-xs font-medium',
                    'bg-emerald-100 text-emerald-700' => $member->status === 'active',
                    'bg-amber-100 text-amber-700' => $member->status === 'expired',
                    'bg-red-100 text-red-700' => $member->status === 'blocked',
                ])>{{ $member->status }}</span>
            </div>

            {{-- Membership: dữ liệu thật ở Khối 8 (Ngày 2). Hiển thị an toàn khi chưa có gói nào. --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-3">Membership hiện tại</h3>
                @if ($currentMembership)
                    <p class="text-sm text-gray-700">
                        Gói: <strong>{{ $currentMembership->package?->name }}</strong><br>
                        Hết hạn: {{ optional($currentMembership->end_date)->format('d/m/Y') }}
                    </p>
                @else
                    <p class="text-sm text-gray-500">Hội viên chưa đăng ký gói tập nào. Chức năng đăng ký gói sẽ hoàn thiện ở Khối 8.</p>
                @endif
            </div>

            {{-- Chỉ số cơ thể: dữ liệu thật ở Ngày 2 (body_measurements). Hiển thị an toàn khi chưa có. --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-3">Chỉ số cơ thể gần nhất</h3>
                @if ($latestBodyMeasurement)
                    <p class="text-sm text-gray-700">
                        Cân nặng: {{ $latestBodyMeasurement->weight }} kg —
                        BMI: {{ $latestBodyMeasurement->bmi }} <br>
                        Đo ngày: {{ optional($latestBodyMeasurement->measured_at)->format('d/m/Y') }}
                    </p>
                @else
                    <p class="text-sm text-gray-500">Chưa có dữ liệu đo chỉ số cơ thể. Sẽ được PT/hội viên cập nhật ở Ngày 2.</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
