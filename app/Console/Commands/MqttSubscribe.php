<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Services\TelemetryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class MqttSubscribe extends Command
{
    protected $signature = 'mqtt:subscribe {--host= : MQTT host} {--port=1883 : MQTT port} {--topic= : MQTT topic (supports +/#)} {--clientId= : MQTT client id}';

    protected $description = 'Subscribe to an MQTT broker and forward telemetry to the TelemetryService and Broadcast events.';

    /**
     * @var array<string, int>
     */
    protected array $lastLogAt = [];

    public function __construct(protected TelemetryService $telemetryService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $host = $this->option('host') ?: config('leaf.mqtt.host', '127.0.0.1');
        $port = (int) ($this->option('port') ?: config('leaf.mqtt.port', 1883));
        $topic = $this->option('topic') ?: config('leaf.mqtt.topics.telemetry', 'leaf/devices/+/telemetry');
        $statusTopic = config('leaf.mqtt.topics.status', 'leaf/devices/+/status');
        $clientId = $this->option('clientId') ?: config('leaf.mqtt.client_id', 'leaf-mqtt-subscriber-'.uniqid());
        $qos = (int) config('leaf.mqtt.qos', 0);

        $this->info("Connecting to MQTT broker {$host}:{$port} topic={$topic}");

        $backoff = 1;

        while (true) {
            try {
                $this->info('Starting MQTT client...');

                $client = new MqttClient($host, $port, $clientId);
                $settings = (new ConnectionSettings())
                    ->setUseTls((bool) config('leaf.mqtt.use_tls', false))
                    ->setKeepAliveInterval(10);

                $username = config('leaf.mqtt.username');
                $password = config('leaf.mqtt.password');
                if (is_string($username) && $username !== '') {
                    $settings->setUsername($username);
                    $settings->setPassword(is_string($password) ? $password : null);
                }

                $client->connect($settings, true);

                $this->info('Connected to MQTT broker. Subscribing...');

                $client->subscribe($topic, function (string $topic, string $message, bool $retained, array $matched) {
                    $this->handleTelemetryMessage($topic, $message, $retained, $matched);
                }, $qos);
                $client->subscribe($statusTopic, function (string $topic, string $message, bool $retained, array $matched) {
                    $this->handleStatusMessage($topic, $message, $retained, $matched);
                }, $qos);

                // Process network loop (blocking)
                $client->loop(true);

                // If loop exits normally, break
                $this->info('MQTT client loop exited, reconnecting...');
                $backoff = 1;
            } catch (\Throwable $e) {
                $this->logThrottled('error', 'subscriber', 'MQTT subscriber error: '.$e->getMessage(), [
                    'host' => $host,
                    'port' => $port,
                    'topic' => $topic,
                ], 30);
                $this->error('MQTT subscriber error: '.$e->getMessage());
                sleep(min(60, $backoff));
                $backoff = max(1, $backoff * 2);
                continue;
            }
        }

        return self::SUCCESS;
    }

    /**
     * Handle one MQTT telemetry message. Public so it can be tested without a live broker loop.
     *
     * @param  array<int|string, mixed>  $matched
     */
    public function handleTelemetryMessage(string $topic, string $message, bool $retained = false, array $matched = []): void
    {
        try {
            $payload = json_decode($message, true);
            if (! is_array($payload)) {
                $this->logThrottled('warning', 'invalid-json:'.$topic, 'MQTT telemetry ignored: invalid JSON payload.', [
                    'topic' => $topic,
                    'retained' => $retained,
                ]);

                return;
            }

            $deviceIdentifier = $this->extractDeviceIdentifier($topic, $payload);
            if ($deviceIdentifier === null) {
                $this->logThrottled('warning', 'missing-device:'.$topic, 'MQTT telemetry ignored: missing device identifier.', [
                    'topic' => $topic,
                    'payload_keys' => array_keys($payload),
                ]);

                return;
            }

            $device = Device::query()->where('device_id', $deviceIdentifier)->first();
            if (! $device) {
                $this->logThrottled('warning', 'unknown-device:'.$deviceIdentifier, 'MQTT telemetry ignored: unknown device.', [
                    'topic' => $topic,
                    'device_id' => $deviceIdentifier,
                ]);

                return;
            }

            $this->telemetryService->storeTelemetry(
                $device,
                $payload,
                skipSideEffects: false,
                broadcastRealtime: true,
            );
        } catch (ValidationException $e) {
            $this->logThrottled('warning', 'validation:'.$topic, 'MQTT telemetry ignored: validation failed.', [
                'topic' => $topic,
                'errors' => $e->errors(),
            ]);
        } catch (\Throwable $e) {
            $this->logThrottled('error', 'handler:'.$topic, 'MQTT telemetry handler error: '.$e->getMessage(), [
                'topic' => $topic,
                'exception' => $e,
            ], 15);
        }
    }

    /**
     * Handle device status messages so the dashboard online indicator tracks MQTT heartbeats.
     *
     * @param  array<int|string, mixed>  $matched
     */
    public function handleStatusMessage(string $topic, string $message, bool $retained = false, array $matched = []): void
    {
        $payload = json_decode($message, true);
        if (! is_array($payload) || ($payload['status'] ?? null) !== 'online') {
            return;
        }

        $deviceIdentifier = $this->extractDeviceIdentifier($topic, $payload);
        if ($deviceIdentifier === null) {
            return;
        }

        $updateData = ['last_seen_at' => now()];

        $actuators = $payload['actuators'] ?? null;
        if (is_array($actuators)) {
            $actuatorMap = [
                'cooling_fan' => 'actuator_cooling_fan',
                'nutrient_pump_a' => 'actuator_nutrient_pump_a',
                'nutrient_pump_b' => 'actuator_nutrient_pump_b',
                'ph_up_pump' => 'actuator_ph_up_pump',
                'ph_down_pump' => 'actuator_ph_down_pump',
            ];

            foreach ($actuatorMap as $jsonKey => $dbColumn) {
                if (array_key_exists($jsonKey, $actuators)) {
                    $updateData[$dbColumn] = (bool) $actuators[$jsonKey];
                }
            }

            $updateData['actuator_states_updated_at'] = now();

            $this->logThrottled('info', 'actuator-state:' . $deviceIdentifier, '[MQTT] Actuator state updated', [
                'device_id' => $deviceIdentifier,
                'actuators' => $actuators,
            ], 60);
        }

        Device::query()
            ->where('device_id', $deviceIdentifier)
            ->update($updateData);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function extractDeviceIdentifier(string $topic, array $payload): ?string
    {
        $deviceIdentifier = $payload['device_id'] ?? $payload['deviceId'] ?? null;
        if (is_string($deviceIdentifier) && $deviceIdentifier !== '') {
            return $deviceIdentifier;
        }

        if (is_numeric($deviceIdentifier)) {
            return (string) $deviceIdentifier;
        }

        $parts = explode('/', trim($topic, '/'));

        if (count($parts) >= 3 && $parts[0] === 'leaf' && $parts[1] === 'devices' && in_array($parts[3] ?? null, ['telemetry', 'status'], true) && $parts[2] !== '') {
            return $parts[2];
        }

        if (count($parts) >= 3 && $parts[0] === 'devices' && in_array($parts[2], ['telemetry', 'status'], true) && $parts[1] !== '') {
            return $parts[1];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logThrottled(string $level, string $key, string $message, array $context = [], int $seconds = 60): void
    {
        $now = time();
        if (($this->lastLogAt[$key] ?? 0) + $seconds > $now) {
            return;
        }

        $this->lastLogAt[$key] = $now;
        Log::log($level, $message, $context);
    }
}
