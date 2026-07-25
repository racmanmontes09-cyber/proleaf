<?php

use App\Livewire\Actions\Logout;
use App\Models\Alert;
use App\Models\Device;
use Livewire\Volt\Component;

new class extends Component
{
    public string $alertBadgeLabel = 'Waiting';

    public string $deviceNameLabel = 'Waiting for device...';

    public string $deviceStatusLabel = 'Waiting for device...';
    /**
     * Log the current user out of the application.
     */
    public function mount(): void
    {
        $this->refreshDeviceStatus();
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    public function refreshDeviceStatus(): void
    {
        $device = Device::query()
            ->latest('last_seen_at')
            ->select(['id', 'device_id', 'name', 'last_seen_at'])
            ->with(['latestTelemetry' => function ($query) {
                $query->latestReading()->select([
                    'id',
                    'device_id',
                    'air_temperature',
                    'humidity',
                    'ph',
                    'ec',
                    'updated_at',
                ]);
            }])
            ->first();

        $this->deviceNameLabel = $device && $device->name
            ? 'Node '.$device->name
            : 'Waiting for device...';
        $this->deviceStatusLabel = $device
            ? ($device->is_online ? 'Online' : 'Offline')
            : 'Waiting for device...';
        $this->alertBadgeLabel = $this->resolveAlertBadgeLabel($device);
    }

    private function resolveAlertBadgeLabel(?Device $device): string
    {
        if ($device === null || $device->latestTelemetry === null) {
            return 'Waiting';
        }

        $alertCount = Alert::query()
            ->forDevice($device)
            ->active()
            ->count();

        return $alertCount === 0 ? '0 Active' : $alertCount.' Active';
    }

    private function resolveStatus(float $value, float $low, float $high): string
    {
        if ($value < $low) {
            return 'LOW';
        }

        if ($value > $high) {
            return 'HIGH';
        }

        return 'NORMAL';
    }
}; ?>

<div wire:poll.visible.15s>

    <!-- ========================================== -->
    <!-- 1. FIXED DESKTOP SIDEBAR (256px / w-64)    -->
    <!-- ========================================== -->
    <aside class="hidden lg:flex flex-col w-64 shrink-0 bg-[#1B4332] text-white border-r border-[#2D6A4F]/30 fixed inset-y-0 left-0 z-30 select-none justify-between overflow-x-hidden min-w-0">
        
        <!-- Upper Section: Brand & Nav Links -->
        <div class="flex flex-col overflow-y-auto">
            
            <!-- Sidebar Brand Header -->
            <div class="h-16 px-6 flex items-center justify-between border-b border-[#2D6A4F]/30 shrink-0">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3 group focus:outline-none">
                    <div class="w-9 h-9 rounded-xl bg-[#2D6A4F] flex items-center justify-center text-white shadow-md group-hover:scale-105 transition-transform">
                        🌱
                    </div>
                    <div class="flex flex-col">
                        <span class="text-sm font-extrabold tracking-tight text-white flex items-center gap-1.5">
                            Project L.E.A.F.
                        </span>
                        <span class="text-[10px] font-semibold text-[#95D5B2]">
                            IoT Automation Hub
                        </span>
                    </div>
                </a>
            </div>

            <!-- Sidebar Navigation Menu Links -->
            <div class="p-4 space-y-6">
                
                <div class="space-y-1">
                    <span class="px-3 text-[10px] font-bold uppercase tracking-wider text-[#95D5B2]/70">
                        Main Menu
                    </span>

                    <!-- Dashboard Link -->
                    <button 
                        @click="activeTab = 'dashboard'; sidebarOpen = false" 
                        type="button"
                        :class="activeTab === 'dashboard' ? 'bg-[#2D6A4F] text-white shadow-md shadow-[#2D6A4F]/30 font-bold' : 'text-[#95D5B2]/80 hover:text-white hover:bg-[#2D6A4F]/30 font-medium'"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs transition-all text-left"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <span class="truncate">Dashboard</span>
                    </button>

                    <!-- Monitoring Link -->
                    <a 
                        href="{{ route('monitoring') }}"
                        wire:navigate
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs transition-all text-left {{ request()->routeIs('monitoring') ? 'bg-[#2D6A4F] text-white shadow-md shadow-[#2D6A4F]/30 font-bold' : 'text-[#95D5B2]/80 hover:text-white hover:bg-[#2D6A4F]/30 font-medium' }}"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 010-7.778M12 20a9.99 9.99 0 000-14M15.889 16.404a5.5 5.5 0 000-7.778M12 12h.01"/>
                            </svg>
                            <span class="truncate">Monitoring</span>
                        </div>
                    </a>

                    <!-- Analytics Link -->
                    <a 
                        href="{{ route('analytics') }}"
                        wire:navigate
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs transition-all text-left {{ request()->routeIs('analytics') ? 'bg-[#2D6A4F] text-white shadow-md shadow-[#2D6A4F]/30 font-bold' : 'text-[#95D5B2]/80 hover:text-white hover:bg-[#2D6A4F]/30 font-medium' }}"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/>
                            </svg>
                            <span class="truncate">Analytics</span>
                        </div>
                    </a>

                    <!-- Devices Link -->
                    <a 
                        href="{{ route('device-management') }}"
                        wire:navigate
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs transition-all text-left {{ request()->routeIs('device-management') ? 'bg-[#2D6A4F] text-white shadow-md shadow-[#2D6A4F]/30 font-bold' : 'text-[#95D5B2]/80 hover:text-white hover:bg-[#2D6A4F]/30 font-medium' }}"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M3 9h2m-2 6h2m14-6h2m-2 6h2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                            </svg>
                            <span class="truncate">Device Management</span>
                        </div>
                    </a>

                    <!-- Alerts Link -->
                    <a 
                        href="{{ route('alerts-logs') }}"
                        wire:navigate
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs transition-all text-left {{ request()->routeIs('alerts-logs') ? 'bg-[#2D6A4F] text-white shadow-md shadow-[#2D6A4F]/30 font-bold' : 'text-[#95D5B2]/80 hover:text-white hover:bg-[#2D6A4F]/30 font-medium' }}"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span class="truncate">Alerts & Logs</span>
                        </div>
                        <span class="px-2 py-0.5 text-[9px] font-bold bg-[#95D5B2] text-[#1B4332] rounded-full shrink-0">{{ $alertBadgeLabel }}</span>
                    </a>

                    <!-- Settings Link -->
                    <a 
                        href="{{ route('settings') }}"
                        wire:navigate
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs transition-all text-left {{ request()->routeIs('settings') ? 'bg-[#2D6A4F] text-white shadow-md shadow-[#2D6A4F]/30 font-bold' : 'text-[#95D5B2]/80 hover:text-white hover:bg-[#2D6A4F]/30 font-medium' }}"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            <span class="truncate">System Settings</span>
                        </div>
                    </a>

                    <!-- Reports Link -->
                    <a 
                        href="{{ route('reports') }}"
                        wire:navigate
                        class="w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs transition-all text-left {{ request()->routeIs('reports') ? 'bg-[#2D6A4F] text-white shadow-md shadow-[#2D6A4F]/30 font-bold' : 'text-[#95D5B2]/80 hover:text-white hover:bg-[#2D6A4F]/30 font-medium' }}"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <span class="truncate">Reports & Export</span>
                        </div>
                    </a>
                </div>

                <div class="space-y-1 pt-4 border-t border-[#2D6A4F]/30">
                    <span class="px-3 text-[10px] font-bold uppercase tracking-wider text-[#95D5B2]/70">
                        Account & System
                    </span>

                    <!-- Profile Link -->
                    <a 
                        href="{{ route('profile') }}" 
                        wire:navigate 
                        class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-medium transition-all {{ request()->routeIs('profile') ? 'bg-[#2D6A4F] text-white shadow-md' : 'text-[#95D5B2]/80 hover:text-white hover:bg-[#2D6A4F]/30' }}"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        <span class="truncate">Profile Settings</span>
                    </a>

                    <!-- Logout Action Button -->
                    <button 
                        wire:click="logout" 
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-medium text-rose-300 hover:text-rose-100 hover:bg-rose-900/30 transition-all text-left"
                    >
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span class="truncate">Log Out</span>
                    </button>
                </div>

            </div>
        </div>

        <!-- Sidebar Footer Status Card -->
        <div class="p-4 border-t border-[#2D6A4F]/30 bg-black/20 shrink-0">
            <div class="p-3 rounded-xl bg-[#2D6A4F]/30 border border-[#2D6A4F]/50 flex items-center gap-3">
                <span class="relative flex h-2.5 w-2.5 shrink-0">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#95D5B2] opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-[#95D5B2]"></span>
                </span>
                <div class="min-w-0">
                    <p class="text-xs font-bold text-white truncate">{{ $deviceNameLabel }}</p>
                    <p class="text-[10px] text-[#95D5B2] truncate">{{ $deviceStatusLabel }}</p>
                </div>
            </div>
        </div>

    </aside>

    <!-- ========================================== -->
    <!-- 2. FIXED TOP NAVIGATION BAR (HEADER)       -->
    <!-- ========================================== -->
    <header class="fixed top-0 left-0 right-0 lg:left-64 z-20 bg-white/95 backdrop-blur-md border-b border-[#2D6A4F]/10 h-16 flex items-center justify-between px-3 sm:px-4 lg:px-6 xl:px-8 shadow-sm">

        
        <!-- Left Header: Mobile Toggle & System Title -->
        <div class="flex items-center gap-3 sm:gap-4 min-w-0">
            <button 
                @click="sidebarOpen = !sidebarOpen" 
                type="button" 
                class="lg:hidden p-2 rounded-xl text-[#1B4332] hover:bg-[#2D6A4F]/10 focus:outline-none shrink-0"
                aria-label="Toggle Mobile Drawer"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            <div class="flex items-center gap-3 min-w-0">
                <span class="text-sm font-bold text-[#1B4332] flex items-center gap-2 truncate">
                    🌱 Project L.E.A.F. Control Hub
                </span>
                <span class="hidden sm:inline-block h-4 w-px bg-gray-200 shrink-0"></span>
                <span class="hidden sm:inline-block text-xs font-mono text-[#40916C] shrink-0">
                    {{ date('Y-m-d H:i') }} TST
                </span>
            </div>
        </div>

        <!-- Right Header: Telemetry Status & User Profile -->
        <div class="flex items-center gap-3 sm:gap-4 shrink-0">
            
            <div class="hidden md:flex items-center gap-2 px-3 py-1 rounded-full bg-[#2D6A4F]/10 border border-[#2D6A4F]/20 text-xs font-semibold text-[#2D6A4F] shrink-0">
                <span class="w-2 h-2 rounded-full bg-[#2D6A4F] animate-pulse"></span>
                Live 5s Polling
            </div>

            <!-- Notifications Button -->
            <button type="button" class="relative p-2 rounded-xl text-[#1B4332] hover:bg-[#2D6A4F]/10 transition-colors focus:outline-none shrink-0" aria-label="Notifications">
                <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-emerald-500"></span>
                <svg class="w-5 h-5 text-[#1B4332]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </button>

            <!-- User Profile Dropdown -->
            <div class="relative shrink-0" x-data="{ open: false }">
                <button 
                    @click="open = !open" 
                    type="button" 
                    class="flex items-center gap-3 p-1.5 rounded-xl hover:bg-[#2D6A4F]/10 transition-colors focus:outline-none"
                >
                    <div class="w-8 h-8 rounded-lg bg-[#2D6A4F] text-white flex items-center justify-center font-bold text-xs shadow-sm shrink-0">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                    <div class="hidden sm:flex flex-col text-left min-w-0">
                        <span class="text-xs font-bold text-[#1B4332] truncate max-w-[120px]">
                            {{ auth()->user()->name }}
                        </span>
                        <span class="text-[10px] text-[#40916C] truncate max-w-[120px]">
                            Operator
                        </span>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <!-- Dropdown Menu -->
                <div 
                    x-show="open" 
                    @click.away="open = false" 
                    x-transition 
                    class="absolute right-0 mt-2 w-48 rounded-2xl bg-white border border-[#2D6A4F]/10 shadow-xl py-2 z-50 text-xs"
                >
                    <div class="px-4 py-2 border-b border-gray-100">
                        <p class="font-bold text-[#1B4332] truncate">{{ auth()->user()->name }}</p>
                        <p class="text-gray-400 truncate">{{ auth()->user()->email }}</p>
                    </div>

                    <a href="{{ route('profile') }}" wire:navigate class="flex items-center gap-2 px-4 py-2 text-[#1B4332] hover:bg-[#2D6A4F]/10 transition-colors">
                        <svg class="w-4 h-4 text-[#2D6A4F]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Profile Settings
                    </a>

                    <button wire:click="logout" class="w-full flex items-center gap-2 px-4 py-2 text-rose-600 hover:bg-rose-50 transition-colors text-left">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Log Out
                    </button>
                </div>
            </div>

        </div>

    </header>

    <!-- ========================================== -->
    <!-- 3. MOBILE DRAWER NAVIGATION (SLIDE-OVER)   -->
    <!-- ========================================== -->
    <div 
        x-show="sidebarOpen" 
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm lg:hidden"
        @click="sidebarOpen = false"
    >
        <div 
            @click.stop 
            class="w-64 bg-[#1B4332] text-white min-h-screen p-6 space-y-6 flex flex-col justify-between"
        >
            <div class="space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-[#2D6A4F]/30">
                    <span class="font-extrabold text-white flex items-center gap-2">
                        🌱 Project L.E.A.F.
                    </span>
                    <button @click="sidebarOpen = false" class="text-white p-1">✕</button>
                </div>

                <div class="space-y-1.5 text-xs">
                    <a href="{{ route('dashboard') }}" wire:navigate @click="sidebarOpen = false" class="block px-3 py-2 rounded-xl {{ request()->routeIs('dashboard') ? 'text-white font-semibold bg-[#2D6A4F]/30' : 'text-[#95D5B2] hover:bg-[#2D6A4F]/30 transition-colors' }}">📊 Dashboard</a>
                    <a href="{{ route('monitoring') }}" wire:navigate @click="sidebarOpen = false" class="block px-3 py-2 rounded-xl {{ request()->routeIs('monitoring') ? 'text-white font-semibold bg-[#2D6A4F]/30' : 'text-[#95D5B2] hover:bg-[#2D6A4F]/30 transition-colors' }}">📡 Monitoring</a>
                    <a href="{{ route('analytics') }}" wire:navigate @click="sidebarOpen = false" class="block px-3 py-2 rounded-xl {{ request()->routeIs('analytics') ? 'text-white font-semibold bg-[#2D6A4F]/30' : 'text-[#95D5B2] hover:bg-[#2D6A4F]/30 transition-colors' }}">📈 Analytics</a>
                    <a href="{{ route('device-management') }}" wire:navigate @click="sidebarOpen = false" class="block px-3 py-2 rounded-xl {{ request()->routeIs('device-management') ? 'text-white font-semibold bg-[#2D6A4F]/30' : 'text-[#95D5B2] hover:bg-[#2D6A4F]/30 transition-colors' }}">⚡ Device Management</a>
                    <a href="{{ route('alerts-logs') }}" wire:navigate @click="sidebarOpen = false" class="block px-3 py-2 rounded-xl {{ request()->routeIs('alerts-logs') ? 'text-white font-semibold bg-[#2D6A4F]/30' : 'text-[#95D5B2] hover:bg-[#2D6A4F]/30 transition-colors' }}">🔔 Alerts & Logs</a>
                    <a href="{{ route('settings') }}" wire:navigate @click="sidebarOpen = false" class="block px-3 py-2 rounded-xl {{ request()->routeIs('settings') ? 'text-white font-semibold bg-[#2D6A4F]/30' : 'text-[#95D5B2] hover:bg-[#2D6A4F]/30 transition-colors' }}">⚙️ System Settings</a>
                    <a href="{{ route('reports') }}" wire:navigate @click="sidebarOpen = false" class="block px-3 py-2 rounded-xl {{ request()->routeIs('reports') ? 'text-white font-semibold bg-[#2D6A4F]/30' : 'text-[#95D5B2] hover:bg-[#2D6A4F]/30 transition-colors' }}">📋 Reports & Export</a>
                    <a href="{{ route('profile') }}" wire:navigate class="block px-3 py-2 rounded-xl text-[#95D5B2] hover:bg-[#2D6A4F]/30 transition-colors">👤 Profile Settings</a>
                    <button wire:click="logout" class="w-full text-left px-3 py-2 rounded-xl text-rose-300 hover:bg-rose-900/30 transition-colors">🚪 Log Out</button>
                </div>

            </div>
        </div>
    </div>

</div>
