<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\DeviceStatus;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeviceStatusDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_binds_latest_telemetry_values_from_the_device_relationship(): void
    {
        $device = Device::create([
            'device_id' => 'LEAF-ESP32-01',
            'name' => 'ESP32-01',
            'last_seen_at' => now(),
        ]);

        $device->telemetries()->create([
            'air_temperature' => 24.8,
            'humidity' => 68,
            'water_temperature' => 22.4,
            'ph' => 6.3,
            'ec' => 1.9,
            'water_flow' => 2.4,
            'water_level' => 84,
        ]);

        Livewire::test(DeviceStatus::class)
            ->assertSet('deviceStatusLabel', 'Online')
            ->assertSet('temperatureValue', '24.8')
            ->assertSet('humidityValue', '68')
            ->assertSet('waterTemperatureValue', '22.4')
            ->assertSet('phValue', '6.3')
            ->assertSet('ecValue', '1.9')
            ->assertSet('waterFlowValue', '2.4')
            ->assertSet('waterLevelValue', '84')
            ->assertSet('hasChartTelemetry', true);
    }

    public function test_dashboard_prefers_configured_local_device_when_it_exists(): void
    {
        $latestDevice = Device::create([
            'device_id' => 'LEAF-ESP32-OTHER',
            'name' => 'Other ESP32',
            'last_seen_at' => now(),
        ]);
        $latestDevice->telemetries()->create([
            'air_temperature' => 99.9,
            'water_temperature' => 40.0,
            'measured_at' => now(),
        ]);

        $targetDevice = Device::create([
            'device_id' => 'esp32-001',
            'name' => 'Greenhouse ESP32',
            'last_seen_at' => now()->subHour(),
        ]);

        config(['leaf.dashboard.device_db_id' => $targetDevice->id]);

        $targetDevice->telemetries()->create([
            'air_temperature' => 30.5,
            'humidity' => 72,
            'water_temperature' => 26.4,
            'ph' => 6.4,
            'ec' => 1.6,
            'water_flow' => 1.2,
            'water_level' => 74,
            'measured_at' => now()->subMinute(),
        ]);

        Livewire::test(DeviceStatus::class)
            ->assertSet('device.id', $targetDevice->id)
            ->assertSet('temperatureValue', '30.5')
            ->assertSet('humidityValue', '72')
            ->assertSet('waterTemperatureValue', '26.4')
            ->assertSet('phValue', '6.4')
            ->assertSet('ecValue', '1.6')
            ->assertSet('waterFlowValue', '1.2')
            ->assertSet('waterLevelValue', '74')
            ->assertSet('hasChartTelemetry', true);
    }

    public function test_dashboard_uses_real_telemetry_for_initial_display_even_when_fake_rows_exist(): void
    {
        $targetDevice = Device::create([
            'device_id' => 'esp32-001',
            'name' => 'Greenhouse ESP32',
            'last_seen_at' => now(),
        ]);

        $targetDevice->telemetries()->create([
            'air_temperature' => 29.5,
            'humidity' => 64,
            'water_temperature' => 25.1,
            'ph' => 6.2,
            'ec' => 1.4,
            'water_flow' => 0.9,
            'water_level' => 68,
            'measured_at' => now()->subMinutes(6),
            'firmware_version' => 'leaf-fake-dashboard-telemetry',
        ]);

        $targetDevice->telemetries()->create([
            'air_temperature' => 30.5,
            'humidity' => 72,
            'water_temperature' => 26.4,
            'ph' => 6.4,
            'ec' => 1.6,
            'water_flow' => 1.2,
            'water_level' => 74,
            'measured_at' => now()->subMinute(),
            'firmware_version' => '1.0.0',
        ]);

        Livewire::test(DeviceStatus::class)
            ->assertSet('device.id', $targetDevice->id)
            ->assertSet('temperatureValue', '30.5')
            ->assertSet('humidityValue', '72')
            ->assertSet('waterTemperatureValue', '26.4')
            ->assertSet('phValue', '6.4')
            ->assertSet('ecValue', '1.6')
            ->assertSet('waterFlowValue', '1.2')
            ->assertSet('waterLevelValue', '74')
            ->assertSet('hasChartTelemetry', true);
    }


    public function test_dashboard_chart_payload_survives_browser_attribute_parsing(): void
    {
        $device = Device::create([
            'device_id' => 'LEAF-ESP32-01',
            'name' => 'ESP32-01',
            'last_seen_at' => now(),
        ]);

        $device->telemetries()->create([
            'air_temperature' => 24.8,
            'humidity' => 68,
            'water_temperature' => 22.4,
            'ph' => 6.3,
            'ec' => 1.9,
            'water_flow' => 2.4,
            'water_level' => 84,
        ]);

        $html = Livewire::test(DeviceStatus::class)->html();
        $dom = new \DOMDocument();
        @$dom->loadHTML($html);

        $xData = null;
        foreach ($dom->getElementsByTagName('div') as $div) {
            if ($div->hasAttribute('x-data')) {
                $xData = $div->getAttribute('x-data');
                break;
            }
        }

        $this->assertNotNull($xData);
        $this->assertStringContainsString('initialChartPayload', $xData);
        $this->assertStringContainsString('initialKpis', $xData);
        $this->assertStringContainsString('air_temperature', $xData);
        $this->assertStringContainsString('Water pH', $xData);
        $this->assertStringContainsString('analyticsSeries', $xData);
        $this->assertStringContainsString('kpiValue', $html);
        $this->assertStringContainsString('dashboard-chart-data-updated', $html);
        $this->assertStringContainsString('dashboard-device-selected', $html);
    }

    public function test_dashboard_refresh_dispatches_chart_update_payload(): void
    {
        $device = Device::create([
            'device_id' => 'LEAF-ESP32-01',
            'name' => 'ESP32-01',
            'last_seen_at' => now(),
        ]);

        $device->telemetries()->create([
            'air_temperature' => 24.8,
            'humidity' => 68,
            'water_temperature' => 22.4,
            'ph' => 6.3,
            'ec' => 1.9,
            'water_flow' => 2.4,
            'water_level' => 84,
        ]);

        Livewire::test(DeviceStatus::class)
            ->call('refreshDashboard')
            ->assertDispatched('dashboard-chart-data-updated');
    }

    public function test_dashboard_light_refresh_dispatches_device_selected_when_latest_device_changes(): void
    {
        Device::create([
            'device_id' => 'LEAF-ESP32-01',
            'name' => 'ESP32-01',
            'last_seen_at' => now()->subMinute(),
        ]);

        $component = Livewire::test(DeviceStatus::class);

        $newDevice = Device::create([
            'device_id' => 'LEAF-ESP32-02',
            'name' => 'ESP32-02',
            'last_seen_at' => now(),
        ]);

        $component
            ->call('refreshDashboardLight')
            ->assertSet('device.id', $newDevice->id)
            ->assertDispatched('dashboard-device-selected');
    }

    public function test_dashboard_shows_waiting_state_when_no_device_exists(): void
    {
        Livewire::test(DeviceStatus::class)
            ->assertSet('deviceStatusLabel', 'Waiting for device...')
            ->assertSet('deviceNameLabel', 'Waiting for device name...')
            ->assertSet('lastSeenLabel', 'Waiting for device...')
            ->assertSet('alertBadgeLabel', 'Waiting');
    }
}
