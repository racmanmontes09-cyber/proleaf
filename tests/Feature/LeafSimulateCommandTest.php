<?php

namespace Tests\Feature;

use App\Models\Device;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LeafSimulateCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_simulator_sends_telemetry_to_the_existing_api_contract(): void
    {
        config()->set('leaf.simulation.device_token', 'leaf_test_token');

        Http::fake([
            '*' => Http::response(['success' => true, 'message' => 'ok'], 201),
        ]);

        $this->artisan('leaf:simulate', [
            '--devices' => 2,
            '--interval' => 0,
            '--duration' => 0,
        ])->assertExitCode(0);

        Http::assertSentCount(2);

        Http::assertSent(function ($request): bool {
            $this->assertSame('POST', $request->method());
            $this->assertStringEndsWith('/api/devices/telemetry', $request->url());

            $payload = $request->data();
            $this->assertArrayHasKey('device_id', $payload);
            $this->assertArrayHasKey('air_temperature', $payload);
            $this->assertArrayHasKey('humidity', $payload);
            $this->assertArrayHasKey('water_temperature', $payload);
            $this->assertArrayHasKey('ph', $payload);
            $this->assertArrayHasKey('ec', $payload);
            $this->assertArrayHasKey('water_flow', $payload);
            $this->assertArrayHasKey('water_level', $payload);
            $this->assertArrayHasKey('signal_strength', $payload);
            $this->assertArrayHasKey('battery_voltage', $payload);
            $this->assertArrayHasKey('firmware_version', $payload);
            $this->assertArrayHasKey('payload_version', $payload);
            $this->assertArrayHasKey('sequence_number', $payload);
            $this->assertArrayHasKey('measured_at', $payload);

            return true;
        });
    }

    public function test_live_dashboard_simulator_uses_env_device_and_existing_api_path(): void
    {
        $device = Device::create([
            'device_id' => 'esp32-001',
            'name' => 'Greenhouse ESP32',
            'last_seen_at' => now()->subMinute(),
        ]);
        $token = $device->issueDeviceToken();

        $this->withLeafEnvironment([
            'LEAF_DEVICE_ID' => 'esp32-001',
            'LEAF_DEVICE_NAME' => 'Greenhouse ESP32',
            'LEAF_DEVICE_TOKEN' => $token,
        ], function () use ($token): void {
            $exitCode = Artisan::call('leaf:simulate-telemetry', [
                '--count' => 3,
                '--interval' => 0,
            ]);

            $output = Artisan::output();

            $this->assertSame(0, $exitCode, $output);
            $this->assertStringNotContainsString($token, $output);
        });

        $device->refresh();
        $readings = $device->telemetries()->orderBy('id')->get();

        $this->assertNotNull($device->last_seen_at);
        $this->assertCount(3, $readings);
        $this->assertSame([1, 2, 3], $readings->pluck('sequence_number')->all());

        foreach ($readings as $reading) {
            $this->assertSame($device->id, $reading->device_id);
            $this->assertGreaterThanOrEqual(29.0, $reading->air_temperature);
            $this->assertLessThanOrEqual(34.0, $reading->air_temperature);
            $this->assertGreaterThanOrEqual(25.0, $reading->water_temperature);
            $this->assertLessThanOrEqual(29.0, $reading->water_temperature);
            $this->assertGreaterThanOrEqual(5.8, $reading->ph);
            $this->assertLessThanOrEqual(6.8, $reading->ph);
            $this->assertGreaterThanOrEqual(1.0, $reading->ec);
            $this->assertLessThanOrEqual(1.8, $reading->ec);
            $this->assertGreaterThanOrEqual(65.0, $reading->water_level);
            $this->assertLessThanOrEqual(90.0, $reading->water_level);
            $this->assertGreaterThanOrEqual(0.8, $reading->water_flow);
            $this->assertLessThanOrEqual(1.8, $reading->water_flow);
            $this->assertNotNull($reading->measured_at);
        }
    }

    public function test_live_dashboard_simulator_refuses_token_for_another_device(): void
    {
        $device = Device::create([
            'device_id' => 'esp32-other',
            'name' => 'Other ESP32',
        ]);
        $token = $device->issueDeviceToken();

        $this->withLeafEnvironment([
            'LEAF_DEVICE_ID' => 'esp32-001',
            'LEAF_DEVICE_NAME' => 'Greenhouse ESP32',
            'LEAF_DEVICE_TOKEN' => $token,
        ], function () use ($token): void {
            $exitCode = Artisan::call('leaf:simulate-telemetry', [
                '--count' => 1,
                '--interval' => 0,
            ]);

            $output = Artisan::output();

            $this->assertSame(1, $exitCode);
            $this->assertStringContainsString('LEAF_DEVICE_ID does not match the authenticated database device', $output);
            $this->assertStringNotContainsString($token, $output);
        });

        $this->assertDatabaseCount('telemetries', 0);
    }

    private function withLeafEnvironment(array $values, Closure $callback): void
    {
        $previous = [];

        foreach ($values as $key => $value) {
            $previous[$key] = [
                'getenv' => getenv($key),
                'env_exists' => array_key_exists($key, $_ENV),
                'env' => $_ENV[$key] ?? null,
                'server_exists' => array_key_exists($key, $_SERVER),
                'server' => $_SERVER[$key] ?? null,
            ];

            putenv($key.'='.$value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        try {
            $callback();
        } finally {
            foreach ($previous as $key => $state) {
                if ($state['getenv'] === false) {
                    putenv($key);
                } else {
                    putenv($key.'='.$state['getenv']);
                }

                if ($state['env_exists']) {
                    $_ENV[$key] = $state['env'];
                } else {
                    unset($_ENV[$key]);
                }

                if ($state['server_exists']) {
                    $_SERVER[$key] = $state['server'];
                } else {
                    unset($_SERVER[$key]);
                }
            }
        }
    }
}
