<?php

namespace App\Console\Commands;

use App\Models\Device;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SimulateLeafDashboardTelemetry extends Command
{
    protected $signature = 'leaf:simulate-telemetry
        {--count=1 : Number of telemetry readings to send before stopping}
        {--interval=1 : Delay between readings in seconds}
        {--continuous : Run until interrupted instead of stopping after --count}
        {--skip-heartbeat : Skip the initial authenticated heartbeat request}';

    protected $description = 'Simulate live Project L.E.A.F telemetry for the .env ESP32 device through the existing REST API path';

    private const FIRMWARE_VERSION = 'leaf-sim-telemetry-1.0';

    private const SENSOR_PROFILE = [
        'air_temperature' => ['min' => 29.0, 'max' => 34.0, 'decimals' => 1, 'period' => 9.0, 'phase' => 0.1],
        'humidity' => ['min' => 60.0, 'max' => 80.0, 'decimals' => 0, 'period' => 11.0, 'phase' => 1.3],
        'water_temperature' => ['min' => 25.0, 'max' => 29.0, 'decimals' => 1, 'period' => 13.0, 'phase' => 2.1],
        'ph' => ['min' => 5.8, 'max' => 6.8, 'decimals' => 2, 'period' => 15.0, 'phase' => 0.8],
        'ec' => ['min' => 1.0, 'max' => 1.8, 'decimals' => 2, 'period' => 17.0, 'phase' => 2.7],
        'water_level' => ['min' => 65.0, 'max' => 90.0, 'decimals' => 0, 'period' => 24.0, 'phase' => 1.9],
        'water_flow' => ['min' => 0.8, 'max' => 1.8, 'decimals' => 2, 'period' => 8.0, 'phase' => 2.9],
    ];

    private bool $running = true;

    public function handle(): int
    {
        $credentials = $this->resolveCredentials();
        if ($credentials === null) {
            return self::FAILURE;
        }

        [$deviceId, $deviceName, $token, $device] = $credentials;

        $count = (int) $this->option('count');
        $interval = (float) $this->option('interval');
        $continuous = (bool) $this->option('continuous');

        if (! $continuous && $count < 1) {
            $this->error('--count must be at least 1 unless --continuous is used.');

            return self::FAILURE;
        }

        if ($interval < 0) {
            $this->error('--interval must be zero or greater.');

            return self::FAILURE;
        }

        $this->trap([SIGINT, SIGTERM], function (): void {
            $this->running = false;
            $this->newLine();
            $this->info('Stopping simulator...');
        });

        $this->info('Starting live telemetry simulator.');
        $this->line("Target device: {$deviceId} ({$deviceName})");
        $this->line('Telemetry endpoint: POST /api/devices/telemetry');
        $this->line('Authentication: Authorization bearer device token from .env');
        $this->line($continuous ? 'Mode: continuous until interrupted' : "Readings: {$count} | Interval: {$interval}s");

        if (! (bool) $this->option('skip-heartbeat')) {
            $heartbeat = $this->sendHeartbeat($deviceName, $token);
            if (! $this->responseSucceeded($heartbeat)) {
                $this->error('Initial heartbeat failed with HTTP '.$heartbeat->getStatusCode().'. '.$this->responseMessage($heartbeat));

                return self::FAILURE;
            }

            $this->line('Initial heartbeat accepted; device status should be current.');
        }

        $sequence = ((int) ($device->telemetries()->max('sequence_number') ?? 0));
        $sent = 0;
        $accepted = 0;
        $failed = 0;
        $startedAt = microtime(true);

        while ($this->running && ($continuous || $sent < $count)) {
            $sequence++;
            $payload = $this->buildTelemetryPayload($deviceId, $sequence);
            $response = $this->sendTelemetry($payload, $token);
            $sent++;

            if ($this->responseSucceeded($response)) {
                $accepted++;
                $this->line($this->acceptedLine($sent, $response, $payload));
            } else {
                $failed++;
                $this->warn('Reading #'.$sent.' failed with HTTP '.$response->getStatusCode().'. '.$this->responseMessage($response));
            }

            if (! $continuous && $sent >= $count) {
                break;
            }

            if ($interval > 0) {
                usleep((int) round($interval * 1000000));
            }
        }

        $elapsed = round(microtime(true) - $startedAt, 2);
        $this->info('Telemetry simulation completed.');
        $this->table(['Metric', 'Value'], [
            ['Device', $deviceId],
            ['Readings attempted', $sent],
            ['Accepted', $accepted],
            ['Failed', $failed],
            ['Elapsed', $elapsed.' s'],
        ]);

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function resolveCredentials(): ?array
    {
        $deviceId = $this->envValue('LEAF_DEVICE_ID');
        $deviceName = $this->envValue('LEAF_DEVICE_NAME');
        $token = $this->envValue('LEAF_DEVICE_TOKEN');

        if ($deviceId === null) {
            $this->error('LEAF_DEVICE_ID is missing from .env.');

            return null;
        }

        if ($deviceName === null) {
            $this->error('LEAF_DEVICE_NAME is missing from .env.');

            return null;
        }

        if ($token === null) {
            $this->error('LEAF_DEVICE_TOKEN is missing from .env.');

            return null;
        }

        $device = Device::findForDeviceToken($token);
        if (! $device instanceof Device) {
            $this->error('No local device matches LEAF_DEVICE_TOKEN. The simulator will not create devices.');

            return null;
        }

        if ($device->device_id !== $deviceId) {
            $this->error('LEAF_DEVICE_ID does not match the authenticated database device. The simulator will not send telemetry.');

            return null;
        }

        if ($device->hasExpiredDeviceToken()) {
            $this->error('The configured device token is expired.');

            return null;
        }

        if ($device->hasRevokedDeviceToken()) {
            $this->error('The configured device token is revoked.');

            return null;
        }

        if (! $device->isActive()) {
            $this->error('The configured device is disabled.');

            return null;
        }

        return [$deviceId, $deviceName, $token, $device];
    }

    private function envValue(string $key): ?string
    {
        $value = env($key);

        if ($value === null || $value === '') {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        }

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function sendHeartbeat(string $deviceName, string $token): Response
    {
        return $this->postJson('/api/devices/heartbeat', [
            'name' => $deviceName,
            'firmware_version' => self::FIRMWARE_VERSION,
            'local_ip_address' => '127.0.0.1',
            'wifi_rssi' => -58,
            'uptime_seconds' => max(1, (int) (microtime(true) % 1000000)),
            'free_heap' => 180000,
            'last_boot_at' => now()->subHours(2)->format('Y-m-d H:i:s'),
        ], $token);
    }

    private function sendTelemetry(array $payload, string $token): Response
    {
        return $this->postJson('/api/devices/telemetry', $payload, $token);
    }

    private function postJson(string $uri, array $payload, string $token): Response
    {
        $content = json_encode($payload, JSON_THROW_ON_ERROR);
        $request = Request::create($uri, 'POST', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            'REMOTE_ADDR' => '127.0.0.1',
        ], $content);

        $kernel = app(HttpKernel::class);
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);

        return $response;
    }

    private function buildTelemetryPayload(string $deviceId, int $sequenceNumber): array
    {
        return [
            'device_id' => $deviceId,
            'air_temperature' => $this->sensorValue('air_temperature', $sequenceNumber),
            'humidity' => $this->sensorValue('humidity', $sequenceNumber),
            'water_temperature' => $this->sensorValue('water_temperature', $sequenceNumber),
            'ph' => $this->sensorValue('ph', $sequenceNumber),
            'ec' => $this->sensorValue('ec', $sequenceNumber),
            'water_flow' => $this->sensorValue('water_flow', $sequenceNumber),
            'water_level' => $this->sensorValue('water_level', $sequenceNumber),
            'measured_at' => now()->format('Y-m-d\TH:i:s.uP'),
            'sequence_number' => $sequenceNumber,
            'firmware_version' => self::FIRMWARE_VERSION,
            'signal_strength' => (int) round($this->boundedWave($sequenceNumber, -72, -48, 10.0, 0.4)),
            'battery_voltage' => round($this->boundedWave($sequenceNumber, 4.7, 5.1, 18.0, 1.1), 2),
            'payload_version' => 1,
        ];
    }

    private function sensorValue(string $field, int $sequenceNumber): float|int
    {
        $profile = self::SENSOR_PROFILE[$field];
        $value = $this->boundedWave(
            $sequenceNumber,
            $profile['min'],
            $profile['max'],
            $profile['period'],
            $profile['phase'],
        );

        $rounded = round($value, $profile['decimals']);

        return $profile['decimals'] === 0 ? (int) $rounded : $rounded;
    }

    private function boundedWave(int $sequenceNumber, float $min, float $max, float $period, float $phase): float
    {
        $center = ($min + $max) / 2;
        $amplitude = ($max - $min) * 0.42;
        $microDrift = ($max - $min) * 0.05;
        $value = $center
            + sin(($sequenceNumber / $period) + $phase) * $amplitude
            + sin(($sequenceNumber / ($period / 2)) + $phase) * $microDrift;

        return max($min, min($max, $value));
    }

    private function responseSucceeded(Response $response): bool
    {
        return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
    }

    private function responseMessage(Response $response): string
    {
        $decoded = json_decode($response->getContent(), true);

        if (is_array($decoded) && isset($decoded['message']) && is_string($decoded['message'])) {
            return $decoded['message'];
        }

        return Response::$statusTexts[$response->getStatusCode()] ?? 'Unexpected response.';
    }

    private function acceptedLine(int $readingNumber, Response $response, array $payload): string
    {
        return sprintf(
            'Reading #%d accepted (%d): air %.1f C, water %.1f C, pH %.2f, EC %.2f, level %d%%, flow %.2f L/min',
            $readingNumber,
            $response->getStatusCode(),
            $payload['air_temperature'],
            $payload['water_temperature'],
            $payload['ph'],
            $payload['ec'],
            $payload['water_level'],
            $payload['water_flow'],
        );
    }
}
