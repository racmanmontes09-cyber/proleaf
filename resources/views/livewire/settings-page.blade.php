<div class="space-y-6">
    @if ($message)
        <div class="rounded-2xl border {{ $saved ? 'border-[#2D6A4F]/20 dark:border-white/15 bg-[#2D6A4F]/10 dark:bg-white/10 text-[#1B4332] dark:text-slate-100' : 'border-rose-200 dark:border-rose-500/30 bg-rose-50 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400' }} px-4 py-3 text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5">
            {{ $message }}
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-6">
        <div class="rounded-2xl border border-[#2D6A4F]/10 dark:border-white/10 bg-white dark:bg-[#1E293B] p-4 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <x-leaf.page-header
                    title="System Parameters"
                    subtitle="Approved thresholds and device timing values for NFT lettuce monitoring."
                    badge="Admin"
                />
                <div class="flex gap-2">
                    <button type="submit" class="rounded-xl bg-[#2D6A4F] px-4 py-2 text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5 font-semibold text-white">Save Changes</button>
                    <button type="button" wire:click="resetToDefaults" class="rounded-xl border border-[#2D6A4F]/20 dark:border-white/15 bg-white dark:bg-[#1E293B] px-4 py-2 text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5 font-semibold text-[#2D6A4F] dark:text-leaf-300">Reset to Defaults</button>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-[#2D6A4F]/10 dark:border-white/10 bg-white dark:bg-[#1E293B] p-6 shadow-sm">
            <h3 class="text-lg lg:text-xl lg:leading-7 max-sm:text-sm font-semibold text-[#1B4332] dark:text-slate-100">Sensor Thresholds</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @php($thresholds = [
                    ['label' => 'Air Temperature', 'unit' => '°C', 'min' => 'temperature_min', 'max' => 'temperature_max'],
                    ['label' => 'pH', 'unit' => 'pH', 'min' => 'ph_min', 'max' => 'ph_max'],
                    ['label' => 'EC', 'unit' => 'mS/cm', 'min' => 'ec_min', 'max' => 'ec_max'],
                ])

                @foreach ($thresholds as $threshold)
                    <div class="rounded-2xl border border-[#2D6A4F]/10 dark:border-white/10 bg-[#F8FAF8] dark:bg-[#0F172A] p-4">
                        <h4 class="text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5 font-semibold text-[#1B4332] dark:text-slate-100">{{ $threshold['label'] }}</h4>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <label class="text-xs lg:text-sm lg:leading-5 max-sm:text-[12px] max-sm:leading-5 font-semibold text-[#1B4332] dark:text-slate-100">
                                <span>Minimum</span>
                                <input type="number" step="any" wire:model="settings.{{ $threshold['min'] }}" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 dark:border-white/15 bg-white dark:bg-[#0F172A] px-3 py-2 text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5 text-[#1B4332] dark:text-slate-200" />
                                @error('settings.'.$threshold['min']) <span class="mt-1 block text-xs lg:text-sm lg:leading-5 max-sm:text-[12px] max-sm:leading-5 text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                            </label>
                            <label class="text-xs lg:text-sm lg:leading-5 max-sm:text-[12px] max-sm:leading-5 font-semibold text-[#1B4332] dark:text-slate-100">
                                <span>Maximum</span>
                                <input type="number" step="any" wire:model="settings.{{ $threshold['max'] }}" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 dark:border-white/15 bg-white dark:bg-[#0F172A] px-3 py-2 text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5 text-[#1B4332] dark:text-slate-200" />
                                @error('settings.'.$threshold['max']) <span class="mt-1 block text-xs lg:text-sm lg:leading-5 max-sm:text-[12px] max-sm:leading-5 text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                            </label>
                        </div>
                        <p class="mt-2 text-[11px] lg:text-xs lg:leading-4 max-sm:text-[12px] max-sm:leading-4 text-[#2D6A4F]/70 dark:text-leaf-300/70">Unit: {{ $threshold['unit'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-[#2D6A4F]/10 dark:border-white/10 bg-white dark:bg-[#1E293B] p-6 shadow-sm">
            <h3 class="text-lg lg:text-xl lg:leading-7 max-sm:text-sm font-semibold text-[#1B4332] dark:text-slate-100">Automation Timing</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5 font-semibold text-[#1B4332] dark:text-slate-100">
                    <span>Sensor Upload Interval</span>
                    <input type="number" wire:model="settings.sensor_upload_interval" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 dark:border-white/15 bg-white dark:bg-[#0F172A] px-3 py-2 text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5 text-[#1B4332] dark:text-slate-200" />
                    <span class="mt-1 block text-xs lg:text-sm lg:leading-5 max-sm:text-[12px] max-sm:leading-5 text-[#2D6A4F]/70 dark:text-leaf-300/70">Seconds</span>
                    @error('settings.sensor_upload_interval') <span class="mt-1 block text-xs lg:text-sm lg:leading-5 max-sm:text-[12px] max-sm:leading-5 text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                </label>
                <label class="text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5 font-semibold text-[#1B4332] dark:text-slate-100">
                    <span>Heartbeat Interval</span>
                    <input type="number" wire:model="settings.heartbeat_interval" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 dark:border-white/15 bg-white dark:bg-[#0F172A] px-3 py-2 text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5 text-[#1B4332] dark:text-slate-200" />
                    <span class="mt-1 block text-xs lg:text-sm lg:leading-5 max-sm:text-[12px] max-sm:leading-5 text-[#2D6A4F]/70 dark:text-leaf-300/70">Seconds</span>
                    @error('settings.heartbeat_interval') <span class="mt-1 block text-xs lg:text-sm lg:leading-5 max-sm:text-[12px] max-sm:leading-5 text-rose-600 dark:text-rose-400">{{ $message }}</span> @enderror
                </label>
            </div>
        </div>
    </form>
</div>
