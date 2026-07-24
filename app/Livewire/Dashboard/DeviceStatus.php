<?php

namespace App\Livewire\Dashboard;

use App\Models\Device;
use Livewire\Component;

class DeviceStatus extends Component
{
    public ?Device $device = null;

    public string $deviceStatusLabel = 'Online';

    public string $deviceStatusType = 'online';

    public string $bannerStatusLabel = 'ESP32 Hardware Node: Online';

    public string $lastUpdatedLabel = 'Last Updated: Just now';

    public string $deviceNameLabel = 'Node ESP32-01';

    public string $deviceIdLabel = 'LEAF-ESP32-01';

    public string $firmwareLabel = 'v2.4.1 (Stable)';

    public string $localIpLabel = 'N/A';

    public string $wifiRssiLabel = '-62 dBm (Strong)';

    public string $lastSeenLabel = 'Never';

    public string $systemUptimeLabel = '14d 08h 22m';

    public string $airTempValue = '24.8';

    public string $airHumidityValue = '68';

    public string $waterTempValue = '22.4';

    public string $waterPhValue = '6.3';

    public string $nutrientEcValue = '1.9';

    public string $waterLevelValue = '84';

    public string $waterFlowValue = '2.4';

    public function render()
    {
        $device = Device::query()->latest('last_seen_at')->first();

        $this->device = $device;
        $this->deviceStatusLabel = $device && $device->is_online ? 'Online' : 'Offline';
        $this->deviceStatusType = $device && $device->is_online ? 'online' : 'offline';
        $this->bannerStatusLabel = 'ESP32 Hardware Node: '.$this->deviceStatusLabel;
        $this->lastUpdatedLabel = $device && $device->last_seen_at
            ? 'Last Updated: '.$device->last_seen_at->diffForHumans()
            : 'Last Updated: Just now';
        $this->deviceNameLabel = $device && $device->name
            ? 'Node '.$device->name
            : 'Node ESP32-01';
        $this->deviceIdLabel = $device && $device->device_id ? $device->device_id : 'LEAF-ESP32-01';
        $this->firmwareLabel = $device && $device->firmware_version
            ? $device->firmware_version
            : 'v2.4.1 (Stable)';
        $this->localIpLabel = $device && $device->local_ip_address
            ? $device->local_ip_address
            : 'N/A';
        $this->wifiRssiLabel = $device && $device->wifi_rssi !== null
            ? $device->wifi_rssi.' dBm'
            : '-62 dBm (Strong)';
        $this->lastSeenLabel = $device && $device->last_seen_at
            ? $device->last_seen_at->diffForHumans()
            : 'Never';
        $this->systemUptimeLabel = $device && $device->uptime_seconds !== null
            ? $this->formatUptime($device->uptime_seconds)
            : '14d 08h 22m';

        return view('livewire.dashboard.device-status', [
            'device' => $this->device,
        ]);
    }

    private function formatUptime(int $seconds): string
    {
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        return sprintf('%dd %dh %dm', $days, $hours, $minutes);
    }
}