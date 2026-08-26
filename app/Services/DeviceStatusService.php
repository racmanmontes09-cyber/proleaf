<?php

namespace App\Services;

use App\Models\Device;
use App\Models\Telemetry;
use Illuminate\Support\Collection;

class DeviceStatusService
{
    /**
     * Determine device online/offline status label.
     */
    public function getDeviceStatusLabel(?Device $device): string
    {
        if ($device === null) {
            return 'Waiting for device...';
        }

        return $device->is_online ? 'Online' : 'Offline';
    }

    /**
     * Determine device status type for UI badge styling.
     */
    public function getDeviceStatusType(?Device $device): string
    {
        if ($device === null) {
            return 'standby';
        }

        return $device->is_online ? 'online' : 'offline';
    }

    /**
     * Get banner status label string.
     */
    public function getBannerStatusLabel(?Device $device): string
    {
        if ($device === null) {
            return 'ESP32 Hardware Node: Waiting for device...';
        }

        return 'ESP32 Hardware Node: '.$this->getDeviceStatusLabel($device);
    }

    /**
     * Get last updated label string.
     */
    public function getLastUpdatedLabel(?Device $device): string
    {
        if (! $device || ! $device->last_seen_at) {
            return 'Last Updated: Waiting for device...';
        }

        $suffix = $device->is_online ? 'Now' : $device->last_seen_at->diffForHumans();

        return 'Last Updated: '.$suffix;
    }

    /**
     * Get device name label.
     */
    public function getDeviceNameLabel(?Device $device): string
    {
        if ($device && $device->name) {
            return $device->name;
        }

        return 'Waiting for device name...';
    }

    /**
     * Get device ID label.
     */
    public function getDeviceIdLabel(?Device $device): string
    {
        if ($device && $device->device_id) {
            return $device->device_id;
        }

        return 'Waiting for device ID...';
    }

    /**
     * Get firmware version label.
     */
    public function getFirmwareLabel(?Device $device): string
    {
        if ($device && $device->firmware_version) {
            return $device->firmware_version;
        }

        return 'Waiting for firmware...';
    }

    /**
     * Get local IP address label.
     */
    public function getLocalIpLabel(?Device $device): string
    {
        if ($device && $device->local_ip_address) {
            return $device->local_ip_address;
        }

        return 'Waiting for hardware...';
    }

    /**
     * Get Wi-Fi RSSI signal strength label.
     */
    public function getWifiRssiLabel(?Device $device): string
    {
        if ($device && $device->wifi_rssi !== null) {
            return $device->wifi_rssi.' dBm';
        }

        return 'Waiting for hardware...';
    }

    /**
     * Get last seen diffForHumans label.
     */
    public function getLastSeenLabel(?Device $device): string
    {
        if (! $device || ! $device->last_seen_at) {
            return 'Waiting for device...';
        }

        return $device->is_online ? 'Now' : $device->last_seen_at->diffForHumans();
    }

    /**
     * Get system uptime formatted label.
     */
    public function getSystemUptimeLabel(?Device $device): string
    {
        if ($device && $device->uptime_seconds !== null) {
            return $this->formatUptime($device->uptime_seconds);
        }

        return 'Waiting for hardware...';
    }

    /**
     * Format uptime seconds into human-readable string (X3d 12h 45m).
     */
    public function formatUptime(int $seconds): string
    {
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return sprintf('%dd %dh %dm', $days, $hours, $minutes);
    }

    /**
     * Resolve battery status label.
     */
    public function resolveBatteryStatusLabel(?Device $device, ?Telemetry $telemetry = null): string
    {
        if ($device && $device->is_online) {
            return 'Pending hardware integration';
        }

        return 'Waiting for hardware...';
    }

    /**
     * Build device cards list for dashboard view.
     */
    public function buildDeviceCards(Collection $devices): array
    {
        if ($devices->isEmpty()) {
            return [];
        }

        return $devices->take(2)->map(function (Device $device): array {
            return [
                'name' => $device->name ?: 'Waiting for device name...',
                'deviceId' => $device->device_id ?: 'Waiting for device ID...',
                'isOnline' => $device->is_online,
                'firmware' => $device->firmware_version ?: 'Waiting for firmware...',
                'wifiRssi' => $device->wifi_rssi !== null ? $device->wifi_rssi.' dBm' : 'Waiting for hardware...',
                'uptime' => $device->uptime_seconds !== null ? $this->formatUptime($device->uptime_seconds) : 'Waiting for hardware...',
                'freeHeap' => $device->free_heap !== null ? $device->free_heap.' B' : 'Waiting for hardware...',
                'battery' => 'Pending hardware integration',
                'transportStatus' => $device->is_online ? 'Connected via HTTP API' : 'Offline',
                'lastSeen' => $device->last_seen_at ? ($device->is_online ? 'Now' : $device->last_seen_at->diffForHumans()) : 'Waiting for device...',
            ];
        })->all();
    }

    /**
     * Build actuator control cards list.
     */
    public function buildActuatorCards(?Telemetry $telemetry = null): array
    {
        return [
            [
                'name' => 'Nutrient Dosing Pump A',
                'detail' => 'Relay · Primary nutrient dosing',
                'command' => 'nutrient_a',
                'status' => 'OFF',
                'statusType' => 'standby',
            ],
            [
                'name' => 'Nutrient Dosing Pump B',
                'detail' => 'Relay · Secondary nutrient dosing',
                'command' => 'nutrient_b',
                'status' => 'OFF',
                'statusType' => 'standby',
            ],
            [
                'name' => 'pH Up Dosing Pump',
                'detail' => 'Relay · pH increase dosing',
                'command' => 'ph_up',
                'status' => 'OFF',
                'statusType' => 'standby',
            ],
            [
                'name' => 'pH Down Dosing Pump',
                'detail' => 'Relay · pH decrease dosing',
                'command' => 'ph_down',
                'status' => 'OFF',
                'statusType' => 'standby',
            ],
            [
                'name' => 'VPD Intake Cooling Fan',
                'detail' => 'Relay · Temperature regulation',
                'command' => 'cooling_fan',
                'status' => 'OFF',
                'statusType' => 'standby',
            ],
        ];
    }
}
