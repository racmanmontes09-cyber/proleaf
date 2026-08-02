<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
        <link rel="manifest" href="{{ asset('site.webmanifest') }}">
        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

        @php
            $currentRoute = request()->route()?->getName();
            $pageTitles = [
                'dashboard' => 'Dashboard | Project L.E.A.F.',
                'profile' => 'Profile | Project L.E.A.F.',
            ];

            $user = auth()->user();
            $userName = $user?->name ?? 'Administrator';
            $userRoleLabel = $user?->roles?->first()?->name ?? 'Administrator';
            $initials = trim(collect(explode(' ', $userName))->map(fn ($part) => strtoupper(substr($part, 0, 1)))->join('')) ?: 'PL';
            $notificationCount = 0;
            if ($user && \Illuminate\Support\Facades\Schema::hasTable('notifications')) {
                try {
                    $notificationCount = $user->unreadNotifications()->count();
                } catch (\Throwable $e) {
                    $notificationCount = 0;
                }
            }
            $profileRoute = Route::has('profile') ? route('profile') : null;

            $latestDevice = \App\Models\Device::query()->latest('last_seen_at')->first();
            $esp32IsOnline = (bool) ($latestDevice?->is_online ?? false);
            $esp32Label = $latestDevice ? ($esp32IsOnline ? 'ESP32 Online' : 'ESP32 Offline') : 'ESP32 Offline';
            $esp32DotClass = $esp32IsOnline ? 'bg-[#2D6A4F]' : 'bg-rose-500';
        @endphp
        <title>{{ $pageTitles[$currentRoute] ?? 'Project L.E.A.F. | IoT-Based Hydroponic Cultivation System' }}</title>

        <!-- Fonts: Inter -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

        <!-- ApexCharts CDN & Vite Assets -->
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            :root {
                --leaf-primary: #2D6A4F;
                --leaf-secondary: #40916C;
                --leaf-accent: #95D5B2;
                --leaf-bg: #F8FAF8;
                --leaf-text: #1B4332;
            }

            body {
                font-family: 'Inter', sans-serif;
                background-color: var(--leaf-bg);
                color: var(--leaf-text);
                overflow-x: hidden;
            }

            .glass-card {
                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border: 1px solid rgba(45, 106, 79, 0.12);
            }

            .bg-grid-pattern {
                background-image: radial-gradient(rgba(45, 106, 79, 0.06) 1px, transparent 1px);
                background-size: 24px 24px;
            }
        </style>
    </head>
    <body class="h-full font-sans antialiased bg-[#F8FAF8] text-[#1B4332] bg-grid-pattern selection:bg-[#95D5B2] selection:text-[#1B4332] overflow-x-hidden">
        
        <div class="min-h-screen bg-[#F8FAF8] relative overflow-x-hidden">
            <div class="flex flex-col min-h-screen min-w-0">
                <header x-data="{ openMobile: false, openProfile: false, currentTab: 'dashboard' }" x-on:switch-tab.window="currentTab = $event.detail" x-on:active-tab-changed.window="currentTab = $event.detail" class="sticky top-0 z-40">
                    <div class="ispsc-topbar bg-[#F8FAF8]/95 border-b border-[#2D6A4F]/10 shadow-sm backdrop-blur-sm">
                        <div class="max-w-[1680px] mx-auto flex flex-col gap-4 px-3 py-3 sm:px-4 lg:px-6 xl:px-8 2xl:px-10 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex items-center gap-4 min-w-0 lg:w-[34%]">
                                <a href="{{ Route::has('dashboard') ? route('dashboard') : url('/') }}" class="flex items-center gap-4 min-w-0">
                                    <img src="{{ asset('logo/logo.png') }}" alt="Project L.E.A.F. logo" class="h-14 w-14 object-contain" />
                                    <div class="min-w-0">
                                        <p class="truncate text-base font-semibold leading-tight tracking-tight text-[#1B4332]">Project L.E.A.F.</p>
                                        <p class="truncate text-xs uppercase tracking-[0.24em] text-[#40916C]/80">Hydroponic Automation System</p>
                                    </div>
                                </a>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-4 lg:w-[62%] lg:justify-end">
                                <div class="flex items-center gap-4 text-sm font-medium text-[#1B4332]/85">
                                    <span class="inline-flex items-center gap-2 rounded-full border border-[#2D6A4F]/10 bg-white/80 px-3 py-2 text-sm text-[#1B4332]/90 shadow-sm">
                                        <span id="esp32-status-dot" class="h-2.5 w-2.5 rounded-full {{ $esp32DotClass }}"></span>
                                        <span id="esp32-status-label">{{ $esp32Label }}</span>
                                    </span>

                                    <span id="dashboard-clock-time" class="rounded-full border border-[#2D6A4F]/10 bg-white/80 px-3 py-2 text-sm text-[#1B4332]/80 shadow-sm whitespace-nowrap">{{ now()->format('g:i A') }}</span>
                                </div>

                                <div class="flex items-center gap-4">
                                    <!-- Logout button removed (secured logout handled server-side) -->

                                    <div class="relative" x-data="{ openProfile: false }" @click.outside="openProfile = false">
                                        <button type="button" @click="openProfile = !openProfile"
                                            class="inline-flex items-center gap-2 rounded-full bg-white border border-[#2D6A4F]/10 px-2 py-2 text-sm font-medium text-[#1B4332] transition shadow-sm hover:bg-[#F8FAF8] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#40916C]/40"
                                            aria-haspopup="true" :aria-expanded="openProfile">
                                            <span class="relative flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-[#2D6A4F] via-[#40916C] to-[#95D5B2] text-sm font-semibold text-white shadow-lg ring-2 ring-white">
                                                {{ $initials }}
                                                <span class="absolute -right-0.5 -bottom-0.5 flex h-3.5 w-3.5 items-center justify-center rounded-full bg-white ring-2 ring-white">
                                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                                </span>
                                            </span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#2D6A4F]/80" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                            </svg>
                                        </button>

                                        <div x-show="openProfile"
                                            x-transition.opacity.scale.origin-top.right
                                            class="absolute right-0 z-10 mt-3 w-56 origin-top-right rounded-2xl bg-white p-2 shadow-xl ring-1 ring-black/5 focus:outline-none"
                                            style="display: none;">
                                            <div class="space-y-2">
                                                <a href="{{ $profileRoute ?? '#' }}" class="block rounded-2xl px-4 py-2 text-sm text-[#1B4332] transition hover:bg-[#95D5B2]/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#40916C]/40">Profile</a>
                                                <a href="{{ $profileRoute ?? '#' }}" class="block rounded-2xl px-4 py-2 text-sm text-[#1B4332] transition hover:bg-[#95D5B2]/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#40916C]/40">Preferences</a>
                                                <div class="border-t border-[#2D6A4F]/10 pt-3">
                                                    <form method="POST" action="{{ Route::has('logout') ? route('logout') : '/logout' }}">
                                                        @csrf
                                                        <button type="submit" class="w-full rounded-2xl bg-[#2D6A4F] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#1B4332] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#40916C]/40">Logout</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ispsc-secondary bg-white/90 border-b border-[#2D6A4F]/10 shadow-sm">
                        <div class="max-w-[1680px] mx-auto flex items-center justify-between gap-4 px-3 py-3 sm:px-4 lg:px-6 xl:px-8 2xl:px-10">
                            <nav class="hidden lg:flex flex-wrap items-center justify-center gap-3 text-sm font-semibold tracking-[0.08em]" aria-label="Primary navigation">
                                @php
                                    $menuItems = [
                                        ['label' => 'Dashboard', 'route' => 'dashboard', 'tab' => 'dashboard'],
                                        ['label' => 'Monitoring', 'route' => 'monitoring', 'tab' => 'monitoring'],
                                        ['label' => 'Analytics', 'route' => 'analytics', 'tab' => 'analytics'],
                                        ['label' => 'Devices', 'route' => 'devices', 'tab' => 'devices'],
                                        ['label' => 'Alerts', 'route' => 'alerts', 'tab' => 'alerts'],
                                        ['label' => 'Reports', 'route' => 'reports', 'tab' => 'reports'],
                                        ['label' => 'Settings', 'route' => 'settings', 'tab' => 'settings'],
                                    ];
                                @endphp
                                @foreach ($menuItems as $item)
                                    @php
                                        $routeExists = Route::has($item['route']);
                                        $tabKey = $item['tab'];
                                    @endphp
                                    <a href="{{ $routeExists ? route($item['route']) : '#' }}"
                                        @click.prevent="$dispatch('switch-tab', '{{ $tabKey }}')"
                                        class="transition duration-150 cursor-pointer rounded-full px-3 py-2 text-sm font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#95D5B2]/30"
                                        :class="currentTab === '{{ $tabKey }}' ? 'bg-[#2D6A4F] text-white shadow-sm' : 'text-[#1B4332]/80 hover:text-[#2D6A4F] hover:bg-[#2D6A4F]/10'"
                                        :aria-current="currentTab === '{{ $tabKey }}' ? 'page' : 'false'">
                                        {{ $item['label'] }}
                                    </a>
                                @endforeach
                            </nav>

                            <button type="button" @click="openMobile = !openMobile"
                                class="inline-flex items-center gap-2 rounded-full border border-[#2D6A4F]/10 bg-white/90 px-3 py-2 text-sm font-semibold text-[#1B4332] transition shadow-sm hover:border-[#2D6A4F]/20 hover:bg-[#F8FAF8] lg:hidden"
                                aria-label="Toggle menu"
                                :aria-expanded="openMobile">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                                Menu
                            </button>
                        </div>

                        <div x-show="openMobile" x-transition x-cloak class="lg:hidden bg-white/95 border-t border-[#2D6A4F]/10 shadow-sm">
                            <div class="max-w-[1680px] mx-auto px-3 py-3 sm:px-4 lg:px-6">
                                <nav class="space-y-2 text-sm font-semibold tracking-[0.08em] text-[#1B4332]" aria-label="Mobile navigation">
                                    @foreach ($menuItems as $item)
                                        @php
                                            $routeExists = Route::has($item['route']);
                                            $tabKey = $item['tab'];
                                        @endphp
                                        <a href="{{ $routeExists ? route($item['route']) : '#' }}"
                                            @click.prevent="$dispatch('switch-tab', '{{ $tabKey }}'); openMobile = false"
                                            class="block rounded-full px-4 py-3 transition duration-150 cursor-pointer"
                                            :class="currentTab === '{{ $tabKey }}' ? 'bg-[#2D6A4F] text-white' : 'text-[#1B4332]/80 hover:bg-[#F8FAF8] hover:text-[#2D6A4F]'"
                                            :aria-current="currentTab === '{{ $tabKey }}' ? 'page' : 'false'">
                                            {{ $item['label'] }}
                                        </a>
                                    @endforeach
                                </nav>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="flex-1 w-full max-w-[1680px] mx-auto min-w-0 px-3 sm:px-4 lg:px-6 xl:px-8 2xl:px-10 py-4 sm:py-6 lg:py-8 overflow-x-hidden">
                    {{ $slot }}
                </main>

                <footer class="mt-auto border-t border-slate-200/80 bg-white/90 text-slate-700 dark:border-slate-700/80 dark:bg-slate-950/95 dark:text-slate-300">
                    <div class="max-w-[1680px] mx-auto px-3 py-5 sm:px-4 lg:px-6 xl:px-8 2xl:px-10">
                        <div class="grid gap-4 md:grid-cols-3 md:items-start">
                            <div class="space-y-1 text-sm text-slate-700 dark:text-slate-300 md:text-left text-center">
                                <p class="font-semibold text-slate-900 dark:text-slate-100">Project L.E.A.F.</p>
                                <p class="text-slate-500 dark:text-slate-400">Lettuce Environment Automation & Farming</p>
                                <p class="text-slate-500 dark:text-slate-400">IoT-Based NFT Hydroponic Cultivation System</p>
                            </div>

                            <div class="space-y-1 text-sm text-slate-700 dark:text-slate-300 text-center">
                                <p class="font-semibold text-slate-900 dark:text-slate-100">Firmware Version: <span class="font-normal text-slate-600 dark:text-slate-400">{{ $latestDevice?->firmware_version ?? 'v1.0.0' }}</span></p>
                                <p class="font-semibold text-slate-900 dark:text-slate-100">Dashboard Version: <span class="font-normal text-slate-600 dark:text-slate-400">v1.0.0</span></p>
                                <p class="font-semibold text-slate-900 dark:text-slate-100">Last Updated: <span class="font-normal text-slate-600 dark:text-slate-400">{{ now()->year }}</span></p>
                            </div>

                            <div class="space-y-1 text-sm text-slate-700 dark:text-slate-300 md:text-right text-center">
                                <p class="font-semibold text-slate-900 dark:text-slate-100">© {{ now()->year }} Project L.E.A.F.</p>
                                <p class="text-slate-500 dark:text-slate-400">BSIT Capstone Project</p>
                                <p class="text-slate-500 dark:text-slate-400">Tagudin, Ilocos Sur</p>
                                <p class="text-slate-500 dark:text-slate-400">Status: <span class="font-semibold {{ $esp32IsOnline ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">{{ $esp32Label }}</span></p>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </div>

    </body>
</html>
