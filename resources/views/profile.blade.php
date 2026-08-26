<x-app-layout>
    <style>
        @media (max-width: 639px) {
            .mobile-dashboard-type * {
                font-size: 7px !important;
            }
        }
    </style>

    @php
        $user = auth()->user();
        $assignedDevice = App\Models\Device::query()->latest('last_seen_at')->first();
    @endphp

    <div class="mobile-dashboard-type space-y-3 sm:space-y-8">
        
        <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-[#2D6A4F] transition hover:text-[#1B4332]">
            <span aria-hidden="true">&larr;</span>
            Back to Dashboard
        </a>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 sm:gap-6">
            
            <!-- Left Info Column -->
            <div class="lg:col-span-4 space-y-3 sm:space-y-6">
                <div class="p-3 sm:p-6 rounded-3xl bg-white border border-[#2D6A4F]/10 shadow-sm space-y-3 sm:space-y-6">
                    <div class="flex items-center gap-4 pb-4 border-b border-gray-100">
                        <div class="w-16 h-16 rounded-2xl bg-[#2D6A4F] text-white flex items-center justify-center text-2xl font-bold shadow-md">
                            {{ strtoupper(substr($user?->name ?? 'U', 0, 1)) }}
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-[#1B4332]">{{ $user?->name ?? 'Not available' }}</h3>
                            <p class="text-xs text-[#40916C] font-mono">{{ $user?->email ?? 'Not available' }}</p>
                            <span class="inline-block mt-1 px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-[#95D5B2]/30 text-[#1B4332]">
                                Account Operator
                            </span>
                        </div>
                    </div>

                    <div class="space-y-3 text-xs">
                        <div class="flex justify-between py-1.5 border-b border-gray-50">
                            <span class="text-gray-500">Account Created</span>
                            <span class="font-mono text-gray-700">{{ optional($user?->created_at)->format('M d, Y') ?? 'Not available' }}</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-gray-50">
                            <span class="text-gray-500">Security Status</span>
                            <span class="font-bold text-[#2D6A4F]">Not available</span>
                        </div>
                        <div class="flex justify-between py-1.5">
                            <span class="text-gray-500">Assigned Node</span>
                            <span class="font-mono text-[#2D6A4F] font-semibold">{{ $assignedDevice?->device_id ?? 'Waiting for device...' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Forms Column -->
            <div class="lg:col-span-8 space-y-3 sm:space-y-6">
                
                <!-- Profile Info Form -->
                <div class="p-3 sm:p-6 bg-white rounded-3xl border border-[#2D6A4F]/10 shadow-sm">
                    <div class="max-w-xl">
                        <livewire:profile.update-profile-information-form />
                    </div>
                </div>

                <!-- Update Password Form -->
                <div class="p-3 sm:p-6 bg-white rounded-3xl border border-[#2D6A4F]/10 shadow-sm">
                    <div class="max-w-xl">
                        <livewire:profile.update-password-form />
                    </div>
                </div>

                <!-- Delete User Form -->
                <div class="p-3 sm:p-6 bg-rose-50/50 rounded-3xl border border-rose-200 shadow-sm">
                    <div class="max-w-xl">
                        <livewire:profile.delete-user-form />
                    </div>
                </div>

            </div>

        </div>

    </div>
</x-app-layout>

