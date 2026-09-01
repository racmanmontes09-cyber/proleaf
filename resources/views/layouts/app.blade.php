@php
    $user = auth()->user();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <script>
            (function () {
                try {
                    var saved = localStorage.getItem('leaf-theme');
                    var dark = saved ? saved === 'dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
                    document.documentElement.classList.toggle('dark', dark);
                } catch (e) {}
            })();
        </script>

        <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">
        <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
        <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
        <link rel="manifest" href="{{ asset('site.webmanifest') }}">
        <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

@php
    $currentRoute = request()->route()?->getName();
    $pageTitles = [
        'dashboard' => 'Dashboard | Project L.E.A.F.',
        'admin.dashboard' => 'Admin Dashboard | Project L.E.A.F.',
        'admin.parameter-logs' => 'Parameter Logs | Project L.E.A.F.',
        'admin.system-parameters' => 'System Parameters | Project L.E.A.F.',
        'admin.users' => 'Users | Project L.E.A.F.',
        'admin.activity-logs' => 'Activity Logs | Project L.E.A.F.',
        'profile' => 'Profile | Project L.E.A.F.',
    ];

    $userName = $user?->name ?? 'Administrator';
    $isAdmin = (bool) ($user?->isAdmin() ?? false);
    $userRoleLabel = $isAdmin ? 'Admin' : ($user?->primaryRoleName() ?? 'Viewer');
    $initials = trim(collect(explode(' ', $userName))->map(fn ($part) => strtoupper(substr($part, 0, 1)))->join('')) ?: 'PL';
    $notificationCount = $isAdmin && \Illuminate\Support\Facades\Schema::hasTable('alerts')
        ? \App\Models\Alert::query()->active()->critical()->count()
        : 0;
    $profileRoute = Route::has('profile') ? route('profile') : null;

    $latestDevice = \App\Models\Device::query()->latest('last_seen_at')->first();
    $esp32IsOnline = (bool) ($latestDevice?->is_online ?? false);
    $esp32Label = $latestDevice ? ($esp32IsOnline ? 'ESP32 Online' : 'ESP32 Offline') : 'ESP32 Offline';
    $adminNavGroups = [
        'MONITORING' => [
            ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'dashboard'],
        ],
        'DATA' => [
            ['label' => 'Parameter Logs', 'route' => 'admin.parameter-logs', 'icon' => 'logs'],
        ],
        'SYSTEM' => [
            ['label' => 'System Parameters', 'route' => 'admin.system-parameters', 'icon' => 'settings'],
            ['label' => 'Users', 'route' => 'admin.users', 'icon' => 'users'],
            ['label' => 'Activity Logs', 'route' => 'admin.activity-logs', 'icon' => 'activity'],
        ],
    ];
@endphp
        <title>{{ $pageTitles[$currentRoute] ?? 'Project L.E.A.F. | IoT-Based Hydroponic Cultivation System' }}</title>

        <!-- Fonts: Inter, Merriweather, JetBrains Mono -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600;700&family=Merriweather:wght@300;400;700&display=swap" rel="stylesheet">

        <!-- ApexCharts CDN & Vite Assets -->
        <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .bg-grid-pattern {
                background-image: radial-gradient(rgba(45, 106, 79, 0.06) 1px, transparent 1px);
                background-size: 24px 24px;
            }
            .dark .bg-grid-pattern {
                background-image: radial-gradient(rgba(148, 163, 184, 0.04) 1px, transparent 1px);
            }
            /* Suppress sidebar/nav transitions on initial page load to prevent flicker */
            .suppress-transitions,
            .suppress-transitions * {
                transition: none !important;
            }
        </style>
    </head>
    <body class="h-full antialiased overflow-x-clip selection:bg-[#95D5B2] selection:text-[#1B4332]" style="background-color: var(--leaf-bg); color: var(--leaf-text);">
        
        <div class="min-h-screen relative overflow-x-clip" style="background-color: var(--leaf-bg);">
            <div class="flex flex-col min-h-screen min-w-0">
                <header x-data="{
                        openMobile: false,
                        openProfile: false,
                        currentTab: 'dashboard',
                        openLiveView: false,
                        lvState: 'idle',
                        lvMessage: '',
                        lvSrc: '',
                        lvAudioUrl: '',
                        audioContext: null,
                        audioReader: null,
                        audioRun: 0,
                        audioNextTime: 0,
                        audioPending: new Uint8Array(),
                        async startLiveView() {
                            this.stopLiveAudio();
                            this.openLiveView = true;
                            this.lvState = 'loading';
                            this.lvMessage = '';
                            this.lvSrc = '';
                            try {
                                const res = await fetch('{{ route('dashboard.camera.stream-url') }}', {
                                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                });
                                if (!res.ok) throw new Error('HTTP ' + res.status);
                                const data = await res.json();
                                if (data.url) {
                                    this.lvSrc = data.url;
                                    this.lvAudioUrl = data.audio_url || '';
                                    this.startLiveAudio();
                                } else {
                                    this.lvState = 'error';
                                    this.lvMessage = data.online === false
                                        ? 'Camera has not checked in recently. Power it on or retry shortly.'
                                        : 'No camera registered yet. Connect the ESP32-S3-CAM to the network.';
                                }
                            } catch (e) {
                                this.lvState = 'error';
                                this.lvMessage = 'Could not reach the server. Check your connection and retry.';
                            }
                        },
                        stopLiveView() {
                            this.stopLiveAudio();
                            this.openLiveView = false;
                            this.lvSrc = '';
                            this.lvAudioUrl = '';
                            this.lvState = 'idle';
                            this.lvMessage = '';
                        },
                        async startLiveAudio() {
                            if (!this.lvAudioUrl) return;
                            this.stopLiveAudio();
                            const run = ++this.audioRun;
                            this.audioContext = this.audioContext || new AudioContext({ sampleRate: 16000 });
                            await this.audioContext.resume();
                            const response = await fetch(this.lvAudioUrl, { cache: 'no-store' });
                            if (!response.body || run !== this.audioRun) return;
                            this.audioReader = response.body.getReader();
                            this.audioNextTime = this.audioContext.currentTime + 0.1;
                            while (run === this.audioRun) {
                                const { value, done } = await this.audioReader.read();
                                if (done) break;
                                let bytes = value;
                                if (this.audioPending.length) {
                                    const merged = new Uint8Array(this.audioPending.length + bytes.length);
                                    merged.set(this.audioPending);
                                    merged.set(bytes, this.audioPending.length);
                                    bytes = merged;
                                }
                                const sampleBytes = bytes.length - (bytes.length % 2);
                                this.audioPending = bytes.slice(sampleBytes);
                                if (!sampleBytes) continue;
                                const samples = new Int16Array(bytes.buffer, bytes.byteOffset, sampleBytes / 2);
                                const buffer = this.audioContext.createBuffer(1, samples.length, 16000);
                                const channel = buffer.getChannelData(0);
                                for (let i = 0; i < samples.length; i++) channel[i] = samples[i] / 32768;
                                const source = this.audioContext.createBufferSource();
                                source.buffer = buffer;
                                source.connect(this.audioContext.destination);
                                this.audioNextTime = Math.max(this.audioNextTime, this.audioContext.currentTime + 0.02);
                                source.start(this.audioNextTime);
                                this.audioNextTime += buffer.duration;
                            }
                        },
                        stopLiveAudio() {
                            this.audioRun++;
                            if (this.audioReader) this.audioReader.cancel().catch(() => {});
                            this.audioReader = null;
                            this.audioPending = new Uint8Array();
                        },
                    }" x-on:switch-tab.window="currentTab = $event.detail" x-on:active-tab-changed.window="currentTab = $event.detail" class="sticky top-0 z-40">
                    <div class="ispsc-topbar w-full max-w-full border-b border-[#2D6A4F]/10 dark:border-white/10 shadow-sm backdrop-blur-sm transition-all duration-300 lg:w-auto"
                        style="background-image: linear-gradient(135deg, color-mix(in srgb, #1B4332 75%, var(--leaf-bg)) 0%, color-mix(in srgb, #2D6A4F 75%, var(--leaf-bg)) 55%, color-mix(in srgb, #40916C 75%, var(--leaf-bg)) 100%);"
                        @if ($isAdmin)
                            x-bind:class="$store.sidebar.collapsed ? 'lg:ml-16' : 'lg:ml-60'"
                        @endif>
                        <div class="mx-auto flex w-full max-w-[1680px] flex-col gap-2 sm:gap-4 px-3 py-3 max-sm:py-[9.5px] sm:px-4 lg:px-6 xl:px-8 2xl:px-10 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex w-full min-w-0 items-center gap-4 lg:w-[34%]">
                                <a href="{{ Route::has('dashboard') ? route('dashboard') : url('/') }}" class="flex items-center gap-4 min-w-0">
                                    <img src="{{ asset('logo/ISPSC.jpg') }}" alt="Project L.E.A.F. logo" class="h-14 w-14 object-contain" />
                                    <div class="min-w-0">
                                        <p class="truncate text-base lg:text-lg max-sm:text-[14px] max-sm:leading-5 font-semibold leading-tight tracking-normal" style="color: #ffffff;">Project L.E.A.F.</p>
                                        <p class="truncate text-xs lg:text-sm lg:leading-5 max-sm:text-[10px] max-sm:leading-4 uppercase tracking-[0.24em] lg:tracking-normal max-sm:tracking-normal opacity-80" style="color: #ffffff;">Hydroponic Automation System</p>
                                    </div>
                                </a>
                            </div>

<div class="flex w-full flex-wrap items-center justify-between gap-4 lg:w-[62%] lg:justify-end">
                                <div class="flex max-sm:w-full flex-wrap items-center justify-center gap-2 text-sm lg:text-[15px] lg:leading-6 max-sm:text-[12px] max-sm:leading-5 font-medium sm:gap-4" style="color: color-mix(in srgb, var(--leaf-text) 85%, transparent);">
                                    <span id="dashboard-date" class="inline-flex items-center gap-2 rounded-full border border-[#2D6A4F]/10 dark:border-white/10 px-3 py-2 max-sm:px-[9px] max-sm:py-[5px] text-sm lg:text-[15px] lg:leading-5 max-sm:text-[12px] max-sm:leading-4 shadow-sm whitespace-nowrap" style="background-color: color-mix(in srgb, var(--leaf-surface) 80%, transparent); color: color-mix(in srgb, var(--leaf-text) 90%, transparent);">{{ now()->format('D, M d') }}</span>

                                    <span id="dashboard-clock-time" class="rounded-full border border-[#2D6A4F]/10 dark:border-white/10 px-3 py-2 max-sm:px-[9px] max-sm:py-[5px] text-sm lg:text-[15px] lg:leading-5 max-sm:text-[12px] max-sm:leading-4 shadow-sm whitespace-nowrap" style="background-color: color-mix(in srgb, var(--leaf-surface) 80%, transparent); color: color-mix(in srgb, var(--leaf-text) 80%, transparent);">{{ now()->format('g:i A') }}</span>

                                    <div class="relative">
                                        <button type="button" @click="startLiveView()"
                                            class="inline-flex items-center gap-2 rounded-full border border-[#2D6A4F]/10 dark:border-white/10 px-3 py-2 max-sm:px-[11px] max-sm:py-[7px] text-sm lg:text-[15px] lg:leading-5 max-sm:text-[12px] max-sm:leading-4 shadow-sm transition hover:bg-[#95D5B2]/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#40916C]/40 cursor-pointer" style="background-color: color-mix(in srgb, var(--leaf-surface) 80%, transparent); color: color-mix(in srgb, var(--leaf-text) 80%, transparent);"
                                            aria-label="Live View">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            <span class="hidden sm:inline text-xs lg:text-sm lg:leading-5 font-medium">Live View</span>
                                        </button>
                                    </div>

                                    <div class="relative max-sm:ml-auto" x-data="{ openProfile: false }" @click.outside="openProfile = false" @keydown.escape.window="openProfile = false">
                                        <button type="button" @click="openProfile = !openProfile"
                                            class="inline-flex items-center gap-2 rounded-full border border-[#2D6A4F]/10 dark:border-white/10 px-1.5 py-1.5 text-left text-sm max-sm:text-[12px] max-sm:leading-5 font-medium transition shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#40916C]/40" style="background-color: var(--leaf-surface); color: var(--leaf-text);"
                                            aria-haspopup="true" :aria-expanded="openProfile">
                                            <span class="relative flex h-9 w-9 max-sm:h-[24px] max-sm:w-[24px] items-center justify-center rounded-full bg-gradient-to-br from-[#2D6A4F] via-[#40916C] to-[#95D5B2] text-xs lg:text-sm lg:leading-5 max-sm:text-[11px] max-sm:leading-4 font-semibold text-white shadow ring-2 ring-white dark:ring-[#1E293B]">
                                                {{ $initials }}
                                                <span class="absolute -right-0.5 -bottom-0.5 flex h-3 w-3 max-sm:h-2.5 max-sm:w-2.5 items-center justify-center rounded-full bg-white ring-2 ring-white dark:bg-[#1E293B] dark:ring-[#1E293B]">
                                                    <span class="h-1.5 w-1.5 max-sm:h-1 max-sm:w-1 rounded-full bg-emerald-500"></span>
                                                </span>
                                            </span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#2D6A4F]/80 dark:text-leaf-300/70" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                            </svg>
                                        </button>

                                        <div x-show="openProfile"
                                            x-transition.opacity.scale.origin.top.right
                                            class="absolute right-0 z-10 mt-3 w-[min(20rem,calc(100vw-1.5rem))] origin-top-right rounded-2xl p-2 shadow-xl ring-1 ring-black/5 dark:ring-white/10 focus:outline-none" style="background-color: var(--leaf-surface); display: none;">
                                            <div class="space-y-2">
                                                <a href="{{ $profileRoute ?? '#' }}" @click="openProfile = false" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm max-sm:text-[12px] max-sm:leading-5 transition hover:bg-[#95D5B2]/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#40916C]/40" style="color: var(--leaf-text);"><span aria-hidden="true">⚙</span>Account Settings</a>
                                                @if (Route::has('preferences'))
                                                    <a href="{{ route('preferences') }}" @click="openProfile = false" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm max-sm:text-[12px] max-sm:leading-5 transition hover:bg-[#95D5B2]/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#40916C]/40" style="color: var(--leaf-text);"><span aria-hidden="true">◐</span>Appearance</a>
                                                @endif
                                                <button type="button" @click="$store.theme.toggle()" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm max-sm:text-[12px] max-sm:leading-5 transition hover:bg-[#95D5B2]/10 dark:hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#40916C]/40 cursor-pointer" style="color: var(--leaf-text);">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#2D6A4F]/70 dark:hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                                                    </svg>
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="hidden h-4 w-4 text-leaf-300 dark:block" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                                                    </svg>
                                                    <span class="flex-1 text-left">Dark Mode</span>
                                                    <span class="text-xs uppercase tracking-wide opacity-60 dark:hidden">Off</span>
                                                    <span class="hidden text-xs uppercase tracking-wide opacity-60 dark:block">On</span>
                                                </button>
                                                @if ($isAdmin && Route::has('admin.activity-logs'))<a href="{{ route('admin.activity-logs') }}" @click="openProfile = false" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm max-sm:text-[12px] max-sm:leading-5 transition hover:bg-[#95D5B2]/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#40916C]/40" style="color: var(--leaf-text);"><span aria-hidden="true">▤</span>Activity Logs</a>@endif
                                                <div class="border-t border-[#2D6A4F]/10 dark:border-white/10 pt-3">
                                                    <form method="POST" action="{{ Route::has('logout') ? route('logout') : '/logout' }}">
                                                        @csrf
                                                        <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm max-sm:text-[12px] max-sm:leading-5 font-semibold text-rose-700 dark:text-rose-400 transition hover:bg-rose-50 dark:hover:bg-rose-950/40 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-300"><span aria-hidden="true">↪</span>Log Out</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>



                <!-- Live View Overlay -->
                <div x-show="openLiveView" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4 max-sm:p-0" @click.self="stopLiveView()" @keydown.escape.window="stopLiveView()">
                    <div x-show="openLiveView" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95 translate-y-4" x-transition:enter-end="opacity-100 scale-100 translate-y-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100 translate-y-0" x-transition:leave-end="opacity-0 scale-95 translate-y-4" class="relative flex h-full w-full max-w-4xl flex-col overflow-hidden rounded-3xl bg-[#1B4332] shadow-2xl max-sm:max-w-none max-sm:rounded-none">
                        <div class="flex items-center justify-between px-5 py-3 bg-[#2D6A4F]/90">
                            <div class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full bg-red-500 animate-pulse"></span>
                                <span class="text-xs sm:text-sm lg:text-base lg:leading-6 font-bold text-white tracking-wide lg:tracking-normal">Live Camera Feed</span>
                            </div>
                            <button type="button" @click="stopLiveView()" class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-white/10 text-white transition hover:bg-white/25 cursor-pointer" aria-label="Close">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <div class="relative aspect-video max-sm:aspect-auto min-h-0 flex-1 bg-black flex items-center justify-center">
                            <img x-show="lvState === 'live'" :src="lvSrc" x-on:load="lvState = 'live'" x-on:error="lvSrc = ''; lvState = 'error'; lvMessage = 'Stream interrupted or camera unreachable.'" class="absolute inset-0 h-full w-full object-contain" alt="Live camera feed">
                            <div x-show="lvState !== 'live'" class="text-center text-white/50 px-6" x-cloak>
                                <svg x-show="lvState === 'loading'" class="h-16 w-16 mx-auto mb-3 animate-spin opacity-60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10" class="opacity-20" />
                                    <path d="M22 12a10 10 0 0 0-10-10" stroke-linecap="round" />
                                </svg>
                                <svg x-show="lvState !== 'loading'" xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-3 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                                <p class="text-xs sm:text-sm lg:text-base lg:leading-6 font-medium" x-text="lvMessage || (lvState === 'loading' ? 'Connecting to camera stream...' : 'Ready to start live view')"></p>
                                <button type="button" x-show="lvState === 'error'" @click="startLiveView()" class="mt-4 inline-flex items-center gap-2 rounded-full bg-[#2D6A4F] px-4 py-2 text-xs lg:text-sm lg:leading-5 font-semibold text-white transition hover:bg-[#40916C] cursor-pointer">
                                    Retry
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                </header>

                @if ($isAdmin)
                    <div class="flex w-full max-w-full min-w-0 flex-1" x-data="adminSidebar()">
                        {{-- Mobile overlay --}}
                        <div x-show="$store.sidebar.mobileOpen" x-cloak x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-40 bg-black/50 lg:hidden" @click="$store.sidebar.mobileOpen = false"></div>

                        {{-- Sidebar --}}
                        <aside
                            x-bind:class="$store.sidebar.mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
                            class="fixed inset-y-0 left-0 z-50 flex flex-col border-r border-white/5 bg-[#1B4332] text-white transition-all duration-300 ease-in-out"
                            x-bind:style="{'width': window.innerWidth >= 1024 ? $store.sidebar.width : '240px', 'min-height': '100vh'}"
                            x-on:mouseenter="if (!$store.sidebar.collapsed) $store.sidebar.expanded = true"
                            x-on:mouseleave="$store.sidebar.expanded = false"
                            aria-label="Admin navigation"
                        >
                            {{-- Sidebar header / collapse toggle --}}
                            <div class="flex items-center border-b border-white/5" x-bind:class="$store.sidebar.collapsed ? 'justify-center px-2 py-3' : 'justify-between px-4 py-3'">
                                <span x-show="!$store.sidebar.collapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="text-[11px] lg:text-sm lg:leading-5 font-bold uppercase tracking-widest lg:tracking-normal text-white/50 truncate">Admin</span>
                                <button type="button" x-on:click="$store.sidebar.toggle()" class="flex h-7 w-7 items-center justify-center rounded-md text-white/60 transition hover:bg-white/10 hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#95D5B2] cursor-pointer" x-bind:aria-label="$store.sidebar.collapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-300" x-bind:class="$store.sidebar.collapsed ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" /></svg>
                                </button>
                            </div>

                            {{-- Navigation groups --}}
                            <nav class="flex-1 overflow-y-auto py-2" aria-label="Admin navigation">
                                @foreach ($adminNavGroups as $groupName => $items)
                                    <div class="mb-1">
                                        <p x-show="!$store.sidebar.collapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="px-4 py-2 text-[10px] lg:text-xs lg:leading-4 font-bold uppercase tracking-[0.12em] lg:tracking-normal text-white/40 select-none">{{ $groupName }}</p>
                                        <p x-show="$store.sidebar.collapsed" class="px-2 py-2 text-center text-[8px] font-bold uppercase text-white/30 select-none" x-bind:title="'{{ $groupName }}'">·</p>
                                        @foreach ($items as $item)
                                            <a
                                                href="{{ route($item['route']) }}"
                                                title="{{ $item['label'] }}"
                                                class="group relative mx-2 flex items-center rounded-lg text-[13px] lg:text-[15px] lg:leading-5 font-medium transition-all duration-200 {{ request()->routeIs($item['route']) ? 'bg-[#40916C]/80 text-white shadow-sm' : 'text-white/65 hover:bg-white/8 hover:text-white' }}"
                                                x-bind:class="$store.sidebar.collapsed ? 'justify-center px-2 py-2.5' : 'gap-3 px-3 py-2.5'"
                                            >
                                                {{-- Icon --}}
                                                <span class="flex h-5 w-5 shrink-0 items-center justify-center" aria-hidden="true">
                                                    @if($item['icon'] === 'dashboard')
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                                                    @elseif($item['icon'] === 'logs')
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                                    @elseif($item['icon'] === 'settings')
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                                    @elseif($item['icon'] === 'users')
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                                                    @elseif($item['icon'] === 'activity')
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-[18px] w-[18px]" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                    @endif
                                                </span>
                                                {{-- Label --}}
                                                <span x-show="!$store.sidebar.collapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="truncate">{{ $item['label'] }}</span>
                                                {{-- Active indicator bar --}}
                                                @if(request()->routeIs($item['route']))
                                                    <span class="absolute left-0 top-1/2 -translate-y-1/2 h-5 w-[3px] rounded-r-full bg-[#95D5B2]"></span>
                                                @endif
                                            </a>
                                        @endforeach
                                    </div>
                                @endforeach
                            </nav>

                            {{-- Sidebar footer --}}
                            <div class="border-t border-white/5 p-2">
                                <a href="{{ $profileRoute ?? '#' }}" title="Profile" class="group flex items-center rounded-lg transition-all duration-200 gap-3 px-3 py-2.5 text-white/50 hover:bg-white/8 hover:text-white" x-bind:class="$store.sidebar.collapsed ? 'justify-center px-2 py-2.5' : 'gap-3 px-3 py-2.5'">
                                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#2D6A4F] via-[#40916C] to-[#95D5B2] text-[11px] lg:text-xs lg:leading-4 font-bold text-white shadow-sm">{{ $initials }}</span>
                                    <span x-show="!$store.sidebar.collapsed" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" class="truncate text-xs lg:text-sm lg:leading-5 font-medium">{{ $userName }}</span>
                                </a>
                            </div>
                        </aside>

                        {{-- Main content --}}
                        <main
                            class="w-full max-w-full min-w-0 flex-1 overflow-x-hidden px-3 pt-4 pb-24 sm:px-4 sm:pt-6 sm:pb-6 lg:w-auto lg:px-6 lg:pb-6 xl:px-8 2xl:px-10 transition-all duration-300 {{ $currentRoute === 'dashboard' ? 'lg:h-[calc(100vh-80px)] lg:overflow-hidden lg:py-4' : '' }}"
                            x-bind:class="$store.sidebar.collapsed ? 'lg:ml-16' : 'lg:ml-60'"
                        >
                            {{ $slot }}
                        </main>

                        {{-- Mobile bottom navigation bar --}}
                        <nav class="fixed bottom-0 left-0 right-0 z-50 flex items-center justify-around border-t border-[#2D6A4F]/10 dark:border-white/10 bg-white/95 dark:bg-[#0F172A]/95 backdrop-blur-sm px-2 py-1.5 pb-[max(0.375rem,env(safe-area-inset-bottom))] lg:hidden" aria-label="Admin navigation">
                            @foreach ($adminNavGroups as $groupName => $items)
                                @foreach ($items as $item)
                                    <a
                                        href="{{ route($item['route']) }}"
                                        title="{{ $item['label'] }}"
                                        aria-label="{{ $item['label'] }}"
                                        class="relative flex flex-col items-center justify-center gap-0.5 rounded-lg px-2 py-1.5 min-w-[48px] transition-all duration-200 {{ request()->routeIs($item['route']) ? 'text-[#2D6A4F] dark:text-leaf-300' : 'text-gray-400 hover:text-[#2D6A4F]/70 dark:text-gray-500 dark:hover:text-leaf-300/70' }}"
                                    >
                                        @if($item['icon'] === 'dashboard')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                                        @elseif($item['icon'] === 'logs')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                        @elseif($item['icon'] === 'settings')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        @elseif($item['icon'] === 'users')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                                        @elseif($item['icon'] === 'activity')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        @endif
                                        <span class="text-[10px] font-semibold leading-tight">{{ substr($item['label'], 0, 8) }}</span>
                                        {{-- Active indicator bar --}}
                                        @if(request()->routeIs($item['route']))
                                            <span class="absolute top-0 left-1/2 -translate-x-1/2 h-[3px] w-5 rounded-b-full bg-[#2D6A4F] dark:bg-leaf-300"></span>
                                        @endif
                                    </a>
                                @endforeach
                            @endforeach
                        </nav>
                    </div>
                @else
                    <main class="flex-1 w-full max-w-[1680px] mx-auto min-w-0 px-3 sm:px-4 lg:px-6 xl:px-8 2xl:px-10 py-4 sm:py-6 lg:py-8 overflow-x-hidden {{ $currentRoute === 'dashboard' ? 'lg:h-[calc(100vh-80px)] lg:overflow-hidden lg:py-4' : '' }}">
                        {{ $slot }}
                    </main>
                @endif


            </div>
        </div>

        <script>
            document.documentElement.classList.add('suppress-transitions');
            window.addEventListener('load', function() {
                requestAnimationFrame(function() {
                    document.documentElement.classList.remove('suppress-transitions');
                });
            });
        </script>
    </body>
</html>
