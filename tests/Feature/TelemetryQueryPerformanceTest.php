<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Telemetry;
use App\Services\TelemetryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TelemetryQueryPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_latest_telemetry_query_is_bounded(): void
    {
        $device = Device::create(['device_id' => 'ESP-TEST-01', 'name' => 'Test Device']);

        foreach (range(1, 200) as $index) {
            Telemetry::create([
                'device_id' => $device->id,
                'measured_at' => now()->subMinutes($index),
            ]);
        }

        DB::enableQueryLog();
        app(TelemetryService::class)->getTelemetryHistory($device, 100);
        $queries = DB::getQueryLog();

        $this->assertCount(1, $queries, 'getTelemetryHistory should execute exactly one query');
        $this->assertStringContainsString('limit', strtolower($queries[0]['query']), 'Query should include a limit clause');
    }

    public function test_get_devices_with_telemetries_uses_latest_telemetry_relation(): void
    {
        $device = Device::create(['device_id' => 'ESP-TEST-02', 'name' => 'Test Device 2', 'last_seen_at' => now()]);

        Telemetry::create(['device_id' => $device->id, 'air_temperature' => 22.5, 'measured_at' => now()->subMinute()]);

        DB::enableQueryLog();
        $devices = app(TelemetryService::class)->getDevicesWithTelemetries(1);
        $queries = DB::getQueryLog();

        $this->assertLessThanOrEqual(2, count($queries), 'getDevicesWithTelemetries should execute at most two queries with eager loading');
        $this->assertSame(1, $devices->count());
        $this->assertNotNull($devices->first()->latestTelemetry);
    }
}
