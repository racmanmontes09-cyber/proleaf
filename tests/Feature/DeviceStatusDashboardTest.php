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
        $this->assertStringContainsString('Water pH', $xData);
        $this->assertStringContainsString('analyticsSeries', $xData);
        $this->assertStringContainsString('dashboard-chart-data-updated', $html);
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

    public function test_dashboard_shows_waiting_state_when_no_device_exists(): void
    {
        Livewire::test(DeviceStatus::class)
            ->assertSet('deviceStatusLabel', 'Waiting for device...')
            ->assertSet('deviceNameLabel', 'Waiting for device name...')
            ->assertSet('lastSeenLabel', 'Waiting for device...')
            ->assertSet('alertBadgeLabel', 'Waiting');
    }
}
