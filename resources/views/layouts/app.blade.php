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
                <header class="w-full bg-white/95 border-b border-[#2D6A4F]/10 shadow-sm sticky top-0 z-40 backdrop-blur-sm">
                    <div class="max-w-[1680px] mx-auto px-3 sm:px-4 lg:px-6 xl:px-8 2xl:px-10 py-3 md:py-4 flex flex-col md:flex-row items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="rounded-2xl bg-[#2D6A4F] px-3 py-2 text-white text-xs font-semibold uppercase tracking-[0.24em]">L.E.A.F.</div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-[#1B4332] truncate">Project L.E.A.F.</p>
                                <p class="text-xs text-[#1B4332]/70 truncate">Hydroponic Automation</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 text-sm font-medium text-[#1B4332]/85">
                            <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded-2xl transition duration-150 {{ request()->routeIs('dashboard') ? 'bg-[#2D6A4F] text-white' : 'hover:bg-[#95D5B2]/20' }}">Dashboard</a>
                            <span class="px-3 py-2 rounded-2xl text-[#1B4332]/60 bg-[#F1F5F2]">Devices</span>
                            <span class="px-3 py-2 rounded-2xl text-[#1B4332]/60 bg-[#F1F5F2]">Alerts</span>
                            @if(
                                method_exists(
                                    
                                    Illuminate\Support\Facades\Route::class,
                                    'has'
                                ) && Route::has('settings')
                            )
                                <a href="{{ route('settings') }}" class="px-3 py-2 rounded-2xl transition duration-150 {{ request()->routeIs('settings') ? 'bg-[#2D6A4F] text-white' : 'hover:bg-[#95D5B2]/20' }}">Settings</a>
                            @else
                                <span class="px-3 py-2 rounded-2xl text-[#1B4332]/60 bg-[#F1F5F2]">Settings</span>
                            @endif
                        </div>

                        <div class="flex items-center gap-3">
                            <form method="POST" action="{{ Route::has('logout') ? route('logout') : '/logout' }}">
                                @csrf
                                <button type="submit" class="px-3 py-2 rounded-2xl bg-[#2D6A4F] text-white text-sm font-semibold transition duration-150 hover:bg-[#1B4332]">Logout</button>
                            </form>
                        </div>
                    </div>
                </header>

                <main class="flex-1 w-full max-w-[1680px] mx-auto min-w-0 px-3 sm:px-4 lg:px-6 xl:px-8 2xl:px-10 py-4 sm:py-6 lg:py-8 overflow-x-hidden">
                    {{ $slot }}
                </main>
            </div>
        </div>

    </body>
</html>
