<?php

namespace App\Services;

use App\Models\Device;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Log;

class DeviceRuntimeConfigurationService
{
    public const SCHEMA_VERSION = 1;

    public const APPROVED_SETTING_DEFINITIONS = [
        'temperature_min' => ['value' => '18', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'Air Temperature Minimum', 'description' => 'Minimum acceptable air temperature.'],
        'temperature_max' => ['value' => '25', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'Air Temperature Maximum', 'description' => 'Maximum acceptable air temperature and fan activation threshold.'],
        'water_temperature_min' => ['value' => '18', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'Water Temperature Minimum', 'description' => 'Minimum acceptable water temperature.'],
        'water_temperature_max' => ['value' => '24', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'Water Temperature Maximum', 'description' => 'Maximum acceptable water temperature.'],
        'ph_min' => ['value' => '5.5', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'pH Minimum', 'description' => 'Minimum acceptable pH.'],
        'ph_max' => ['value' => '6.5', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'pH Maximum', 'description' => 'Maximum acceptable pH.'],
        'ec_min' => ['value' => '1.2', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'EC Minimum', 'description' => 'Minimum acceptable EC.'],
        'ec_max' => ['value' => '2.0', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'EC Maximum', 'description' => 'Maximum acceptable EC.'],
        'water_flow_min' => ['value' => '0.5', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'Water Flow Minimum', 'description' => 'Minimum acceptable water flow.'],
        'water_flow_max' => ['value' => '2.0', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'Water Flow Maximum', 'description' => 'Maximum acceptable water flow.'],
        'water_level_min' => ['value' => '20', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'Water Level Minimum', 'description' => 'Minimum acceptable water level.'],
        'water_level_max' => ['value' => '80', 'type' => 'float', 'group' => 'sensor_thresholds', 'label' => 'Water Level Maximum', 'description' => 'Maximum acceptable water level.'],
        'sensor_upload_interval' => ['value' => '5', 'type' => 'integer', 'group' => 'automation', 'label' => 'Sensor Upload Interval', 'description' => 'How often the ESP32 uploads sensor data.'],
        'heartbeat_interval' => ['value' => '30', 'type' => 'integer', 'group' => 'automation', 'label' => 'Heartbeat Interval', 'description' => 'How often the ESP32 reports device status.'],
    ];

    /**
     * Build the complete retained runtime configuration payload for firmware.
     */
    public function buildPayload(?Device $device = null): array
    {
        $settings = SystemSetting::getAllSettings();
        $payloadSettings = [];

        foreach (self::APPROVED_SETTING_DEFINITIONS as $key => $definition) {
            $value = $settings[$key] ?? $definition['value'];
            $payloadSettings[$key] = $this->castValue($value, $definition['type']);
        }

        return [
            'schema_version' => self::SCHEMA_VERSION,
            'device_id' => $device?->device_id,
            'settings' => $payloadSettings,
        ];
    }

    /**
     * Publish the current runtime configuration to every known device.
     *
     * @return array{attempted:int,published:int,failed:int}
     */
    public function publishCurrentConfigurationToAllDevices(): array
    {
        $summary = [
            'attempted' => 0,
            'published' => 0,
            'failed' => 0,
        ];

        Device::query()
            ->whereNotNull('device_id')
            ->where('device_id', '!=', '')
            ->select(['id', 'device_id'])
            ->orderBy('id')
            ->chunkById(100, function ($devices) use (&$summary): void {
                foreach ($devices as $device) {
                    $summary['attempted']++;

                    if ($this->publishCurrentConfiguration($device)) {
                        $summary['published']++;
                    } else {
                        $summary['failed']++;
                    }
                }
            });

        return $summary;
    }

    public function publishCurrentConfiguration(Device $device): bool
    {
        $payload = json_encode($this->buildPayload($device), JSON_THROW_ON_ERROR);

        Log::debug('[SETTINGS] Publishing runtime configuration', [
            'device_id' => $device->device_id,
            'payload' => $payload,
        ]);

        $published = MqttPublisher::publishDeviceConfiguration($device, $payload);

        if ($published) {
            Log::debug('[SETTINGS] Runtime configuration published successfully', [
                'device_id' => $device->device_id,
                'topic' => MqttPublisher::deviceSettingsTopic($device),
            ]);
        } else {
            Log::warning('[SETTINGS] Runtime configuration publish FAILED', [
                'device_id' => $device->device_id,
            ]);
        }

        return $published;
    }

    private function castValue(mixed $value, string $type): int|float|string|null
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'integer' => (int) $value,
            'float' => (float) $value,
            default => (string) $value,
        };
    }
}
