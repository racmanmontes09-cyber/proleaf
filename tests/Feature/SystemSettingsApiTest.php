<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSettingsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_settings_endpoint_returns_thresholds_and_intervals(): void
    {
        SystemSetting::putValue('temperature_min', '18');
        SystemSetting::putValue('temperature_max', '25');
        SystemSetting::putValue('humidity_min', '60');
        SystemSetting::putValue('humidity_max', '80');
        SystemSetting::putValue('water_temperature_min', '18');
        SystemSetting::putValue('water_temperature_max', '24');
        SystemSetting::putValue('ph_min', '5.5');
        SystemSetting::putValue('ph_max', '6.5');
        SystemSetting::putValue('ec_min', '1.2');
        SystemSetting::putValue('ec_max', '2.0');
        SystemSetting::putValue('water_flow_min', '0.5');
        SystemSetting::putValue('water_flow_max', '2.0');
        SystemSetting::putValue('water_level_min', '20');
        SystemSetting::putValue('water_level_max', '80');
        SystemSetting::putValue('heartbeat_interval', '30');
        SystemSetting::putValue('sensor_upload_interval', '5');

        $this->getJson('/api/device/settings')
            ->assertOk()
            ->assertJsonPath('temperature.min', 18)
            ->assertJsonPath('temperature.max', 25)
            ->assertJsonPath('humidity.min', 60)
            ->assertJsonPath('humidity.max', 80)
            ->assertJsonPath('heartbeat_interval', 30)
            ->assertJsonPath('upload_interval', 5);
    }
}
