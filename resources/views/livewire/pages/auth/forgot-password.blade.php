<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        $this->reset('email');

        session()->flash('status', __($status));
    }
}; ?>

<style>
    @media (max-width: 639px) {
        .mobile-dashboard-type * {
            font-size: 7px !important;
        }
    }
</style>

<div class="mobile-dashboard-type min-h-screen grid lg:grid-cols-12 overflow-hidden bg-[#F8FAF8]">

    <!-- ========================================== -->
    <!-- LEFT SIDE: BRANDING & SECURITY VISUAL (45%) -->
    <!-- ========================================== -->
    <div class="hidden lg:flex lg:col-span-5 flex-col justify-between p-12 bg-gradient-to-br from-[#1B4332] via-[#2D6A4F] to-[#1B4332] text-white relative overflow-hidden select-none">
        
        <!-- Ambient Glowing Lights -->
        <div class="absolute -top-24 -left-24 w-96 h-96 bg-[#95D5B2]/20 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-[#40916C]/30 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Top Branding Header -->
        <div class="relative z-10">
            <a href="{{ url('/') }}" wire:navigate class="inline-flex items-center gap-3 group focus:outline-none">
                <div class="w-10 h-10 rounded-xl bg-white/90 border border-white/20 flex items-center justify-center overflow-hidden group-hover:scale-105 transition-transform">
                    <img src="{{ asset('logo/logo.png') }}" alt="Project L.E.A.F. logo" class="h-8 w-8 object-contain" />
                </div>
                <div class="flex flex-col">
                    <span class="text-xl font-extrabold tracking-tight text-white flex items-center gap-2">
                        Project L.E.A.F.
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-[#95D5B2]/20 text-[#95D5B2] border border-[#95D5B2]/30">
                            Security Gateway
                        </span>
                    </span>
                </div>
            </a>
        </div>

        <!-- Middle Content & Security Visual Card -->
        <div class="relative z-10 my-auto space-y-8 py-8">
            
            <div class="space-y-3">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 text-[#95D5B2] text-xs font-semibold border border-white/10 backdrop-blur-md">
                    <span class="w-2 h-2 rounded-full bg-[#95D5B2] animate-pulse"></span>
                    Account Recovery Guard
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white leading-tight">
                    An IoT-Based Hydroponic Cultivation System
                </h1>
                <p class="text-sm sm:text-base text-[#95D5B2]/90 leading-relaxed font-normal">
                    Securely recover access to your hydroponic monitoring system.
                </p>
            </div>

            <!-- Glassmorphism Security Telemetry Preview Card -->
            <div class="p-6 rounded-3xl bg-white/10 backdrop-blur-xl border border-white/15 shadow-2xl space-y-5">
                
                <div class="flex items-center justify-between pb-3 border-b border-white/10 text-xs">
                    <div class="flex items-center gap-2 font-mono text-[#95D5B2]">
                        <!-- Shield Keyhole Heroicon -->
                        <svg class="w-4 h-4 text-[#95D5B2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        ENCRYPTED RESET PIPELINE
                    </div>
                    <span class="text-[11px] px-2 py-0.5 rounded bg-white/10 text-white font-mono">
                        TLS 1.3 Guard
                    </span>
                </div>

                <div class="space-y-3">
                    <div class="p-3.5 rounded-2xl bg-black/20 border border-white/10 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-[#95D5B2]/20 flex items-center justify-center text-[#95D5B2]">
                                🔒
                            </div>
                            <div>
                                <p class="text-xs font-bold text-white">Hardware Controller Access</p>
                                <p class="text-[10px] text-[#95D5B2]">Protected Node Credentials</p>
                            </div>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded bg-[#95D5B2]/20 text-[#95D5B2] font-semibold">SECURE</span>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-black/20 border border-white/10 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-[#95D5B2]/20 flex items-center justify-center text-[#95D5B2]">
                                📧
                            </div>
                            <div>
                                <p class="text-xs font-bold text-white">Verification Link Dispatch</p>
                                <p class="text-[10px] text-[#95D5B2]">Time-Limited Token Generation</p>
                            </div>
                        </div>
                        <span class="text-[10px] px-2 py-0.5 rounded bg-[#95D5B2]/20 text-[#95D5B2] font-semibold">READY</span>
                    </div>
                </div>

            </div>

        </div>

        <!-- Left Side Footer -->
        <div class="relative z-10 text-xs text-[#95D5B2]/70 flex items-center justify-between pt-6 border-t border-white/10">
            <span>© 2026 Project L.E.A.F.</span>
            <span>Project L.E.A.F. • Livewire 3</span>
        </div>

    </div>

    <!-- ========================================== -->
    <!-- RIGHT SIDE: FORGOT PASSWORD FORM CARD (55%) -->
    <!-- ========================================== -->
    <div class="lg:col-span-7 flex flex-col justify-center items-center p-6 sm:p-12 relative min-h-screen">
        
        <div class="w-full max-w-md space-y-8">
            
            <!-- Mobile Brand Header (Visible only on smaller screens) -->
            <div class="lg:hidden text-center space-y-2">
                <a href="{{ url('/') }}" wire:navigate class="inline-flex items-center gap-2.5">
                    <div class="w-10 h-10 rounded-xl bg-white/90 border border-[#2D6A4F]/10 flex items-center justify-center overflow-hidden shadow-md">
                        <img src="{{ asset('logo/logo.png') }}" alt="Project L.E.A.F. logo" class="h-8 w-8 object-contain" />
                    </div>
                    <span class="text-2xl font-extrabold text-[#1B4332]">Project L.E.A.F.</span>
                </a>
                <p class="text-xs text-[#40916C] font-semibold uppercase tracking-wider">
                    IoT-Based Hydroponic Cultivation System
                </p>
            </div>

            <!-- Forgot Password Card Wrapper -->
            <div class="p-0 lg:p-8 xl:p-10 rounded-none lg:rounded-3xl bg-transparent lg:bg-white/90 backdrop-blur-none lg:backdrop-blur-xl border-0 lg:border border-[#2D6A4F]/12 shadow-none lg:shadow-2xl lg:shadow-emerald-950/5 space-y-6">
                
                <!-- Card Header -->
                <div class="space-y-2 text-left">
                    <div class="hidden lg:flex items-center gap-2 text-xs font-bold text-[#2D6A4F] uppercase tracking-wider">
                        🌱 Project L.E.A.F.
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-[#1B4332] tracking-tight">
                        Forgot Password
                    </h2>
                    <p class="text-sm text-[#1B4332]/70 leading-relaxed">
                        Forgot your password? Enter your email address and we'll send you a password reset link.
                    </p>
                </div>

                <!-- Session Status Alert -->
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <!-- Form -->
                <form wire:submit="sendPasswordResetLink" class="space-y-5">
                    
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
                                wire:model="email" 
                                id="email" 
                                type="email" 
                                name="email" 
                                required 
                                autofocus 
                                autocomplete="email"
                                placeholder="name@farm.com"
                                class="w-full pl-11 pr-4 py-3 rounded-xl border border-[#2D6A4F]/20 bg-white/80 text-[#1B4332] placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-[#2D6A4F] focus:border-[#2D6A4F] text-sm transition-all shadow-sm"
                            />
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-1 text-xs text-rose-600 font-semibold" />
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button 
                            type="submit" 
                            class="w-full py-3.5 px-6 rounded-xl font-bold text-white bg-[#2D6A4F] hover:bg-[#1B4332] shadow-lg shadow-[#2D6A4F]/25 hover:shadow-xl hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center gap-2 group focus:outline-none focus:ring-2 focus:ring-[#2D6A4F] focus:ring-offset-2"
                        >
                            <span wire:loading.remove class="flex items-center gap-2">
                                Send Password Reset Link
                                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </span>
                            <span wire:loading class="flex items-center gap-2">
                                <svg class="animate-spin w-5 h-5 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Sending Reset Link...
                            </span>
                        </button>
                    </div>

                </form>

                <!-- Navigation Links Row -->
                <div class="pt-6 border-t border-[#2D6A4F]/10 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                    <a 
                        href="{{ route('login') }}" 
                        wire:navigate 
                        class="inline-flex items-center font-semibold text-[#2D6A4F] hover:text-[#1B4332] transition-colors gap-1.5 group"
                    >
                        <svg class="w-4 h-4 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14"/>
                        </svg>
                        Back to Login
                    </a>

                    <a 
                        href="{{ url('/') }}" 
                        wire:navigate 
                        class="inline-flex items-center font-semibold text-[#1B4332]/70 hover:text-[#2D6A4F] transition-colors gap-1.5"
                    >
                        Return to Home
                    </a>
                </div>

            </div>

        </div>

    </div>

</div>
