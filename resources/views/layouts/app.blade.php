<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'GymHub') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 antialiased">
    @php
        $user = auth()->user();
        $navGroups = [];

        if ($user) {
            if ($user->role === \App\Models\User::ROLE_PLATFORM_ADMIN) {
                $navGroups = [
                    'Tổng quan' => [
                        ['label' => 'Tổng quan nền tảng', 'icon' => 'home', 'route' => 'admin.dashboard'],
                    ],
                    'Quản lý' => [
                        ['label' => 'Quản lý Gym', 'icon' => 'building-office', 'route' => 'admin.gyms.index'],
                    ],
                ];
            } elseif ($user->role === \App\Models\User::ROLE_GYM_OWNER) {
                $navGroups = [
                    'Tổng quan' => [
                        ['label' => 'Tổng quan Gym', 'icon' => 'home', 'route' => 'gym.dashboard'],
                    ],
                    'Quản lý' => [
                        ['label' => 'Hội viên', 'icon' => 'users', 'route' => 'gym.members.index'],
                        ['label' => 'Gói tập', 'icon' => 'tag', 'route' => 'gym.packages.index'],
                        ['label' => 'Khuyến mãi', 'icon' => 'gift', 'route' => 'gym.promotions.index'],
                        ['label' => 'Membership', 'icon' => 'credit-card', 'route' => 'gym.memberships.index'],
                        ['label' => 'Thanh toán', 'icon' => 'banknotes', 'route' => 'gym.payments.index'],
                    ],
                    'Vận hành' => [
                        ['label' => 'Lịch tập', 'icon' => 'calendar', 'route' => 'gym.schedules.index'],
                        ['label' => 'Check-in', 'icon' => 'qr-code', 'route' => 'gym.checkin.index'],
                        ['label' => 'Thiết bị', 'icon' => 'wrench-screwdriver', 'route' => 'gym.equipment.index'],
                        ['label' => 'Báo cáo doanh thu', 'icon' => 'chart-bar', 'route' => 'gym.reports.revenue'],
                    ],
                    'Kết nối' => [
                        ['label' => 'Cộng đồng', 'icon' => 'chat-bubble', 'route' => 'community.index'],
                        ['label' => 'Đánh giá', 'icon' => 'star', 'route' => 'reviews.index'],
                    ],
                ];
            } elseif ($user->role === \App\Models\User::ROLE_STAFF) {
                $navGroups = [
                    'Tổng quan' => [
                        ['label' => 'Tổng quan nhân viên', 'icon' => 'home', 'route' => 'staff.dashboard'],
                    ],
                    'Quản lý' => [
                        ['label' => 'Hội viên', 'icon' => 'users', 'route' => 'gym.members.index'],
                        ['label' => 'Gói tập', 'icon' => 'tag', 'route' => 'gym.packages.index'],
                        ['label' => 'Khuyến mãi', 'icon' => 'gift', 'route' => 'gym.promotions.index'],
                        ['label' => 'Membership', 'icon' => 'credit-card', 'route' => 'gym.memberships.index'],
                        ['label' => 'Thanh toán', 'icon' => 'banknotes', 'route' => 'gym.payments.index'],
                    ],
                    'Vận hành' => [
                        ['label' => 'Lịch tập', 'icon' => 'calendar', 'route' => 'gym.schedules.index'],
                        ['label' => 'Check-in', 'icon' => 'qr-code', 'route' => 'gym.checkin.index'],
                        ['label' => 'Thiết bị', 'icon' => 'wrench-screwdriver', 'route' => 'gym.equipment.index'],
                    ],
                    'Kết nối' => [
                        ['label' => 'Cộng đồng', 'icon' => 'chat-bubble', 'route' => 'community.index'],
                        ['label' => 'Đánh giá', 'icon' => 'star', 'route' => 'reviews.index'],
                    ],
                ];
            } elseif ($user->role === \App\Models\User::ROLE_TRAINER) {
                $navGroups = [
                    'Tổng quan' => [
                        ['label' => 'Tổng quan huấn luyện viên', 'icon' => 'home', 'route' => 'trainer.dashboard'],
                    ],
                    'Kết nối' => [
                        ['label' => 'Cộng đồng', 'icon' => 'chat-bubble', 'route' => 'community.index'],
                        ['label' => 'Đánh giá về tôi', 'icon' => 'star', 'route' => 'reviews.index'],
                    ],
                ];
            } else {
                $myPageItems = [
                    ['label' => 'QR check-in của tôi', 'icon' => 'qr-code', 'route' => 'member.qr.show'],
                    ['label' => 'Đặt lớp', 'icon' => 'calendar', 'route' => 'member.schedules.index'],
                    ['label' => 'Lịch của tôi', 'icon' => 'clock', 'route' => 'member.bookings.index'],
                ];

                if ($currentMember) {
                    $myPageItems[] = ['label' => 'Chỉ số cơ thể', 'icon' => 'scale', 'route' => 'members.measurements.index', 'params' => [$currentMember]];
                    $myPageItems[] = ['label' => 'Kế hoạch tập', 'icon' => 'clipboard-list', 'route' => 'members.workout-plans.index', 'params' => [$currentMember]];
                    $myPageItems[] = ['label' => 'Dinh dưỡng', 'icon' => 'beaker', 'route' => 'members.nutrition-plans.index', 'params' => [$currentMember]];
                }

                $myPageItems[] = ['label' => 'Thanh toán của tôi', 'icon' => 'banknotes', 'route' => 'member.payments.index'];

                $navGroups = [
                    'Tổng quan' => [
                        ['label' => 'Trang chủ', 'icon' => 'home', 'route' => 'member.home'],
                    ],
                    'Của tôi' => $myPageItems,
                    'Kết nối' => [
                        ['label' => 'Cộng đồng', 'icon' => 'chat-bubble', 'route' => 'community.index'],
                        ['label' => 'Đánh giá', 'icon' => 'star', 'route' => 'reviews.index'],
                    ],
                ];
            }
        }
    @endphp

    <div x-data="{ sidebarOpen: false }" class="min-h-screen flex">

        <div
            x-show="sidebarOpen"
            x-cloak
            @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-gray-900/50 lg:hidden"
        ></div>

        {{-- Sidebar --}}
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-40 w-64 bg-gray-900 text-gray-100 transform transition-transform duration-200 ease-in-out lg:static lg:translate-x-0 flex flex-col"
        >
            <div class="h-16 flex items-center gap-2.5 px-5 border-b border-white/10 shrink-0">
                <x-application-logo class="w-8 h-8 text-sm" />
                <div class="min-w-0">
                    <div class="text-base font-semibold tracking-tight text-white leading-tight">GymHub</div>
                    <div class="text-[11px] text-gray-400 leading-tight">Quản lý phòng gym</div>
                </div>
            </div>

            @isset($currentGym)
                <div class="mx-4 mt-3 mb-1 px-3 py-2 rounded-lg bg-white/5 text-sm text-gray-200 font-medium truncate">
                    {{ $currentGym->name }}
                </div>
            @endisset

            <nav class="flex-1 overflow-y-auto px-3 py-3 text-sm">
                @foreach ($navGroups as $sectionLabel => $items)
                    <div class="{{ $loop->first ? 'mt-1' : 'mt-4' }} mb-1 px-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">
                        {{ $sectionLabel }}
                    </div>
                    <div class="space-y-0.5">
                        @foreach ($items as $item)
                            @php
                                $isActive = request()->routeIs($item['route']) || request()->routeIs($item['route'] . '.*');
                                $itemUrl = isset($item['params']) ? route($item['route'], $item['params']) : route($item['route']);
                            @endphp
                            <a
                                href="{{ $itemUrl }}"
                                @class([
                                    'flex items-center gap-3 rounded-lg px-3 py-2 transition-colors',
                                    'bg-indigo-600 text-white shadow-sm' => $isActive,
                                    'text-gray-300 hover:bg-white/10 hover:text-white' => ! $isActive,
                                ])
                            >
                                <x-icon :name="$item['icon']" class="w-5 h-5 shrink-0" />
                                <span class="truncate">{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                @endforeach

                @auth
                    <div class="mt-4 mb-1 px-3 text-[11px] font-semibold uppercase tracking-wider text-gray-500">Tài khoản</div>
                    <div class="space-y-0.5">
                        @php($isProfileActive = request()->routeIs('profile.edit'))
                        <a
                            href="{{ route('profile.edit') }}"
                            @class([
                                'flex items-center gap-3 rounded-lg px-3 py-2 transition-colors',
                                'bg-indigo-600 text-white shadow-sm' => $isProfileActive,
                                'text-gray-300 hover:bg-white/10 hover:text-white' => ! $isProfileActive,
                            ])
                        >
                            <x-icon name="user-circle" class="w-5 h-5 shrink-0" />
                            <span class="truncate">Hồ sơ cá nhân</span>
                        </a>
                    </div>
                @endauth
            </nav>

            <div class="px-5 py-4 border-t border-white/10 text-xs text-gray-500 shrink-0">
                &copy; {{ date('Y') }} GymHub
            </div>
        </aside>

        {{-- Main column --}}
        <div class="flex-1 flex flex-col min-w-0">

            {{-- Topbar --}}
            <header class="h-16 bg-white/90 backdrop-blur border-b border-gray-200 shadow-sm flex items-center justify-between px-4 lg:px-8 sticky top-0 z-20">
                <div class="flex items-center gap-3">
                    <button
                        @click="sidebarOpen = !sidebarOpen"
                        type="button"
                        class="lg:hidden p-2 rounded-md text-gray-500 hover:bg-gray-100"
                        aria-label="Mở menu"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    @isset($header)
                        <div class="text-lg font-semibold text-gray-900 tracking-tight">{{ $header }}</div>
                    @endisset
                </div>

                <div class="flex items-center gap-4">
                    @auth
                        {{-- Chuông thông báo (Khối 2, Ngày 3) --}}
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" type="button" class="relative p-2 rounded-full text-gray-500 hover:bg-gray-100" aria-label="Thông báo">
                                <x-icon name="bell" class="h-6 w-6" />
                                @if ($unreadNotificationsCount > 0)
                                    <span class="absolute -top-0.5 -right-0.5 inline-flex items-center justify-center h-4 min-w-4 px-1 rounded-full bg-red-500 text-white text-[10px] font-semibold">
                                        {{ $unreadNotificationsCount > 9 ? '9+' : $unreadNotificationsCount }}
                                    </span>
                                @endif
                            </button>
                            <div
                                x-show="open"
                                x-cloak
                                @click.outside="open = false"
                                class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-50"
                            >
                                <div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase">Thông báo gần đây</div>
                                @forelse ($recentNotifications as $notification)
                                    <div class="px-4 py-2 border-t border-gray-50 {{ $notification->read_at ? '' : 'bg-indigo-50/40' }}">
                                        <div class="text-sm text-gray-900 font-medium">{{ $notification->title }}</div>
                                        <div class="text-xs text-gray-500 mt-0.5">{{ $notification->created_at->diffForHumans() }}</div>
                                    </div>
                                @empty
                                    <div class="px-4 py-3 text-sm text-gray-500">Chưa có thông báo nào.</div>
                                @endforelse
                                <a href="{{ route('notifications.index') }}" class="block text-center border-t border-gray-100 px-4 py-2 text-sm text-indigo-600 hover:bg-gray-50">
                                    Xem tất cả
                                </a>
                            </div>
                        </div>

                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" type="button" class="flex items-center gap-2 text-sm">
                                <div class="h-9 w-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-semibold">
                                    {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                                </div>
                                <span class="hidden sm:block font-medium text-gray-700">{{ auth()->user()->name }}</span>
                            </button>
                            <div
                                x-show="open"
                                x-cloak
                                @click.outside="open = false"
                                class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-50"
                            >
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Hồ sơ cá nhân</a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-50">
                                        Đăng xuất
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endauth
                </div>
            </header>

            {{-- Flash messages --}}
            <div class="px-4 lg:px-8 max-w-screen-2xl w-full mx-auto">
                @if (session('success'))
                    <div class="mt-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm flex items-center gap-2">
                        <x-icon name="check-circle" class="w-5 h-5 shrink-0" />
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="mt-4 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm flex items-center gap-2">
                        <x-icon name="exclamation-triangle" class="w-5 h-5 shrink-0" />
                        {{ session('error') }}
                    </div>
                @endif
            </div>

            {{-- Page content --}}
            <main class="flex-1 px-4 lg:px-8 py-6 max-w-screen-2xl w-full mx-auto">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
