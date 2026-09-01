<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\SystemSetting;
use App\Services\DeviceRuntimeConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceRuntimeConfigurationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_runtime_configuration_uses_air_temperature_max_as_only_fan_threshold(): void
    {
        SystemSetting::putValue('temperature_max', '25');
        SystemSetting::putValue('fan_activation_temperature', '30');

        $device = Device::create([
            'device_id' => 'LEAF-RUNTIME-01',
            'name' => 'Runtime Device',
        ]);

        $settings = app(DeviceRuntimeConfigurationService::class)->buildPayload($device)['settings'];

        $this->assertSame(25.0, $settings['temperature_max']);
        $this->assertArrayNotHasKey('fan_activation_temperature', $settings);

        SystemSetting::putValue('temperature_max', '30');

        $settings = app(DeviceRuntimeConfigurationService::class)->buildPayload($device)['settings'];

        $this->assertSame(30.0, $settings['temperature_max']);
        $this->assertArrayNotHasKey('fan_activation_temperature', $settings);
    }
}
