<?php

namespace Tests\Feature;

use App\Events\TelemetryReceived;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TelemetryPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_telemetry_posts_do_not_dispatch_broadcast_event_on_critical_path(): void
    {
        Event::fake([TelemetryReceived::class]);

        $device = Device::create([
            'device_id' => 'LEAF-ESP32-NO-BROADCAST',
            'name' => 'ESP32 No Broadcast Node',
        ]);
        $token = $device->issueDeviceToken();

        $this->withToken($token)->postJson('/api/devices/telemetry', [
            'air_temperature' => 24.0,
            'humidity' => 60.0,
            'water_temperature' => 21.0,
            'ph' => 6.2,
            'ec' => 1.5,
            'water_flow' => 2.0,
            'water_level' => 85.0,
            'sequence_number' => 1,
        ])->assertCreated();

        Event::assertNotDispatched(TelemetryReceived::class);
    }

    public function test_feature_request_reports_latency_profile_for_telemetry_posts(): void
    {
        Event::fake([TelemetryReceived::class]);

        $device = Device::create([
            'device_id' => 'LEAF-ESP32-PERF',
            'name' => 'ESP32 Performance Node',
        ]);
        $token = $device->issueDeviceToken();

        $timings = [];
        $sampleCount = 25;

        for ($i = 0; $i < $sampleCount; $i++) {
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

            $response->assertCreated()
                ->assertJson([
                    'success' => true,
                    'message' => 'Telemetry stored successfully.',
                ]);
        }

        $this->assertDatabaseCount('telemetries', $sampleCount);
        Event::assertNotDispatched(TelemetryReceived::class);

        $latencyProfile = $this->summarizeLatency($timings);
        $this->assertSame($sampleCount, count($timings));

        fwrite(STDOUT, sprintf(
            "Telemetry feature latency report: avg=%.2fms min=%.2fms median=%.2fms max=%.2fms (%d samples)\n",
            $latencyProfile['average'],
            $latencyProfile['minimum'],
            $latencyProfile['median'],
            $latencyProfile['maximum'],
            $sampleCount,
        ));
    }

    private function summarizeLatency(array $timings): array
    {
        sort($timings, SORT_NUMERIC);

        $count = count($timings);
        $middle = intdiv($count, 2);
        $median = $count % 2 === 0
            ? (($timings[$middle - 1] + $timings[$middle]) / 2)
            : $timings[$middle];

        return [
            'average' => array_sum($timings) / $count,
            'minimum' => $timings[0],
            'median' => $median,
            'maximum' => $timings[$count - 1],
        ];
    }
}
