<?php

namespace App\Livewire;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Validate;
use Livewire\Component;

class SettingsPage extends Component
{
    public array $settings = [];

    public bool $saved = false;

    public string $message = '';

    public function mount(): void
    {
        if (! auth()->user()?->canPerform('settings.view')) {
            abort(403);
        }

        $this->loadSettings();
    }

    public function save(): void
    {
        $this->reset('saved', 'message');

        $this->validate();

        if (! auth()->user()?->canPerform('settings.update')) {
            $this->message = 'Forbidden.';
            return;
        }

        $this->saveSettings();

        $this->saved = true;
        $this->message = 'Settings saved successfully.';
    }

    public function resetToDefaults(): void
    {
        $defaults = [
            ['key' => 'temperature_min', 'value' => '18', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'Air Temperature Minimum', 'description' => 'Minimum acceptable air temperature.'],
            ['key' => 'temperature_max', 'value' => '25', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'Air Temperature Maximum', 'description' => 'Maximum acceptable air temperature.'],
            ['key' => 'humidity_min', 'value' => '60', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'Humidity Minimum', 'description' => 'Minimum acceptable humidity.'],
            ['key' => 'humidity_max', 'value' => '80', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'Humidity Maximum', 'description' => 'Maximum acceptable humidity.'],
            ['key' => 'water_temperature_min', 'value' => '18', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'Water Temperature Minimum', 'description' => 'Minimum acceptable water temperature.'],
            ['key' => 'water_temperature_max', 'value' => '24', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'Water Temperature Maximum', 'description' => 'Maximum acceptable water temperature.'],
            ['key' => 'ph_min', 'value' => '5.5', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'pH Minimum', 'description' => 'Minimum acceptable pH.'],
            ['key' => 'ph_max', 'value' => '6.5', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'pH Maximum', 'description' => 'Maximum acceptable pH.'],
            ['key' => 'ec_min', 'value' => '1.2', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'EC Minimum', 'description' => 'Minimum acceptable EC.'],
            ['key' => 'ec_max', 'value' => '2.0', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'EC Maximum', 'description' => 'Maximum acceptable EC.'],
            ['key' => 'water_flow_min', 'value' => '0.5', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'Water Flow Minimum', 'description' => 'Minimum acceptable water flow.'],
            ['key' => 'water_flow_max', 'value' => '2.0', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'Water Flow Maximum', 'description' => 'Maximum acceptable water flow.'],
            ['key' => 'water_level_min', 'value' => '20', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'Water Level Minimum', 'description' => 'Minimum acceptable water level.'],
            ['key' => 'water_level_max', 'value' => '80', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'Water Level Maximum', 'description' => 'Maximum acceptable water level.'],
            ['key' => 'sensor_upload_interval', 'value' => '5', 'type' => 'integer', 'group' => 'automation', 'label' => 'Sensor Upload Interval (seconds)', 'description' => 'How often sensor data is uploaded.'],
            ['key' => 'heartbeat_interval', 'value' => '30', 'type' => 'integer', 'group' => 'automation', 'label' => 'Heartbeat Interval (seconds)', 'description' => 'How often the device heartbeat is sent.'],
            ['key' => 'auto_refresh_interval', 'value' => '10', 'type' => 'integer', 'group' => 'automation', 'label' => 'Auto Refresh Interval (seconds)', 'description' => 'Dashboard refresh frequency.'],
            ['key' => 'fan_activation_temperature', 'value' => '28', 'type' => 'float', 'group' => 'automation', 'label' => 'Fan Activation Temperature', 'description' => 'Temperature at which the fan activates.'],
            ['key' => 'pump_delay', 'value' => '10', 'type' => 'integer', 'group' => 'automation', 'label' => 'Pump Delay', 'description' => 'Delay between pump actions.'],
            ['key' => 'automatic_dosing', 'value' => '1', 'type' => 'boolean', 'group' => 'automation', 'label' => 'Automatic Dosing', 'description' => 'Enables automated dosing.'],
            ['key' => 'automatic_irrigation', 'value' => '1', 'type' => 'boolean', 'group' => 'automation', 'label' => 'Automatic Irrigation', 'description' => 'Enables automated irrigation.'],
            ['key' => 'dashboard_notifications', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'label' => 'Dashboard Notifications', 'description' => 'Enable on-dashboard alerts.'],
            ['key' => 'browser_notifications', 'value' => '0', 'type' => 'boolean', 'group' => 'notifications', 'label' => 'Browser Notifications', 'description' => 'Enable browser notifications.'],
            ['key' => 'alert_cooldown', 'value' => '300', 'type' => 'integer', 'group' => 'notifications', 'label' => 'Alert Cooldown (seconds)', 'description' => 'Cooldown period between repeated alerts.'],
            ['key' => 'critical_alert_repeat', 'value' => '3', 'type' => 'integer', 'group' => 'notifications', 'label' => 'Critical Alert Repeat', 'description' => 'How many times critical alerts repeat.'],
            ['key' => 'enable_notifications', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications', 'label' => 'Enable Notifications', 'description' => 'Enable notifications globally.'],
            ['key' => 'greenhouse_name', 'value' => 'Project L.E.A.F. Greenhouse', 'type' => 'string', 'group' => 'greenhouse', 'label' => 'Greenhouse Name', 'description' => 'Display name for the greenhouse.'],
            ['key' => 'crop_name', 'value' => 'Leafy Greens', 'type' => 'string', 'group' => 'greenhouse', 'label' => 'Crop Name', 'description' => 'Current crop being cultivated.'],
            ['key' => 'crop_variety', 'value' => 'Butterhead', 'type' => 'string', 'group' => 'greenhouse', 'label' => 'Crop Variety', 'description' => 'Specific crop variety.'],
            ['key' => 'location', 'value' => 'Indoor Hydroponics', 'type' => 'string', 'group' => 'greenhouse', 'label' => 'Location', 'description' => 'Location of the greenhouse.'],
            ['key' => 'reservoir_capacity', 'value' => '500', 'type' => 'integer', 'group' => 'greenhouse', 'label' => 'Reservoir Capacity (L)', 'description' => 'Reservoir capacity in liters.'],
            ['key' => 'maximum_plant_capacity', 'value' => '80', 'type' => 'integer', 'group' => 'greenhouse', 'label' => 'Maximum Plant Capacity', 'description' => 'Maximum plant capacity.'],
            ['key' => 'notes', 'value' => '', 'type' => 'string', 'group' => 'greenhouse', 'label' => 'Notes', 'description' => 'Additional greenhouse notes.'],
            ['key' => 'default_device_name', 'value' => 'ESP32 Device', 'type' => 'string', 'group' => 'device_defaults', 'label' => 'Default Device Name', 'description' => 'Default name for new devices.'],
            ['key' => 'default_upload_interval', 'value' => '5', 'type' => 'integer', 'group' => 'device_defaults', 'label' => 'Default Upload Interval', 'description' => 'Default upload interval for new devices.'],
            ['key' => 'default_heartbeat_interval', 'value' => '30', 'type' => 'integer', 'group' => 'device_defaults', 'label' => 'Default Heartbeat Interval', 'description' => 'Default heartbeat interval for new devices.'],
            ['key' => 'future_camera_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'device_defaults', 'label' => 'Future Camera Enabled', 'description' => 'Placeholder camera setting.'],
            ['key' => 'future_ota_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'device_defaults', 'label' => 'Future OTA Enabled', 'description' => 'Placeholder OTA setting.'],
            ['key' => 'theme', 'value' => 'default', 'type' => 'string', 'group' => 'appearance', 'label' => 'Theme', 'description' => 'Application theme.'],
            ['key' => 'timezone', 'value' => 'UTC', 'type' => 'string', 'group' => 'appearance', 'label' => 'Timezone', 'description' => 'Application timezone.'],
            ['key' => 'date_format', 'value' => 'Y-m-d', 'type' => 'string', 'group' => 'appearance', 'label' => 'Date Format', 'description' => 'Preferred date display format.'],
            ['key' => 'temperature_unit', 'value' => 'C', 'type' => 'string', 'group' => 'appearance', 'label' => 'Temperature Unit', 'description' => 'Preferred temperature unit.'],
            ['key' => 'water_volume_unit', 'value' => 'L', 'type' => 'string', 'group' => 'appearance', 'label' => 'Water Volume Unit', 'description' => 'Preferred water volume unit.'],
        ];

        foreach ($defaults as $default) {
            SystemSetting::query()->updateOrCreate(['key' => $default['key']], $default);
        }

        $this->loadSettings();
        $this->saved = true;
        $this->message = 'Settings reset to defaults.';
    }

    protected function rules(): array
    {
        return [
            'settings.temperature_min' => ['required', 'numeric', 'lt:settings.temperature_max'],
            'settings.temperature_max' => ['required', 'numeric', 'gt:settings.temperature_min'],
            'settings.humidity_min' => ['required', 'numeric', 'lt:settings.humidity_max'],
            'settings.humidity_max' => ['required', 'numeric', 'gt:settings.humidity_min'],
            'settings.water_temperature_min' => ['required', 'numeric', 'lt:settings.water_temperature_max'],
            'settings.water_temperature_max' => ['required', 'numeric', 'gt:settings.water_temperature_min'],
            'settings.ph_min' => ['required', 'numeric', 'lt:settings.ph_max'],
            'settings.ph_max' => ['required', 'numeric', 'gt:settings.ph_min'],
            'settings.ec_min' => ['required', 'numeric', 'lt:settings.ec_max'],
            'settings.ec_max' => ['required', 'numeric', 'gt:settings.ec_min'],
            'settings.water_flow_min' => ['required', 'numeric', 'lt:settings.water_flow_max'],
            'settings.water_flow_max' => ['required', 'numeric', 'gt:settings.water_flow_min'],
            'settings.water_level_min' => ['required', 'numeric', 'lt:settings.water_level_max'],
            'settings.water_level_max' => ['required', 'numeric', 'gt:settings.water_level_min'],
            'settings.sensor_upload_interval' => ['required', 'integer', 'min:1'],
            'settings.heartbeat_interval' => ['required', 'integer', 'min:1'],
            'settings.auto_refresh_interval' => ['required', 'integer', 'min:1'],
            'settings.fan_activation_temperature' => ['required', 'numeric'],
            'settings.pump_delay' => ['required', 'integer', 'min:0'],
            'settings.automatic_dosing' => ['required', 'boolean'],
            'settings.automatic_irrigation' => ['required', 'boolean'],
            'settings.dashboard_notifications' => ['required', 'boolean'],
            'settings.browser_notifications' => ['required', 'boolean'],
            'settings.alert_cooldown' => ['required', 'integer', 'min:0'],
            'settings.critical_alert_repeat' => ['required', 'integer', 'min:1'],
            'settings.enable_notifications' => ['required', 'boolean'],
            'settings.greenhouse_name' => ['required', 'string', 'max:255'],
            'settings.crop_name' => ['required', 'string', 'max:255'],
            'settings.crop_variety' => ['required', 'string', 'max:255'],
            'settings.location' => ['required', 'string', 'max:255'],
            'settings.reservoir_capacity' => ['required', 'integer', 'min:1'],
            'settings.maximum_plant_capacity' => ['required', 'integer', 'min:1'],
            'settings.notes' => ['nullable', 'string'],
            'settings.default_device_name' => ['required', 'string', 'max:255'],
            'settings.default_upload_interval' => ['required', 'integer', 'min:1'],
            'settings.default_heartbeat_interval' => ['required', 'integer', 'min:1'],
            'settings.future_camera_enabled' => ['required', 'boolean'],
            'settings.future_ota_enabled' => ['required', 'boolean'],
            'settings.theme' => ['required', 'string', 'max:50'],
            'settings.timezone' => ['required', 'string', 'max:100'],
            'settings.date_format' => ['required', 'string', 'max:50'],
            'settings.temperature_unit' => ['required', 'string', 'max:20'],
            'settings.water_volume_unit' => ['required', 'string', 'max:20'],
        ];
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.settings-page', [
            'user' => $user,
            'telemetryCount' => \App\Models\Telemetry::query()->count(),
            'deviceCount' => \App\Models\Device::query()->count(),
            'storageUsage' => $this->formatBytes(storage_path('app') ? 0 : 0),
        ]);
    }

    public function loadSettings(): void
    {
        $rows = SystemSetting::query()->get();
        $this->settings = $rows->mapWithKeys(function (SystemSetting $setting): array {
            return [$setting->key => $setting->value];
        })->toArray();

        $defaults = [
            'temperature_min' => '18',
            'temperature_max' => '25',
            'humidity_min' => '60',
            'humidity_max' => '80',
            'water_temperature_min' => '18',
            'water_temperature_max' => '24',
            'ph_min' => '5.5',
            'ph_max' => '6.5',
            'ec_min' => '1.2',
            'ec_max' => '2.0',
            'water_flow_min' => '0.5',
            'water_flow_max' => '2.0',
            'water_level_min' => '20',
            'water_level_max' => '80',
            'sensor_upload_interval' => '5',
            'heartbeat_interval' => '30',
            'auto_refresh_interval' => '10',
            'fan_activation_temperature' => '28',
            'pump_delay' => '10',
            'automatic_dosing' => '1',
            'automatic_irrigation' => '1',
            'dashboard_notifications' => '1',
            'browser_notifications' => '0',
            'alert_cooldown' => '300',
            'critical_alert_repeat' => '3',
            'enable_notifications' => '1',
            'greenhouse_name' => 'Project L.E.A.F. Greenhouse',
            'crop_name' => 'Leafy Greens',
            'crop_variety' => 'Butterhead',
            'location' => 'Indoor Hydroponics',
            'reservoir_capacity' => '500',
            'maximum_plant_capacity' => '80',
            'notes' => '',
            'default_device_name' => 'ESP32 Device',
            'default_upload_interval' => '5',
            'default_heartbeat_interval' => '30',
            'future_camera_enabled' => '0',
            'future_ota_enabled' => '0',
            'theme' => 'default',
            'timezone' => 'UTC',
            'date_format' => 'Y-m-d',
            'temperature_unit' => 'C',
            'water_volume_unit' => 'L',
        ];

        foreach ($defaults as $key => $value) {
            $this->settings[$key] = $this->settings[$key] ?? $value;
        }
    }

    protected function saveSettings(): void
    {
        foreach ($this->settings as $key => $value) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            $index++;
        }

        return round($bytes, 2).' '.$units[$index];
    }
}
