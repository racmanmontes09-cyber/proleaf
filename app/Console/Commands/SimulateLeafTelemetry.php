<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SimulateLeafTelemetry extends Command
{
    protected $signature = 'leaf:simulate {--devices=10 : Number of devices to simulate (1-100)} {--interval=1 : Delay between requests in seconds} {--duration=60 : Total runtime in seconds; 0 means run until interrupted}';

    protected $description = 'Simulate Project L.E.A.F ESP32 telemetry by posting to the existing REST API';

    public function handle(): int
    {
        $devices = max(1, min(100, (int) $this->option('devices')));
        $interval = max(0, (int) $this->option('interval'));
        $duration = max(0, (int) $this->option('duration'));

        if ($interval < 0 || $duration < 0) {
            $this->error('Interval and duration must be non-negative.');

            return self::FAILURE;
        }

        $baseUrl = rtrim(config('app.url', 'http://localhost'), '/');
        $endpoint = $baseUrl.'/api/devices/telemetry';
        $tokens = $this->resolveTokens();

        if ($tokens === []) {
            $this->error('No device token configured. Set LEAF_SIMULATION_DEVICE_TOKEN or leaf.simulation.device_token.');

            return self::FAILURE;
        }

        $this->info("Starting telemetry simulation against {$endpoint}");
        $this->info("Devices: {$devices} | Interval: {$interval}s | Duration: {$duration}s (0 = until Ctrl+C)");

        $startTime = microtime(true);
        $requestsSent = 0;
        $successfulRequests = 0;
        $failedRequests = 0;
        $totalDurationMs = 0;
        $sequenceByDevice = [];

        $signal = function () use (&$running): void {
            $running = false;
        };

        $running = true;
        $this->trap([SIGINT, SIGTERM], function () use (&$running): void {
            $running = false;
            $this->info('\nStopping simulator...');
        });

        while ($running) {
            $now = microtime(true);
            if ($duration > 0 && ($now - $startTime) >= $duration) {
                break;
            }

            $batchCompleted = false;

            foreach (range(1, $devices) as $deviceIndex) {
                if (! $running) {
                    break;
                }

                $deviceId = $this->buildDeviceId($deviceIndex);
                $sequenceByDevice[$deviceId] = ($sequenceByDevice[$deviceId] ?? 0) + 1;
                $payload = $this->buildPayload($deviceId, $sequenceByDevice[$deviceId]);
                $token = $tokens[($deviceIndex - 1) % count($tokens)];

                $requestStart = microtime(true);
                try {
                    $response = Http::withToken($token)
                        ->timeout(10)
                        ->acceptJson()
                        ->post($endpoint, $payload);

                    $requestsSent++;
                    $elapsedMs = (microtime(true) - $requestStart) * 1000;
                    $totalDurationMs += $elapsedMs;

                    if ($response->successful()) {
                        $successfulRequests++;
                    } else {
                        $failedRequests++;
                    }

                    $this->writeLiveStats($requestsSent, $successfulRequests, $failedRequests, $totalDurationMs);
                } catch (\Throwable $exception) {
                    $requestsSent++;
                    $failedRequests++;
                    $totalDurationMs += (microtime(true) - $requestStart) * 1000;
                    $this->writeLiveStats($requestsSent, $successfulRequests, $failedRequests, $totalDurationMs);
                    $this->warn('Request failed: '.$exception->getMessage());
                }

                if ($interval > 0) {
                    usleep($interval * 1000000);
                }
            }

            $batchCompleted = true;

            if ($duration > 0 && ($now - $startTime) >= $duration) {
                break;
            }

            if ($duration === 0 && $batchCompleted) {
                break;
            }
        }

        $this->newLine();
        $this->info('Simulation completed.');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Requests sent', $requestsSent],
                ['Successful requests', $successfulRequests],
                ['Failed requests', $failedRequests],
                ['Average response time', $requestsSent > 0 ? round($totalDurationMs / $requestsSent, 2).' ms' : '0 ms'],
            ]
        );

        return self::SUCCESS;
    }

    protected function resolveTokens(): array
    {
        $configuredTokens = config('leaf.simulation.device_tokens') ?? env('LEAF_SIMULATION_DEVICE_TOKENS');
        if (is_string($configuredTokens) && $configuredTokens !== '') {
            return array_values(array_filter(array_map('trim', explode(',', $configuredTokens))));
        }

        $singleToken = config('leaf.simulation.device_token') ?? env('LEAF_SIMULATION_DEVICE_TOKEN');
        if (is_string($singleToken) && $singleToken !== '') {
            return [$singleToken];
        }

        return [];
    }

    protected function buildDeviceId(int $deviceIndex): string
    {
        return 'leaf-device-'.str_pad((string) $deviceIndex, 3, '0', STR_PAD_LEFT);
    }

    protected function buildPayload(string $deviceId, int $sequenceNumber): array
    {
        $baseTemperature = 24 + (fmod($sequenceNumber * 7, 9));
        $airTemperature = round($baseTemperature + (mt_rand(0, 100) / 100), 1);
        $humidity = round(mt_rand(40, 95), 0);
        $waterTemperature = round(18 + (mt_rand(0, 120) / 10), 1);
        $ph = round(5.0 + (mt_rand(0, 150) / 100), 1);
        $ec = round(0.8 + (mt_rand(0, 170) / 100), 2);
        $waterLevel = round(mt_rand(0, 100), 0);
        $waterFlow = round(0.2 + (mt_rand(0, 480) / 100), 1);
        $signalStrength = -60 - mt_rand(0, 35);
        $batteryVoltage = round(3.2 + (mt_rand(0, 150) / 100), 2);
        $uptimeSeconds = 86400 + (mt_rand(0, 200000) / 10);
        $freeHeap = 150000 + mt_rand(0, 30000);

        return [
            'device_id' => $deviceId,
            'air_temperature' => $airTemperature,
            'humidity' => $humidity,
            'water_temperature' => $waterTemperature,
            'ph' => $ph,
            'ec' => $ec,
            'water_flow' => $waterFlow,
            'water_level' => $waterLevel,
            'measured_at' => now()->toIso8601String(),
            'sequence_number' => $sequenceNumber,
            'firmware_version' => 'leaf-sim-1.0',
            'signal_strength' => $signalStrength,
            'battery_voltage' => $batteryVoltage,
            'payload_version' => 1,
        ];
    }

    protected function writeLiveStats(int $requestsSent, int $successfulRequests, int $failedRequests, float $totalDurationMs): void
    {
        $avg = $requestsSent > 0 ? round($totalDurationMs / $requestsSent, 2) : 0;

        $this->output->write(sprintf("\rRequests sent: %d | Successful: %d | Failed: %d | Avg response: %.2f ms", $requestsSent, $successfulRequests, $failedRequests, $avg));
    }
}
