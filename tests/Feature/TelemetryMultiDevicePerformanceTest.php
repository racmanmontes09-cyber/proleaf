<?php

namespace Tests\Feature;

use App\Events\TelemetryReceived;
use App\Models\Device;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TelemetryMultiDevicePerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_telemetry_load_profiles_for_multiple_device_counts(): void
    {
        Event::fake([TelemetryReceived::class]);

        $devices = collect(range(1, 100))->map(function (int $index): array {
            $device = Device::create([
                'device_id' => sprintf('LEAF-LOAD-%03d', $index),
                'name' => sprintf('Load Device %03d', $index),
            ]);

            return [$device, $device->issueDeviceToken()];
        });

        $profiles = [];
        foreach ([1, 10, 50, 100] as $deviceCount) {
            $timings = [];
            $errors = 0;

            foreach ($devices->take($deviceCount)->values() as $offset => [$device, $token]) {
                $start = microtime(true);
                $response = $this->withToken($token)->postJson('/api/devices/telemetry', [
                    'air_temperature' => 24.0 + ($offset % 5),
                    'humidity' => 60 + ($offset % 10),
                    'water_temperature' => 21.5,
                    'ph' => 6.2,
                    'ec' => 1.5,
                    'water_flow' => 1.0,
                    'water_level' => 80.0,
                    'sequence_number' => $offset + 1,
                ]);
                $timings[] = (microtime(true) - $start) * 1000;

                if ($response->status() !== 201) {
                    $errors++;
                }
            }

            sort($timings);
            $count = count($timings);
            $profiles[$deviceCount] = [
                'avg' => array_sum($timings) / max($count, 1),
                'median' => $timings[(int) floor(($count - 1) / 2)] ?? 0.0,
                'p95' => $timings[(int) min($count - 1, ceil($count * 0.95) - 1)] ?? 0.0,
                'p99' => $timings[(int) min($count - 1, ceil($count * 0.99) - 1)] ?? 0.0,
                'max' => $timings[$count - 1] ?? 0.0,
                'errors' => $errors,
            ];

            $this->assertSame(0, $errors, "Telemetry load profile for {$deviceCount} devices had request errors.");
        }

        fwrite(STDERR, sprintf(
            "Telemetry multi-device local profile: 1=%s 10=%s 50=%s 100=%s\n",
            $this->formatProfile($profiles[1]),
            $this->formatProfile($profiles[10]),
            $this->formatProfile($profiles[50]),
            $this->formatProfile($profiles[100]),
        ));

        $this->assertDatabaseCount('telemetries', 161);
    }

    private function formatProfile(array $profile): string
    {
        return sprintf(
            'avg %.2fms median %.2fms p95 %.2fms p99 %.2fms max %.2fms errors %d',
            $profile['avg'],
            $profile['median'],
            $profile['p95'],
            $profile['p99'],
            $profile['max'],
            $profile['errors'],
        );
    }
}
