<?php

namespace Tests\Feature;

use App\Events\TelemetryReceived;
use App\Models\Device;
use App\Services\TelemetryService;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TelemetryPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_telemetry_event_implements_should_broadcast_now(): void
    {
        $implements = class_implements(TelemetryReceived::class);
        $this->assertArrayHasKey(
            ShouldBroadcastNow::class,
            $implements,
            'TelemetryReceived must implement ShouldBroadcastNow to bypass queue delay.'
        );
    }

    public function test_sub_30ms_backend_processing_latency_per_telemetry_post(): void
    {
        Event::fake([TelemetryReceived::class]);

        $device = Device::create([
            'device_id' => 'LEAF-ESP32-PERF',
            'name' => 'ESP32 Performance Node',
        ]);
        $token = $device->issueDeviceToken();

        $timings = [];

        for ($i = 0; $i < 50; $i++) {
            $start = microtime(true);

            $response = $this->withToken($token)->postJson('/api/devices/telemetry', [
                'air_temperature' => 24.0 + ($i * 0.01),
                'humidity' => 60.0,
                'water_temperature' => 21.0,
                'ph' => 6.2,
                'ec' => 1.5,
                'water_flow' => 2.0,
                'water_level' => 85.0,
                'sequence_number' => $i + 1,
            ]);

            $elapsedMs = (microtime(true) - $start) * 1000;
            $timings[] = $elapsedMs;

            $response->assertCreated();
        }

        $avgLatencyMs = array_sum($timings) / count($timings);
        $maxLatencyMs = max($timings);

        $this->assertLessThan(
            30.0,
            $avgLatencyMs,
            "Average backend latency must be under 30ms (actual avg: {$avgLatencyMs}ms, max: {$maxLatencyMs}ms)."
        );
    }

    public function test_high_throughput_batch_telemetry_stores_1000_records_cleanly(): void
    {
        Event::fake([TelemetryReceived::class]);

        $device = Device::create([
            'device_id' => 'LEAF-ESP32-BATCH',
            'name' => 'ESP32 Batch Node',
        ]);

        $service = app(TelemetryService::class);
        $start = microtime(true);

        for ($i = 1; $i <= 1000; $i++) {
            $result = $service->storeTelemetry($device, [
                'air_temperature' => 25.0 + ($i % 5),
                'humidity' => 60.0 + ($i % 10),
                'water_temperature' => 21.5,
                'ph' => 6.2,
                'ec' => 1.5,
                'water_flow' => 2.1,
                'water_level' => 85.0,
                'sequence_number' => $i,
            ]);

            $this->assertTrue($result['success']);
        }

        $totalSeconds = microtime(true) - $start;

        $this->assertDatabaseCount('telemetries', 1000);
        $this->assertLessThan(
            5.0,
            $totalSeconds,
            "1000 telemetry inserts should take less than 5 seconds in total (actual: {$totalSeconds}s)."
        );
    }
}
