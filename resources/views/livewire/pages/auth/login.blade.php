<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="min-h-screen grid lg:grid-cols-12 overflow-hidden bg-[#F8FAF8]">

    <!-- ========================================== -->
    <!-- LEFT SIDE: BRANDING & DASHBOARD VISUAL (45%) -->
    <!-- ========================================== -->
    <div class="hidden lg:flex lg:col-span-5 flex-col justify-between p-12 bg-gradient-to-br from-[#1B4332] via-[#2D6A4F] to-[#1B4332] text-white relative overflow-hidden select-none">
        
        <!-- Ambient Glowing Lights -->
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-[#95D5B2]/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-[#40916C]/30 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Top Branding Header -->
        <div class="relative z-10">
            <a href="{{ url('/') }}" wire:navigate class="inline-flex items-center gap-3 group focus:outline-none">
                <div class="w-10 h-10 rounded-xl bg-[#95D5B2]/20 border border-[#95D5B2]/30 flex items-center justify-center text-white backdrop-blur-md group-hover:scale-105 transition-transform">
                    <!-- Leaf Icon -->
                    <svg class="w-6 h-6 text-[#95D5B2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                    </svg>
                </div>
                <div class="flex flex-col">
                    <span class="text-xl font-extrabold tracking-tight text-white flex items-center gap-2">
                        Project L.E.A.F.
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#95D5B2]/20 text-[#95D5B2] border border-[#95D5B2]/30">
                            IoT System
                        </span>
                    </span>
                </div>
            </a>
        </div>

        <!-- Middle Content & Dashboard Visual Preview -->
        <div class="relative z-10 my-auto space-y-8 py-8">
            
            <div class="space-y-3">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-[#95D5B2] text-xs font-semibold border border-white/10 backdrop-blur-md">
                    <span class="w-2 h-2 rounded-full bg-[#95D5B2] animate-pulse"></span>
                    Automated Lettuce Farm Hub
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white leading-tight">
                    An IoT-Based Hydroponic Cultivation System
                </h1>
                <p class="text-sm sm:text-base text-[#95D5B2]/90 leading-relaxed font-normal">
                    Monitor your hydroponic farm in real time with intelligent IoT monitoring and automation.
                </p>
            </div>

            <!-- Glassmorphism Visual Dashboard Card -->
            <div class="p-6 rounded-3xl bg-white/10 backdrop-blur-xl border border-white/15 shadow-2xl space-y-5">
                
                <!-- Card Header -->
                <div class="flex items-center justify-between pb-3 border-b border-white/10 text-xs">
                    <div class="flex items-center gap-2 font-mono text-[#95D5B2]">
                        <span class="w-2 h-2 rounded-full bg-[#95D5B2] animate-ping"></span>
                        ESP32 TELEMETRY ONLINE
                    </div>
                    <span class="text-[11px] px-2 py-0.5 rounded bg-white/10 text-white font-mono">
                        Lactuca sativa
                    </span>
                </div>

                <!-- Telemetry Metrics Grid -->
                <div class="grid grid-cols-2 gap-3">
                    <div class="p-3 rounded-2xl bg-black/20 border border-white/10">
                        <span class="text-[11px] text-[#95D5B2] block">Water Temp</span>
                        <span class="text-lg font-bold text-white">22.4 °C</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-black/20 border border-white/10">
                        <span class="text-[11px] text-[#95D5B2] block">Solution pH</span>
                        <span class="text-lg font-bold text-white">6.20 pH</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-black/20 border border-white/10">
                        <span class="text-[11px] text-[#95D5B2] block">Nutrient EC</span>
                        <span class="text-lg font-bold text-white">1.82 mS</span>
                    </div>
                    <div class="p-3 rounded-2xl bg-black/20 border border-white/10">
                        <span class="text-[11px] text-[#95D5B2] block">Water Level</span>
                        <span class="text-lg font-bold text-white">88 %</span>
                    </div>
                </div>

                <!-- Live Sparkline Graphic -->
                <div class="pt-2">
                    <div class="flex items-center justify-between text-[11px] text-[#95D5B2] mb-1 font-mono">
                        <span>24h pH Stability Index</span>
                        <span>OPTIONAL TARGET</span>
                    </div>
                    <div class="h-10 w-full">
                        <svg class="w-full h-full" viewBox="0 0 300 50" fill="none">
                            <path d="M0,35 Q 50,20 100,28 T 200,18 T 300,22" fill="none" stroke="#95D5B2" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </div>
                </div>

            </div>

        </div>

        <!-- Left Side Footer -->
        <div class="relative z-10 text-xs text-[#95D5B2]/70 flex items-center justify-between pt-6 border-t border-white/10">
            <span>© 2026 Project L.E.A.F.</span>
            <span>Project L.E.A.F. • Livewire 3 • ESP32</span>
        </div>

    </div>

    <!-- ========================================== -->
    <!-- RIGHT SIDE: LOGIN FORM CARD (55%)          -->
    <!-- ========================================== -->
    <div class="lg:col-span-7 flex flex-col justify-center items-center p-6 sm:p-12 relative min-h-screen">
        
        <!-- Subtle Ambient Background Pattern -->
        <div class="w-full max-w-md space-y-8">
            
            <!-- Mobile Brand Header (Visible only on smaller screens) -->
            <div class="lg:hidden text-center space-y-2">
                <a href="{{ url('/') }}" wire:navigate class="inline-flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-[#2D6A4F] flex items-center justify-center text-white shadow-md">
                        🌱
                    </div>
                    <span class="text-2xl font-extrabold text-[#1B4332]">Project L.E.A.F.</span>
                </a>
                <p class="text-xs text-[#40916C] font-semibold uppercase tracking-wider">
                    IoT-Based Hydroponic Cultivation System
                </p>
            </div>

            <!-- Login Card Wrapper -->
            <div class="p-8 sm:p-10 rounded-3xl bg-white/90 backdrop-blur-xl border border-[#2D6A4F]/12 shadow-2xl shadow-emerald-950/5 space-y-6">
                
                <!-- Card Welcome Header -->
                <div class="space-y-2 text-left">
                    <div class="hidden lg:flex items-center gap-2 text-xs font-bold text-[#2D6A4F] uppercase tracking-wider">
                        🌱 Project L.E.A.F.
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-[#1B4332] tracking-tight">
                        Welcome Back
                    </h2>
                    <p class="text-sm text-[#1B4332]/70">
                        Sign in to access your hydroponic monitoring dashboard.
                    </p>
                </div>

                <!-- Session Status Alert -->
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <!-- Login Form -->
                <form wire:submit="login" class="space-y-5" x-data="{ showPassword: false }">
                    
                    <!-- Email Field -->
                    <div class="space-y-1.5">
                        <label for="email" class="block text-xs font-bold text-[#1B4332] uppercase tracking-wider">
                            Email Address
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#2D6A4F]/60">
                                <!-- Mail Heroicon -->
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <input 
                                wire:model="form.email" 
                                id="email" 
                                type="email" 
                                name="email" 
                                required 
                                autofocus 
                                autocomplete="username"
                                placeholder="name@farm.com"
                                class="w-full pl-11 pr-4 py-3 rounded-xl border border-[#2D6A4F]/20 bg-white/80 text-[#1B4332] placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#2D6A4F] focus:border-[#2D6A4F] text-sm transition-all shadow-sm"
                            />
                        </div>
                        <x-input-error :messages="$errors->get('form.email')" class="mt-1 text-xs text-rose-600 font-semibold" />
                    </div>

                    <!-- Password Field -->
                    <div class="space-y-1.5">
                        <label for="password" class="block text-xs font-bold text-[#1B4332] uppercase tracking-wider">
                            Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#2D6A4F]/60">
                                <!-- Lock Heroicon -->
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                </svg>
                            </div>
                            <input 
                                wire:model="form.password" 
                                id="password" 
                                :type="showPassword ? 'text' : 'password'" 
                                name="password" 
                                required 
                                autocomplete="current-password"
                                placeholder="••••••••"
                                class="w-full pl-11 pr-11 py-3 rounded-xl border border-[#2D6A4F]/20 bg-white/80 text-[#1B4332] placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#2D6A4F] focus:border-[#2D6A4F] text-sm transition-all shadow-sm"
                            />
                            <!-- Toggle Password Visibility Button -->
                            <button 
                                type="button" 
                                @click="showPassword = !showPassword" 
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-gray-400 hover:text-[#2D6A4F] focus:outline-none"
                                title="Toggle password visibility"
                            >
                                <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="showPassword" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.017 10.017 0 014.288-.936c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21M3 3l18 18"/>
                                </svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('form.password')" class="mt-1 text-xs text-rose-600 font-semibold" />
                    </div>

                    <!-- Remember Me & Forgot Password Row -->
                    <div class="flex items-center justify-between pt-1">
                        <label for="remember" class="inline-flex items-center cursor-pointer select-none">
                            <input 
                                wire:model="form.remember" 
                                id="remember" 
                                type="checkbox" 
                                name="remember" 
                                class="w-4 h-4 rounded text-[#2D6A4F] border-gray-300 focus:ring-[#2D6A4F]"
                            >
                            <span class="ml-2 text-xs font-semibold text-[#1B4332]/80">Remember me</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a 
                                href="{{ route('password.request') }}" 
                                wire:navigate 
                                class="text-xs font-semibold text-[#2D6A4F] hover:text-[#1B4332] transition-colors hover:underline"
                            >
                                Forgot password?
                            </a>
                        @endif
                    </div>

                    <!-- Sign In Submit Button -->
                    <div class="pt-2">
                        <button 
                            type="submit" 
                            class="w-full py-3.5 px-6 rounded-xl font-bold text-white bg-[#2D6A4F] hover:bg-[#1B4332] shadow-lg shadow-[#2D6A4F]/25 hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center gap-2 group focus:outline-none focus:ring-2 focus:ring-[#2D6A4F] focus:ring-offset-2"
                        >
                            <span wire:loading.remove class="flex items-center gap-2">
                                Sign In
                                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </span>
                            <span wire:loading class="flex items-center gap-2">
                                <svg class="animate-spin w-5 h-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Signing in...
                            </span>
                        </button>
                    </div>

                </form>

                <!-- Return to Welcome Page Link -->
                <div class="pt-6 border-t border-[#2D6A4F]/10 text-center">
                    <a 
                        href="{{ url('/') }}" 
                        wire:navigate 
                        class="inline-flex items-center text-xs font-semibold text-[#1B4332]/70 hover:text-[#2D6A4F] transition-colors gap-1.5 group"
                    >
                        <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Back to Home
                    </a>
                </div>

            </div>

        </div>

    </div>

</div>
