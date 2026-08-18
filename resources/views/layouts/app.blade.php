<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GymHub') — {{ config('app.name', 'GymHub') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 text-gray-900 antialiased">
    <div class="min-h-screen flex">

        {{-- Overlay cho mobile khi sidebar mở --}}
        <div id="sidebar-overlay" class="fixed inset-0 z-30 bg-gray-900/50 hidden lg:hidden"></div>

        {{-- Sidebar --}}
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-40 w-64 bg-gray-900 text-gray-100 -translate-x-full transform transition-transform duration-200 ease-in-out lg:static lg:translate-x-0 flex flex-col">
            <div class="h-16 flex items-center gap-2 px-5 border-b border-gray-800">
                <div class="h-8 w-8 rounded-lg bg-emerald-500 flex items-center justify-center font-bold text-gray-900">G</div>
                <span class="text-lg font-semibold tracking-tight">GymHub</span>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                @yield('sidebar')
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
                        id="sidebar-toggle"
                        type="button"
                        class="lg:hidden p-2 rounded-md text-gray-500 hover:bg-gray-100"
                        aria-label="Mở menu"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    @isset($currentGym)
                        <div class="hidden sm:flex items-center gap-2 text-sm text-gray-600">
                            <span class="font-medium text-gray-900">{{ $currentGym->name }}</span>
                        </div>
                    @endisset
                </div>

                <div class="flex items-center gap-4">
                    {{-- Notification bell --}}
                    <button type="button" class="relative p-2 rounded-md text-gray-500 hover:bg-gray-100" aria-label="Thông báo">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </button>

                    {{-- User menu --}}
                    @auth
                        <div class="relative">
                            <button id="user-menu-toggle" type="button" class="flex items-center gap-2 text-sm">
                                <div class="h-9 w-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-semibold">
                                    {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                                </div>
                                <span class="hidden sm:block font-medium text-gray-700">{{ auth()->user()->name }}</span>
                            </button>
                            <div id="user-menu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-50">
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Hồ sơ cá nhân</a>
                                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">Đổi mật khẩu</a>
                                <form method="POST" action="#">
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
                <div class="mb-6">
                    <h1 class="text-2xl font-semibold text-gray-900">@yield('page-title', 'Trang chủ')</h1>
                </div>
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        (function () {
            var sidebar = document.getElementById('sidebar');
            var overlay = document.getElementById('sidebar-overlay');
            var toggleBtn = document.getElementById('sidebar-toggle');

            function openSidebar() {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
            }

            function closeSidebar() {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
            }

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function () {
                    sidebar.classList.contains('-translate-x-full') ? openSidebar() : closeSidebar();
                });
            }
            if (overlay) {
                overlay.addEventListener('click', closeSidebar);
            }

            var userMenuToggle = document.getElementById('user-menu-toggle');
            var userMenu = document.getElementById('user-menu');
            if (userMenuToggle && userMenu) {
                userMenuToggle.addEventListener('click', function (e) {
                    e.stopPropagation();
                    userMenu.classList.toggle('hidden');
                });
                document.addEventListener('click', function () {
                    userMenu.classList.add('hidden');
                });
            }
        })();
    </script>
</body>
</html>
