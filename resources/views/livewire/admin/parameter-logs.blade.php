<div class="space-y-6">
    <x-leaf.page-header
        title="Parameter Logs"
        subtitle="Historical telemetry records for NFT lettuce monitoring."
        badge="Telemetry"
    />

    <form wire:submit.prevent="$refresh" class="rounded-2xl border border-[#2D6A4F]/10 dark:border-white/10 bg-white dark:bg-[#1E293B] p-4 shadow-sm">
        <div class="grid gap-4 md:grid-cols-4">
            <label class="text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5 font-semibold text-[#1B4332] dark:text-slate-200">
                <span>Device</span>
                <select wire:model="deviceId" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 dark:border-white/15 bg-white dark:bg-[#0F172A] px-3 py-2 text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5 text-[#1B4332] dark:text-slate-200">
                    <option value="">All devices</option>
                    @foreach ($devices as $device)
                        <option value="{{ $device->id }}">{{ $device->name ?: $device->device_id }}</option>
                    @endforeach
                </select>
            </label>

            <label class="text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5 font-semibold text-[#1B4332] dark:text-slate-200">
                <span>From</span>
                <input type="date" wire:model="fromDate" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 dark:border-white/15 bg-white dark:bg-[#0F172A] px-3 py-2 text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5 text-[#1B4332] dark:text-slate-200" />
            </label>

            <label class="text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5 font-semibold text-[#1B4332] dark:text-slate-200">
                <span>To</span>
                <input type="date" wire:model="toDate" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 dark:border-white/15 bg-white dark:bg-[#0F172A] px-3 py-2 text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5 text-[#1B4332] dark:text-slate-200" />
            </label>

            <div class="flex items-end gap-2">
                <button type="submit" class="rounded-xl bg-[#2D6A4F] px-4 py-2 text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5 font-semibold text-white">Apply</button>
                <button type="button" wire:click="resetFilters" class="rounded-xl border border-[#2D6A4F]/20 dark:border-white/15 bg-white dark:bg-[#1E293B] px-4 py-2 text-sm lg:text-base lg:leading-6 max-sm:text-[13px] max-sm:leading-5 font-semibold text-[#2D6A4F] dark:text-leaf-300">Reset</button>
            </div>
        </div>
    </form>

    <div class="overflow-x-auto rounded-2xl border border-[#2D6A4F]/10 dark:border-white/10 bg-white dark:bg-[#1E293B] shadow-sm">
        <table class="min-w-full divide-y divide-[#2D6A4F]/10 dark:divide-white/10 text-left text-sm lg:text-sm lg:leading-6 max-sm:text-[13px] max-sm:leading-5">
            <thead class="bg-[#F8FAF8] dark:bg-[#0F172A] text-xs lg:text-sm lg:leading-5 max-sm:text-[12px] max-sm:leading-5 uppercase tracking-normal text-[#1B4332]/70 dark:text-slate-400">
                <tr>
                    <th class="px-5 py-3">Timestamp</th>
                    <th class="px-5 py-3">Device</th>
                    <th class="px-5 py-3">pH</th>
                    <th class="px-5 py-3">EC</th>
                    <th class="px-5 py-3">Air Temp</th>
                    <th class="px-5 py-3">Water Temp</th>
                    <th class="px-5 py-3">Water Flow</th>
                    <th class="px-5 py-3">Water Level</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#2D6A4F]/10 dark:divide-white/10 dark:text-slate-300">
                @php
                    $formatValue = fn ($value, int $decimals = 1) => $value !== null ? number_format((float) $value, $decimals) : '--';
                @endphp
                @forelse ($telemetryLogs as $log)
                    @php($timestamp = $log->measured_at ?? $log->received_at ?? $log->created_at)
                    <tr class="text-[#1B4332] dark:text-slate-200">
                        <td class="whitespace-nowrap px-5 py-4 text-xs lg:text-sm lg:leading-5 max-sm:text-[12px] max-sm:leading-5 text-[#2D6A4F]/80 dark:text-leaf-300">{{ $timestamp?->format('M d, Y g:i A') ?? '--' }}</td>
                        <td class="px-5 py-4">
                            <div class="font-semibold">{{ $log->device?->name ?: ($log->device?->device_id ?? 'Unknown device') }}</div>
                            <div class="text-xs lg:text-sm lg:leading-5 max-sm:text-[12px] max-sm:leading-5 text-[#2D6A4F]/70 dark:text-leaf-300">{{ $log->device?->device_id ?? 'No device ID' }}</div>
                        </td>
                        <td class="whitespace-nowrap px-5 py-4 font-mono">{{ $formatValue($log->ph) }}</td>
                        <td class="whitespace-nowrap px-5 py-4 font-mono">{{ $formatValue($log->ec) }} mS/cm</td>
                        <td class="whitespace-nowrap px-5 py-4 font-mono">{{ $formatValue($log->air_temperature) }} °C</td>
                        <td class="whitespace-nowrap px-5 py-4 font-mono">{{ $formatValue($log->water_temperature) }} °C</td>
                        <td class="whitespace-nowrap px-5 py-4 font-mono">{{ $formatValue($log->water_flow) }} L/min</td>
                        <td class="whitespace-nowrap px-5 py-4 font-mono">{{ $formatValue($log->water_level, 0) }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-5 py-12 text-center text-[#1B4332]/70 dark:text-slate-200">No telemetry records match the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $telemetryLogs->links() }}
</div>
