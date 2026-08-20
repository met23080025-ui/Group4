<x-app-layout>
    <x-slot name="header">Đăng ký gói tập</x-slot>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-2xl">
        <p class="text-sm text-gray-500 mb-4">
            Đăng ký tạo ra sẽ ở trạng thái <strong>chờ thanh toán</strong>, chỉ kích hoạt sau khi thanh toán được xác nhận.
        </p>

        <form method="POST" action="{{ route('gym.memberships.store') }}">
            @csrf

            <div class="grid grid-cols-1 gap-6">
                <div>
                    <x-input-label for="member_id" value="Hội viên" />
                    <select id="member_id" name="member_id" required
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">-- Chọn hội viên --</option>
                        @foreach ($members as $member)
                            <option value="{{ $member->id }}" @selected(old('member_id') == $member->id)>
                                {{ $member->member_code }} — {{ $member->user->name }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('member_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="package_id" value="Gói tập" />
                    <select id="package_id" name="package_id" required
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">-- Chọn gói tập --</option>
                        @foreach ($packages as $package)
                            <option value="{{ $package->id }}" @selected(old('package_id') == $package->id)>
                                {{ $package->name }} — {{ number_format($package->price, 0, ',', '.') }} đ ({{ $package->duration_days }} ngày)
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('package_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="promotion_id" value="Khuyến mãi (tuỳ chọn)" />
                    <select id="promotion_id" name="promotion_id"
                        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="">-- Không áp dụng --</option>
                        @foreach ($promotions as $promotion)
                            <option value="{{ $promotion->id }}" @selected(old('promotion_id') == $promotion->id)>
                                {{ $promotion->code }} — {{ $promotion->name }}
                                ({{ $promotion->discount_type === 'percent' ? $promotion->discount_value.'%' : number_format($promotion->discount_value, 0, ',', '.').'đ' }})
                                @if (! $promotion->isValidNow()) — hết hiệu lực @endif
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('promotion_id')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="start_date" value="Ngày bắt đầu (mặc định hôm nay)" />
                    <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" :value="old('start_date')" />
                    <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <x-primary-button>Đăng ký gói tập</x-primary-button>
                <a href="{{ route('gym.memberships.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Hủy</a>
            </div>
        </form>
    </div>
</x-app-layout>
