<div class="space-y-6">
    @if ($message)
        <div class="rounded-2xl border {{ $saved ? 'border-[#2D6A4F]/20 bg-[#2D6A4F]/10 text-[#1B4332]' : 'border-rose-200 bg-rose-50 text-rose-700' }} px-4 py-3 text-sm">
            {{ $message }}
        </div>
    @endif

    <div class="glass-card rounded-2xl border border-[#2D6A4F]/10 p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-[#1B4332]">System Settings</h2>
                <p class="text-sm text-[#2D6A4F]/80">Configure thresholds, automation, notifications, greenhouse defaults, and device behavior.</p>
            </div>
            <div class="flex gap-2">
                <button wire:click="save" class="rounded-xl bg-[#2D6A4F] px-4 py-2 text-sm font-semibold text-white">Save Changes</button>
                <button wire:click="resetToDefaults" class="rounded-xl border border-[#2D6A4F]/20 bg-white px-4 py-2 text-sm font-semibold text-[#2D6A4F]">Reset to Defaults</button>
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="glass-card rounded-2xl border border-[#2D6A4F]/10 p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-[#1B4332]">Sensor Thresholds</h3>
            <p class="mt-1 text-sm text-[#2D6A4F]/80">Configure the minimum and maximum bounds for the telemetry values emitted by your devices.</p>
            <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @php($thresholds = [
                    ['key' => 'temperature', 'label' => 'Air Temperature', 'unit' => '°C', 'min' => 'temperature_min', 'max' => 'temperature_max'],
                    ['key' => 'humidity', 'label' => 'Humidity', 'unit' => '%', 'min' => 'humidity_min', 'max' => 'humidity_max'],
                    ['key' => 'water_temperature', 'label' => 'Water Temperature', 'unit' => '°C', 'min' => 'water_temperature_min', 'max' => 'water_temperature_max'],
                    ['key' => 'ph', 'label' => 'pH', 'unit' => '', 'min' => 'ph_min', 'max' => 'ph_max'],
                    ['key' => 'ec', 'label' => 'EC', 'unit' => 'mS/cm', 'min' => 'ec_min', 'max' => 'ec_max'],
                    ['key' => 'water_flow', 'label' => 'Water Flow', 'unit' => 'L/min', 'min' => 'water_flow_min', 'max' => 'water_flow_max'],
                    ['key' => 'water_level', 'label' => 'Water Level', 'unit' => '%', 'min' => 'water_level_min', 'max' => 'water_level_max'],
                ])

                @foreach ($thresholds as $threshold)
                    <div class="rounded-2xl border border-[#2D6A4F]/10 bg-[#F8FAF8] p-4">
                        <h4 class="text-sm font-semibold text-[#1B4332]">{{ $threshold['label'] }}</h4>
                        <p class="mt-1 text-xs text-[#2D6A4F]/70">Minimum and maximum limits for {{ strtolower($threshold['label']) }}.</p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            <label class="text-xs font-semibold text-[#1B4332]">
                                <span>Minimum</span>
                                <input type="number" step="any" wire:model="settings.{{ $threshold['min'] }}" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]" />
                            </label>
                            <label class="text-xs font-semibold text-[#1B4332]">
                                <span>Maximum</span>
                                <input type="number" step="any" wire:model="settings.{{ $threshold['max'] }}" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]" />
                            </label>
                        </div>
                        <p class="mt-2 text-[11px] text-[#2D6A4F]/70">Unit: {{ $threshold['unit'] ?: 'standard' }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="glass-card rounded-2xl border border-[#2D6A4F]/10 p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-[#1B4332]">Automation</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Sensor Upload Interval (seconds)</span>
                    <input type="number" wire:model="settings.sensor_upload_interval" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]" />
                </label>
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Heartbeat Interval</span>
                    <input type="number" wire:model="settings.heartbeat_interval" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]" />
                </label>
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Auto Refresh Interval</span>
                    <input type="number" wire:model="settings.auto_refresh_interval" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]" />
                </label>
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Fan Activation Temperature</span>
                    <input type="number" step="any" wire:model="settings.fan_activation_temperature" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]" />
                </label>
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Pump Delay</span>
                    <input type="number" wire:model="settings.pump_delay" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]" />
                </label>
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Automatic Dosing</span>
                    <select wire:model="settings.automatic_dosing" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]">
                        <option value="1">Enabled</option>
                        <option value="0">Disabled</option>
                    </select>
                </label>
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Automatic Irrigation</span>
                    <select wire:model="settings.automatic_irrigation" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]">
                        <option value="1">Enabled</option>
                        <option value="0">Disabled</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="glass-card rounded-2xl border border-[#2D6A4F]/10 p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-[#1B4332]">Notifications</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Dashboard Notifications</span>
                    <select wire:model="settings.dashboard_notifications" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]">
                        <option value="1">Enabled</option>
                        <option value="0">Disabled</option>
                    </select>
                </label>
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Browser Notifications</span>
                    <select wire:model="settings.browser_notifications" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]">
                        <option value="1">Enabled</option>
                        <option value="0">Disabled</option>
                    </select>
                </label>
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Alert Cooldown</span>
                    <input type="number" wire:model="settings.alert_cooldown" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]" />
                </label>
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Critical Alert Repeat</span>
                    <input type="number" wire:model="settings.critical_alert_repeat" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]" />
                </label>
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Enable Notifications</span>
                    <select wire:model="settings.enable_notifications" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]">
                        <option value="1">Enabled</option>
                        <option value="0">Disabled</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="glass-card rounded-2xl border border-[#2D6A4F]/10 p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-[#1B4332]">Greenhouse</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Greenhouse Name</span>
                    <input type="text" wire:model="settings.greenhouse_name" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]" />
                </label>
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Crop Name</span>
                    <input type="text" wire:model="settings.crop_name" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]" />
                </label>
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Crop Variety</span>
                    <input type="text" wire:model="settings.crop_variety" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]" />
                </label>
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Location</span>
                    <input type="text" wire:model="settings.location" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]" />
                </label>
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Reservoir Capacity</span>
                    <input type="number" wire:model="settings.reservoir_capacity" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]" />
                </label>
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Maximum Plant Capacity</span>
                    <input type="number" wire:model="settings.maximum_plant_capacity" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]" />
                </label>
                <label class="text-sm font-semibold text-[#1B4332] md:col-span-2">
                    <span>Notes</span>
                    <textarea rows="3" wire:model="settings.notes" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]"></textarea>
                </label>
            </div>
        </div>

        <div class="glass-card rounded-2xl border border-[#2D6A4F]/10 p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-[#1B4332]">Device Defaults</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Default Device Name</span>
                    <input type="text" wire:model="settings.default_device_name" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]" />
                </label>
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Default Upload Interval</span>
                    <input type="number" wire:model="settings.default_upload_interval" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]" />
                </label>
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Default Heartbeat Interval</span>
                    <input type="number" wire:model="settings.default_heartbeat_interval" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]" />
                </label>
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Future Camera Enabled</span>
                    <select wire:model="settings.future_camera_enabled" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]">
                        <option value="1">Enabled</option>
                        <option value="0">Disabled</option>
                    </select>
                </label>
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Future OTA Enabled</span>
                    <select wire:model="settings.future_ota_enabled" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]">
                        <option value="1">Enabled</option>
                        <option value="0">Disabled</option>
                    </select>
                </label>
            </div>
        </div>

        <div class="glass-card rounded-2xl border border-[#2D6A4F]/10 p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-[#1B4332]">Appearance</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Theme</span>
                    <input type="text" wire:model="settings.theme" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]" />
                </label>
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Timezone</span>
                    <input type="text" wire:model="settings.timezone" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]" />
                </label>
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Date Format</span>
                    <input type="text" wire:model="settings.date_format" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]" />
                </label>
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Temperature Unit</span>
                    <input type="text" wire:model="settings.temperature_unit" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]" />
                </label>
                <label class="text-sm font-semibold text-[#1B4332]">
                    <span>Water Volume Unit</span>
                    <input type="text" wire:model="settings.water_volume_unit" class="mt-1 w-full rounded-xl border border-[#2D6A4F]/20 bg-white px-3 py-2 text-sm text-[#1B4332]" />
                </label>
            </div>
        </div>

        <div class="glass-card rounded-2xl border border-[#2D6A4F]/10 p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-[#1B4332]">Maintenance</h3>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div class="rounded-2xl border border-[#2D6A4F]/10 bg-[#F8FAF8] p-4 text-sm text-[#1B4332]">
                    <p class="font-semibold">Database Information</p>
                    <p class="mt-2 text-[#2D6A4F]/80">System settings storage is backed by the dedicated system_settings table.</p>
                </div>
                <div class="rounded-2xl border border-[#2D6A4F]/10 bg-[#F8FAF8] p-4 text-sm text-[#1B4332]">
                    <p class="font-semibold">Telemetry Count</p>
                    <p class="mt-2 text-[#2D6A4F]/80">{{ $telemetryCount }}</p>
                </div>
                <div class="rounded-2xl border border-[#2D6A4F]/10 bg-[#F8FAF8] p-4 text-sm text-[#1B4332]">
                    <p class="font-semibold">Device Count</p>
                    <p class="mt-2 text-[#2D6A4F]/80">{{ $deviceCount }}</p>
                </div>
                <div class="rounded-2xl border border-[#2D6A4F]/10 bg-[#F8FAF8] p-4 text-sm text-[#1B4332]">
                    <p class="font-semibold">Storage Usage</p>
                    <p class="mt-2 text-[#2D6A4F]/80">{{ $storageUsage }}</p>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-2">
                <button class="rounded-xl border border-[#2D6A4F]/20 bg-white px-4 py-2 text-sm font-semibold text-[#2D6A4F]">Export Settings</button>
                <button class="rounded-xl border border-[#2D6A4F]/20 bg-white px-4 py-2 text-sm font-semibold text-[#2D6A4F]">Import Settings</button>
                <button wire:click="resetToDefaults" class="rounded-xl border border-[#2D6A4F]/20 bg-white px-4 py-2 text-sm font-semibold text-[#2D6A4F]">Reset to Defaults</button>
            </div>
        </div>

        <div class="glass-card rounded-2xl border border-[#2D6A4F]/10 p-6 shadow-sm">
            <h3 class="text-lg font-semibold text-[#1B4332]">Account</h3>
            <div class="mt-4 rounded-2xl border border-[#2D6A4F]/10 bg-[#F8FAF8] p-4 text-sm text-[#1B4332]">
                <p class="font-semibold">Authenticated User</p>
                <p class="mt-2 text-[#2D6A4F]/80">{{ $user->name }}</p>
                <p class="mt-1 text-[#2D6A4F]/80">{{ $user->email }}</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('profile') }}" wire:navigate class="rounded-xl bg-[#2D6A4F] px-4 py-2 text-sm font-semibold text-white">Profile</a>
                    <a href="{{ route('password.request') }}" class="rounded-xl border border-[#2D6A4F]/20 bg-white px-4 py-2 text-sm font-semibold text-[#2D6A4F]">Password</a>
                </div>
            </div>
        </div>
    </div>
</div>
