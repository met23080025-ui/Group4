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
            <div class="h-16 flex items-center gap-2 px-5 border-b border-gray-800">
                <x-application-logo class="w-8 h-8 text-sm" />
                <span class="text-lg font-semibold tracking-tight">GymHub</span>
            </div>

            @isset($currentGym)
                <div class="px-5 py-3 border-b border-gray-800 text-sm text-gray-300">
                    {{ $currentGym->name }}
                </div>
            @endisset

            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 text-sm">
                @auth
                    @php($user = auth()->user())

                    @if ($user->role === \App\Models\User::ROLE_PLATFORM_ADMIN)
                        <a href="{{ url('/admin') }}" class="block rounded-lg px-3 py-2 hover:bg-gray-800">Tổng quan nền tảng</a>
                    @elseif ($user->role === \App\Models\User::ROLE_GYM_OWNER)
                        <a href="{{ url('/gym/dashboard') }}" class="block rounded-lg px-3 py-2 hover:bg-gray-800">Tổng quan Gym</a>
                        <a href="{{ url('/gym/members') }}" class="block rounded-lg px-3 py-2 hover:bg-gray-800">Hội viên</a>
                        <a href="{{ url('/gym/packages') }}" class="block rounded-lg px-3 py-2 hover:bg-gray-800">Gói tập</a>
                        <a href="{{ url('/gym/promotions') }}" class="block rounded-lg px-3 py-2 hover:bg-gray-800">Khuyến mãi</a>
                        <a href="{{ url('/gym/memberships') }}" class="block rounded-lg px-3 py-2 hover:bg-gray-800">Membership</a>
                        <a href="{{ url('/gym/payments') }}" class="block rounded-lg px-3 py-2 hover:bg-gray-800">Thanh toán</a>
                    @elseif ($user->role === \App\Models\User::ROLE_STAFF)
                        <a href="{{ url('/staff/dashboard') }}" class="block rounded-lg px-3 py-2 hover:bg-gray-800">Tổng quan nhân viên</a>
                        <a href="{{ url('/gym/members') }}" class="block rounded-lg px-3 py-2 hover:bg-gray-800">Hội viên</a>
                        <a href="{{ url('/gym/packages') }}" class="block rounded-lg px-3 py-2 hover:bg-gray-800">Gói tập</a>
                        <a href="{{ url('/gym/promotions') }}" class="block rounded-lg px-3 py-2 hover:bg-gray-800">Khuyến mãi</a>
                        <a href="{{ url('/gym/memberships') }}" class="block rounded-lg px-3 py-2 hover:bg-gray-800">Membership</a>
                        <a href="{{ url('/gym/payments') }}" class="block rounded-lg px-3 py-2 hover:bg-gray-800">Thanh toán</a>
                    @elseif ($user->role === \App\Models\User::ROLE_TRAINER)
                        <a href="{{ url('/trainer/dashboard') }}" class="block rounded-lg px-3 py-2 hover:bg-gray-800">Tổng quan huấn luyện viên</a>
                    @else
                        <a href="{{ url('/home') }}" class="block rounded-lg px-3 py-2 hover:bg-gray-800">Trang chủ</a>
                        <a href="{{ url('/payments') }}" class="block rounded-lg px-3 py-2 hover:bg-gray-800">Thanh toán của tôi</a>
                    @endif

                    <a href="{{ route('profile.edit') }}" class="block rounded-lg px-3 py-2 hover:bg-gray-800">Hồ sơ cá nhân</a>
                @endauth
            </nav>

            <div class="px-3 py-4 border-t border-gray-800 text-xs text-gray-400">
                &copy; {{ date('Y') }} GymHub
            </div>
        </aside>

        {{-- Main column --}}
        <div class="flex-1 flex flex-col min-w-0">

            {{-- Topbar --}}
            <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 lg:px-6 sticky top-0 z-20">
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
                        <div class="text-lg font-semibold text-gray-900">{{ $header }}</div>
                    @endisset
                </div>

                <div class="flex items-center gap-4">
                    @auth
                        {{-- Chuông thông báo (Khối 2, Ngày 3) --}}
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" type="button" class="relative p-2 rounded-full text-gray-500 hover:bg-gray-100" aria-label="Thông báo">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
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
            <div class="px-4 lg:px-6">
                @if (session('success'))
                    <div class="mt-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="mt-4 rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                        {{ session('error') }}
                    </div>
                @endif
            </div>

            {{-- Page content --}}
            <main class="flex-1 px-4 lg:px-6 py-6">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
