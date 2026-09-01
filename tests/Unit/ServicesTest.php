<?php

namespace Tests\Unit;

use App\Models\Device;
use App\Models\Telemetry;
use App\Services\AlertService;
use App\Services\DashboardService;
use App\Services\DeviceStatusService;
use App\Services\TelemetryService;
use App\Services\ThresholdService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_threshold_service_calculations_and_formatting(): void
    {
        $service = app(ThresholdService::class);

        $this->assertTrue($service->thresholdsAvailable(18.0, 25.0));
        $this->assertFalse($service->thresholdsAvailable(null, 25.0));

        $this->assertEquals('18-25°C', $service->formatThresholdRange(18.0, 25.0, '°C'));
        $this->assertEquals('Not available', $service->formatThresholdRange(null, 25.0));

        $this->assertEquals('LOW', $service->resolveStatus(15.0, 18.0, 25.0));
        $this->assertEquals('HIGH', $service->resolveStatus(30.0, 18.0, 25.0));
        $this->assertEquals('NORMAL', $service->resolveStatus(22.0, 18.0, 25.0));

        $this->assertEquals('warning', $service->resolveStatusType(30.0, 18.0, 25.0));
        $this->assertEquals('online', $service->resolveStatusType(22.0, 18.0, 25.0));

        $this->assertEquals(50.0, $service->scaleTelemetryPercentage(20.0, 10.0, 30.0));
    }

    public function test_device_status_service_formatting(): void
    {
        $service = app(DeviceStatusService::class);

        $this->assertEquals('0d 2h 15m', $service->formatUptime(8100));

        $device = Device::create([
            'device_id' => 'ESP-SVC-01',
            'name' => 'Service Node 1',
            'firmware_version' => 'v1.0.0',
            'wifi_rssi' => -65,
            'uptime_seconds' => 3600,
            'last_seen_at' => now(),
        ]);

        $this->assertEquals('Online', $service->getDeviceStatusLabel($device));
        $this->assertEquals('online', $service->getDeviceStatusType($device));
        $this->assertEquals('Service Node 1', $service->getDeviceNameLabel($device));
        $this->assertEquals('-65 dBm', $service->getWifiRssiLabel($device));
        $this->assertEquals('0d 1h 0m', $service->getSystemUptimeLabel($device));

        $deviceCards = $service->buildDeviceCards(collect([$device]));
        $this->assertCount(1, $deviceCards);
        $this->assertEquals('Service Node 1', $deviceCards[0]['name']);

        $actuatorCards = $service->buildActuatorCards();
        $this->assertCount(5, $actuatorCards);
        $this->assertEquals('cooling_fan', $actuatorCards[4]['command']);
    }

    public function test_device_online_grace_adapts_to_heartbeat_interval(): void
    {
        // With the runtime heartbeat (30s), effective grace = max(60, 30*2+10) = 70s
        $device = Device::create([
            'device_id' => 'ESP-SVC-TIMING',
            'name' => 'Timing Node',
            'last_seen_at' => now()->subSeconds(30),
        ]);

        $this->assertTrue($device->is_online);

        // Still within grace period (50 < 70)
        $device->last_seen_at = now()->subSeconds(50);
        $this->assertTrue($device->is_online);

        // Well within grace period (68 < 70)
        $device->last_seen_at = now()->subSeconds(68);
        $this->assertTrue($device->is_online);

        // Exceeds the effective grace period (75 > 70)
        $device->last_seen_at = now()->subSeconds(75);
        $this->assertFalse($device->is_online);

        // Verify effectiveOnlineGraceSeconds uses the configured floor as a minimum
        $this->assertEquals(70, Device::effectiveOnlineGraceSeconds());
    }

    public function test_telemetry_service_data_processing(): void
    {
        $service = app(TelemetryService::class);
        $device = Device::create(['device_id' => 'ESP-SVC-02', 'name' => 'Service Node 2', 'last_seen_at' => now()]);

        $t1 = Telemetry::create(['device_id' => $device->id, 'air_temperature' => 24.0, 'ph' => 6.2, 'water_flow' => 1.1, 'water_level' => 70, 'measured_at' => now()->subMinutes(10)]);
        $t2 = Telemetry::create(['device_id' => $device->id, 'air_temperature' => 26.0, 'ph' => 6.5, 'water_flow' => 1.3, 'water_level' => 72, 'measured_at' => now()]);

        $devices = $service->getDevicesWithTelemetries(1);
        $this->assertCount(1, $devices);
        $this->assertTrue($devices->first()->is($device));

        $series = $service->buildTelemetryOverviewSeries(collect([$t1, $t2]));
        $this->assertEquals('Water pH', $series[0]['name']);
        $this->assertEquals([6.2, 6.5], $series[0]['data']);
        $this->assertTrue(collect($series)->contains(fn (array $entry): bool => $entry['name'] === 'Water Flow (L/min)' && $entry['data'] === [1.1, 1.3]));
        $this->assertTrue(collect($series)->contains(fn (array $entry): bool => $entry['name'] === 'Water Level (%)' && $entry['data'] === [70.0, 72.0]));

        $storeResult = $service->storeTelemetry($device, [
            'air_temperature' => 25.0,
            'measured_at' => '2026-07-25 15:00:00',
        ]);
        $this->assertTrue($storeResult['success']);
        $this->assertTrue($storeResult['created']);
    }

    public function test_alert_service_building_and_acknowledgement(): void
    {
        $alertService = app(AlertService::class);
        $thresholdService = app(ThresholdService::class);
        $thresholds = $thresholdService->getAllThresholds();

        $device = Device::create([
            'device_id' => 'ESP-SVC-03',
            'name' => 'Service Node 3',
            'last_seen_at' => now()->subMinutes(10), // offline (>30s)
        ]);

        $telemetry = Telemetry::create([
            'device_id' => $device->id,
            'air_temperature' => 35.0, // High temperature alert
            'measured_at' => now(),
        ]);

        $alerts = $alertService->evaluateTelemetry($device, $telemetry);
        $this->assertNotEmpty($alerts);

        $filteredWarning = $alertService->filteredTelemetryAlerts($alerts, 'warning');
        $this->assertNotEmpty($filteredWarning);

        $this->assertDatabaseHas('alerts', [
            'device_id' => $device->id,
            'sensor' => 'air_temperature',
            'status' => 'active',
        ]);

        $airTemperatureAlert = collect($alerts)->firstWhere('sensor', 'air_temperature');
        $this->assertNotNull($airTemperatureAlert);

        $alertService->acknowledgeAlertByKey($airTemperatureAlert['key'], auth()->id());

        $this->assertDatabaseHas('alerts', [
            'device_id' => $device->id,
            'sensor' => 'air_temperature',
            'status' => 'acknowledged',
        ]);
    }

    public function test_dashboard_service_data_aggregation(): void
    {
        $dashboardService = app(DashboardService::class);

        $device = Device::create([
            'device_id' => 'ESP-SVC-04',
            'name' => 'Dashboard Node 4',
            'last_seen_at' => now(),
        ]);

        Telemetry::create([
            'device_id' => $device->id,
            'air_temperature' => 24.5,
            'humidity' => 65.0,
            'measured_at' => now(),
        ]);

        config(['leaf.dashboard.device_db_id' => $device->id]);

        $data = $dashboardService->getDashboardData();

        $this->assertArrayHasKey('device', $data);
        $this->assertArrayHasKey('temperatureValue', $data);
        $this->assertEquals('24.5', $data['temperatureValue']);
        $this->assertEquals('65', $data['humidityValue']);
        $this->assertCount(5, $data['actuatorCards']);
        $fanCard = collect($data['actuatorCards'])->firstWhere('command', 'cooling_fan');
        $this->assertNotNull($fanCard);
        $this->assertFalse(collect($data['monitoringSensors'])->contains(
            fn (array $sensor): bool => ($sensor['name'] ?? '') === 'Relative Air Humidity'
        ));
        $this->assertArrayHasKey('telemetryOverviewSeries', $data);
        $this->assertArrayHasKey('alerts', $data);
    }
}
