<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Broadcast;

class TestTelemetryStream extends Command
{
    protected $signature = 'telemetry:test-stream
        {--count=10 : Number of payloads to send (0 = run until Ctrl+C)}
        {--interval=2 : Seconds between each payload}
        {--token= : Device bearer token (overrides LEAF_DEVICE_TOKEN env)}';

    protected $description = 'End-to-end test: sends mock telemetry to the API and verifies the broadcast pipeline';

    private int $sent = 0;
    private int $ok = 0;
    private int $fail = 0;
    private bool $running = true;

    public function handle(): int
    {
        $this->newLine();
        $this->line('<bg=green;fg=white>  PROJECT L.E.A.F.  </> WebSocket Telemetry Pipeline Test');
        $this->line(str_repeat('─', 60));

        // ── 1. Resolve device token ──────────────────────────────────
        $token = $this->option('token')
            ?: env('LEAF_DEVICE_TOKEN')
            ?: config('leaf.simulation.device_token');

        if (empty($token)) {
            $this->error('No device token found.');
            $this->newLine();
            $this->line('Set one of:');
            $this->bullet('  export LEAF_DEVICE_TOKEN="leaf_..."');
            $this->bullet('  php artisan telemetry:test-stream --token="leaf_..."');
            return self::FAILURE;
        }

        $this->info("Device token: " . substr($token, 0, 12) . '...' . substr($token, -6));

        // ── 2. Resolve target URL ────────────────────────────────────
        $baseUrl = rtrim(config('app.url', 'http://localhost'), '/');
        $endpoint = $baseUrl . '/api/devices/telemetry';
        $this->info("API endpoint: {$endpoint}");

        // ── 3. Pre-flight: connectivity checks ───────────────────────
        $this->newLine();
        $this->line('<fg=yellow>── Pre-flight Checks ──</>');

        $this->checkApiReachable($baseUrl);
        $this->checkReverbPort();
        $this->checkBroadcastConfig();

        // ── 4. Send test payload to verify auth + broadcast ──────────
        $this->newLine();
        $this->line('<fg=yellow>── Single-Shot Verification ──</>');

        $verified = $this->sendSingleVerification($endpoint, $token);

        if (! $verified) {
            $this->newLine();
            $this->error('Single-shot verification failed. Fix the issue above before streaming.');
            return self::FAILURE;
        }

        // ── 5. Stream mock telemetry ─────────────────────────────────
        $count = (int) $this->option('count');
        $interval = max(1, (int) $this->option('interval'));

        $this->newLine();
        $this->line('<fg=yellow>── Streaming Mock Telemetry ──</>');
        $this->info("Interval: {$interval}s | Payloads: " . ($count > 0 ? $count : 'unlimited (Ctrl+C to stop)'));
        $this->newLine();

        $this->trap([SIGINT, SIGTERM], function () {
            $this->running = false;
            $this->newLine();
            $this->info('Interrupted — finishing...');
        });

        $startTime = microtime(true);
        $seq = 0;

        while ($this->running) {
            if ($count > 0 && $this->sent >= $count) {
                break;
            }

            $seq++;
            $payload = $this->buildPayload($seq);
            $this->sendPayload($endpoint, $token, $payload, $seq);

            if ($this->running && ($count <= 0 || $this->sent < $count)) {
                sleep($interval);
            }
        }

        // ── 6. Summary ──────────────────────────────────────────────
        $elapsed = round(microtime(true) - $startTime, 1);
        $this->newLine();
        $this->line(str_repeat('─', 60));
        $this->line('<bg=green;fg=white>  RESULTS  </>');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total sent', $this->sent],
                ['Successful (2xx)', $this->ok],
                ['Failed', $this->fail],
                ['Elapsed', "{$elapsed}s"],
                ['Avg response', $this->sent > 0 ? round(($elapsed / $this->sent) * 1000) . ' ms' : 'N/A'],
            ]
        );

        $this->printBrowserInstructions();

        return $this->fail === 0 ? self::SUCCESS : self::FAILURE;
    }

    // ── Pre-flight checks ───────────────────────────────────────────

    private function checkApiReachable(string $baseUrl): void
    {
        try {
            $resp = Http::timeout(5)->get($baseUrl);
            $this->bullet("API reachable (HTTP {$resp->status()})");
        } catch (\Throwable $e) {
            $this->warn("API unreachable at {$baseUrl}: {$e->getMessage()}");
        }
    }

    private function checkReverbPort(): void
    {
        $host = env('REVERB_HOST', '127.0.0.1');
        $port = env('REVERB_PORT', 8080);
        $fp = @fsockopen($host, (int) $port, $errno, $errstr, 3);

        if ($fp) {
            fclose($fp);
            $this->bullet("Reverb WebSocket port {$port} is open on {$host}");
        } else {
            $this->warn("Reverb port {$port} not reachable on {$host} (errno: {$errno}: {$errstr})");
        }
    }

    private function checkBroadcastConfig(): void
    {
        $driver = config('broadcasting.default');
        $this->bullet("Broadcast driver: {$driver}");

        if ($driver === 'reverb') {
            $key = env('REVERB_APP_KEY');
            $secret = env('REVERB_APP_SECRET');
            if (empty($key) || empty($secret)) {
                $this->warn('REVERB_APP_KEY or REVERB_APP_SECRET is empty — broadcasts will fail auth.');
            } else {
                $this->bullet("Reverb app key: " . substr($key, 0, 8) . '...');
            }
        }
    }

    // ── Single-shot verification ─────────────────────────────────────

    private function sendSingleVerification(string $endpoint, string $token): bool
    {
        $payload = $this->buildPayload(1);

        $this->line('Sending verification payload...');
        $start = microtime(true);

        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->acceptJson()
                ->post($endpoint, $payload);

            $elapsed = round((microtime(true) - $start) * 1000, 1);
            $status = $response->status();

            if ($response->successful()) {
                $this->info("  [{$status}] Telemetry accepted ({$elapsed}ms)");
                $body = $response->json();
                $this->bullet("  success: " . var_export($body['success'] ?? null, true));
                $this->bullet("  message: " . ($body['message'] ?? 'N/A'));

                $this->newLine();
                $this->line('<fg=green>  ✓ API processed the payload.</>');
                $this->line('<fg=green>  ✓ TelemetryReceived event was dispatched to Reverb.</>');
                $this->line('<fg=cyan>  → Open the dashboard in your browser to see live updates.</>');
                return true;
            }

            $this->error("  [{$status}] Request failed ({$elapsed}ms)");
            $body = $response->json();
            if (isset($body['message'])) {
                $this->warn('  ' . $body['message']);
            }
            return false;
        } catch (\Throwable $e) {
            $this->error('  Request exception: ' . $e->getMessage());
            return false;
        }
    }

    // ── Streaming ────────────────────────────────────────────────────

    private function sendPayload(string $endpoint, string $token, array $payload, int $seq): void
    {
        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->acceptJson()
                ->post($endpoint, $payload);

            $this->sent++;
            $status = $response->status();

            if ($response->successful()) {
                $this->ok++;
                $temp = $payload['air_temperature'];
                $ph = $payload['ph'];
                $ec = $payload['ec'];
                $wl = $payload['water_level'];
                $wf = $payload['water_flow'];
                $this->info(
                    "  #{$seq}  [{$status}]  "
                    . "air={$temp}°C  pH={$ph}  EC={$ec}  "
                    . "level={$wl}%  flow={$wf} L/min"
                );
            } else {
                $this->fail++;
                $msg = $response->json('message', 'unknown error');
                $this->error("  #{$seq}  [{$status}]  {$msg}");
            }
        } catch (\Throwable $e) {
            $this->sent++;
            $this->fail++;
            $this->error("  #{$seq}  [ERR]  {$e->getMessage()}");
        }
    }

    // ── Payload generator ────────────────────────────────────────────

    private function buildPayload(int $seq): array
    {
        // Realistic fluctuating values with slow sinusoidal drift
        $t = $seq * 0.5;

        return [
            'air_temperature' => round(24.0 + 3.0 * sin($t * 0.3) + (mt_rand(-50, 50) / 100), 1),
            'humidity' => round(65.0 + 10.0 * sin($t * 0.2) + mt_rand(-3, 3), 0),
            'water_temperature' => round(20.0 + 2.0 * sin($t * 0.15) + (mt_rand(-30, 30) / 100), 1),
            'ph' => round(6.2 + 0.4 * sin($t * 0.25) + (mt_rand(-20, 20) / 100), 1),
            'ec' => round(1.4 + 0.3 * sin($t * 0.18) + (mt_rand(-10, 10) / 100), 2),
            'water_flow' => round(1.2 + 0.5 * sin($t * 0.4) + (mt_rand(-20, 20) / 100), 1),
            'water_level' => round(60.0 + 15.0 * sin($t * 0.1) + mt_rand(-2, 2), 0),
            'measured_at' => now()->toIso8601String(),
            'sequence_number' => $seq,
            'firmware_version' => 'leaf-test-stream',
            'signal_strength' => -55 - mt_rand(0, 20),
            'battery_voltage' => round(3.7 + (mt_rand(0, 50) / 100), 2),
            'payload_version' => 1,
        ];
    }

    // ── Browser instructions ─────────────────────────────────────────

    private function printBrowserInstructions(): void
    {
        $this->newLine();
        $this->line('<bg=green;fg=white>  BROWSER VERIFICATION  </>');
        $this->line(str_repeat('─', 60));
        $this->newLine();

        $appUrl = config('app.url', 'http://localhost');
        $reverbHost = env('VITE_REVERB_HOST') ?: env('REVERB_HOST', '127.0.0.1');
        $reverbPort = env('VITE_REVERB_PORT') ?: env('REVERB_PORT', 8080);
        $reverbScheme = env('VITE_REVERB_SCHEME') ?: env('REVERB_SCHEME', 'http');

        $wsUrl = $reverbScheme . '://' . $reverbHost . ':' . $reverbPort;

        $this->line("  1. Open the dashboard:");
        $this->line("     <info>{$appUrl}/dashboard</info>");
        $this->newLine();

        $this->line("  2. Open browser DevTools → Console tab.");
        $this->newLine();

        $this->line("  3. Look for these log messages:");
        $this->bullet("     [Realtime] Echo connecting ...");
        $this->bullet("     [Realtime] Echo connected ...");
        $this->bullet("     [Realtime] subscribing to devices.{id}.telemetry");
        $this->bullet("     [Realtime] subscribed to devices.{id}.telemetry");
        $this->newLine();

        $this->line("  4. Verify Reverb WebSocket connection:");
        $this->line("     DevTools → Network → WS filter → look for:");
        $this->bullet("     URL: {$wsUrl}/app/{REVERB_APP_KEY}");
        $this->bullet("     Status: 101 Switching Protocols");
        $this->bullet("     (If 403 → auth issue; If 404 → Reverb not running)");
        $this->newLine();

        $this->line("  5. Verify live data updates:");
        $this->bullet("     KPI cards should pulse/flash on new readings");
        $this->bullet("     Charts should append new data points");
        $this->bullet("     'Last Updated' trend text should show 'just now'");
        $this->newLine();

        $this->line("  6. Quick Reverb health check from terminal:");
        $this->line("     <info>curl -s -o /dev/null -w '%{http_code}' {$reverbScheme}://{$reverbHost}:{$reverbPort}</info>");
        $this->line("     Expected: 404 (normal — Reverb only serves WebSocket upgrades)");
        $this->newLine();

        $this->line(str_repeat('─', 60));
    }

    private function bullet(string $text): void
    {
        $this->line("     • {$text}");
    }
}
