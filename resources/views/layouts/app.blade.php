@php
    $user = auth()->user();
@endphp
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

    $userName = $user?->name ?? 'Administrator';
    $isSuperAdmin = (bool) ($user?->isSuperAdmin() ?? false);
    $userRoleLabel = $isSuperAdmin ? 'Super Administrator' : ($user?->primaryRoleName() ?? 'Farmer');
    $initials = trim(collect(explode(' ', $userName))->map(fn ($part) => strtoupper(substr($part, 0, 1)))->join('')) ?: 'PL';
    $profileRoute = Route::has('profile') ? route('profile') : null;

    $latestDevice = \App\Models\Device::query()->latest('last_seen_at')->first();
    $esp32IsOnline = (bool) ($latestDevice?->is_online ?? false);
    $esp32Label = $latestDevice ? ($esp32IsOnline ? 'ESP32 Online' : 'ESP32 Offline') : 'ESP32 Offline';
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
                    <div class="ispsc-topbar sticky top-0 z-40 w-full border-b border-[#2D6A4F]/10 dark:border-white/10 shadow-sm backdrop-blur-sm" style="background-color: color-mix(in srgb, var(--leaf-bg) 95%, transparent);">
                        <div class="max-w-[1680px] mx-auto flex flex-col gap-4 max-sm:gap-2 px-3 py-3 max-sm:px-2 max-sm:py-2 sm:px-4 lg:px-6 xl:px-8 2xl:px-10 lg:flex-row lg:items-center lg:justify-between">
                            <div class="flex items-center gap-4 max-sm:gap-2 min-w-0 lg:w-[34%]">
                                <a href="{{ Route::has('dashboard') ? route('dashboard') : url('/') }}" class="flex items-center gap-3 min-w-0">
                                    <img src="{{ asset('logo/is_logo.png') }}" alt="Project L.E.A.F. is logo" class="h-14 w-14 max-sm:h-5 max-sm:w-5 object-contain" />
                                    <img src="{{ asset('logo/logo.png') }}" alt="Project L.E.A.F. logo" class="h-14 w-14 max-sm:h-5 max-sm:w-5 object-contain" />
                                    <div class="min-w-0">
                                        <p class="text-base max-sm:text-[7px] font-semibold leading-tight tracking-tight whitespace-nowrap" style="color: var(--leaf-text);">Project Leaf</p>
                                        <p class="text-xs max-sm:text-[7px] uppercase tracking-[0.24em] max-sm:tracking-[0.04em] opacity-80 whitespace-nowrap" style="color: var(--leaf-secondary);">Hydroponic Automation System</p>
                                    </div>
                                </a>
                            </div>

                            <div class="flex flex-nowrap items-center justify-between gap-2 max-sm:gap-1 lg:w-[62%] lg:justify-end">
                                <div class="flex flex-nowrap items-center gap-2 max-sm:gap-1 text-sm font-medium" style="color: color-mix(in srgb, var(--leaf-text) 85%, transparent);">
                                    <span id="dashboard-date" class="inline-flex items-center gap-2 rounded-full border border-[#2D6A4F]/10 dark:border-white/10 px-3 py-2 max-sm:px-1 max-sm:py-1 text-sm max-sm:text-[7px] shadow-sm whitespace-nowrap" style="background-color: color-mix(in srgb, var(--leaf-surface) 80%, transparent); color: color-mix(in srgb, var(--leaf-text) 90%, transparent);">{{ now()->format('D, M d') }}</span>

                                    <span id="dashboard-clock-time" class="rounded-full border border-[#2D6A4F]/10 dark:border-white/10 px-3 py-2 max-sm:px-1 max-sm:py-1 text-sm max-sm:text-[7px] shadow-sm whitespace-nowrap" style="background-color: color-mix(in srgb, var(--leaf-surface) 80%, transparent); color: color-mix(in srgb, var(--leaf-text) 80%, transparent);">{{ now()->format('g:i A') }}</span>

                                    <div class="relative">
                                        <button type="button" @click="startLiveView()"
                                            class="inline-flex items-center gap-2 max-sm:gap-0.5 rounded-full border border-[#2D6A4F]/10 dark:border-white/10 px-3 py-2 max-sm:px-1 max-sm:py-1 text-sm max-sm:text-[7px] shadow-sm transition hover:bg-[#95D5B2]/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#40916C]/40 cursor-pointer" style="background-color: color-mix(in srgb, var(--leaf-surface) 80%, transparent); color: color-mix(in srgb, var(--leaf-text) 80%, transparent);"
                                            aria-label="Live View">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                            <span class="hidden sm:inline text-xs font-medium">Live View</span>
                                        </button>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <div class="relative" x-data="{ openProfile: false }" @click.outside="openProfile = false" @keydown.escape.window="openProfile = false">
                                        <button type="button" @click="openProfile = !openProfile"
                                            class="inline-flex items-center gap-1 rounded-full border border-[#2D6A4F]/10 dark:border-white/10 bg-white p-1 shadow-sm transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#40916C]/40" style="background-color: var(--leaf-surface); color: var(--leaf-text);"
                                            aria-haspopup="true" :aria-expanded="openProfile">
                                            <span class="relative flex h-11 w-11 max-sm:h-4 max-sm:w-4 items-center justify-center rounded-full bg-gradient-to-br from-[#2D6A4F] via-[#40916C] to-[#95D5B2] text-sm max-sm:text-[3px] font-semibold text-white shadow-lg ring-2 ring-white">
                                                {{ $initials }}
                                                <span class="absolute -right-0.5 -bottom-0.5 flex h-3.5 w-3.5 items-center justify-center rounded-full bg-white ring-2 ring-white">
                                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                                </span>
                                            </span>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="mr-1 h-4 w-4 text-[#2D6A4F]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                            </svg>
                                        </button>

                                        <div x-show="openProfile"
                                            x-transition.opacity.scale.origin.top.right
                                            class="absolute right-0 z-10 mt-3 w-[min(20rem,calc(100vw-1.5rem))] origin-top-right rounded-2xl p-2 shadow-xl ring-1 ring-black/5 focus:outline-none" style="background-color: var(--leaf-surface);"
                                            style="display: none;">
                                            <div class="space-y-2">
                                                <a href="{{ $profileRoute ?? '#' }}" @click="openProfile = false" class="flex items-center gap-3 rounded-xl px-3 py-2 text-[7px] sm:text-sm transition hover:bg-[#95D5B2]/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#40916C]/40" style="color: var(--leaf-text);"><span aria-hidden="true">⚙</span>Account Settings</a>
                                                @if ($isSuperAdmin)<a href="{{ route('admin.activity-logs') }}" @click="openProfile = false" class="flex items-center gap-3 rounded-xl px-3 py-2 text-[7px] sm:text-sm transition hover:bg-[#95D5B2]/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#40916C]/40" style="color: var(--leaf-text);"><span aria-hidden="true">▤</span>Activity Logs</a>@endif
                                                <div class="border-t border-[#2D6A4F]/10 dark:border-white/10 pt-3">
                                                    <form id="logout-form" method="POST" action="{{ Route::has('logout') ? route('logout') : '/logout' }}">
                                                        @csrf
                                                        <button type="submit" @click="if (!confirm('Are you sure you want to log out?')) { $event.preventDefault(); } openProfile = false" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-[7px] sm:text-sm font-semibold text-rose-700 transition hover:bg-rose-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-300"><span aria-hidden="true">↪</span>Log Out</button>
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
                                <span class="text-[10px] sm:text-sm font-bold text-white tracking-wide">Live Camera Feed</span>
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
                                <p class="text-xs sm:text-sm font-medium" x-text="lvMessage || (lvState === 'loading' ? 'Connecting to camera stream...' : 'Ready to start live view')"></p>
                                <button type="button" x-show="lvState === 'error'" @click="startLiveView()" class="mt-4 inline-flex items-center gap-2 rounded-full bg-[#2D6A4F] px-4 py-2 text-[10px] sm:text-xs font-semibold text-white transition hover:bg-[#40916C] cursor-pointer">
                                    Retry
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                </header>

                @if ($isSuperAdmin)
                    <div class="flex min-w-0 flex-1">
                        <aside class="hidden w-64 shrink-0 border-r border-[#2D6A4F]/10 bg-[#1B4332] text-white lg:flex lg:flex-col" aria-label="Super Admin navigation">
                            <div class="sticky top-0 flex min-h-[calc(100vh-7.5rem)] flex-col p-5">
                                <nav class="space-y-2" aria-label="Super Admin navigation">
                                    <p class="text-xs font-bold uppercase tracking-widest text-white/60 px-3 pb-2 pt-1">Super Admin Console</p>
                                    @foreach ([['Dashboard', 'admin.dashboard', '▦'], ['Users', 'admin.users', '♙'], ['Activity Logs', 'admin.activity-logs', '◷']] as [$label, $routeName, $icon])
                                        <a href="{{ route($routeName) }}" class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition hover:bg-white/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-[#95D5B2] {{ request()->routeIs($routeName) ? 'bg-[#40916C] text-white shadow-sm' : 'text-white/75' }}">
                                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-white/10 text-base" aria-hidden="true">{{ $icon }}</span>
                                            <span>{{ $label }}</span>
                                        </a>
                                    @endforeach
                                </nav>
                            </div>
                        </aside>
                        <main class="min-w-0 flex-1 overflow-x-hidden px-3 py-4 sm:px-4 sm:py-6 lg:px-6 xl:px-8 2xl:px-10">
                            <div class="mb-4 flex gap-2 overflow-x-auto lg:hidden" aria-label="Super Admin navigation">
                                @foreach ([['Dashboard', 'admin.dashboard'], ['Users', 'admin.users'], ['Activity Logs', 'admin.activity-logs']] as [$label, $routeName])
                                    <a href="{{ route($routeName) }}" class="shrink-0 rounded-full px-3 py-2 text-xs font-semibold {{ request()->routeIs($routeName) ? 'bg-[#2D6A4F] text-white' : 'border border-[#2D6A4F]/15 bg-white' }}">{{ $label }}</a>
                                @endforeach
                            </div>
                            {{ $slot }}
                        </main>
                    </div>
                @else
                    <main class="flex-1 w-full max-w-[1680px] mx-auto min-w-0 px-3 sm:px-4 lg:px-6 xl:px-8 2xl:px-10 py-4 sm:py-6 lg:py-8 overflow-x-hidden">
                        {{ $slot }}
                    </main>
                @endif


            </div>
        </div>

    </body>
</html>
