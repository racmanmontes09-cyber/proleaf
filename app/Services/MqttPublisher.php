<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceCommand;
use Illuminate\Support\Facades\Log;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class MqttPublisher
{
    private static ?MqttClient $client = null;

    /**
     * @var array<int, array{topic:string,payload:string,qos:int,retain:bool}>|null
     */
    private static ?array $fakePublishedMessages = null;

    /**
     * Publish a command to a device's MQTT command topic.
     */
    public static function publishCommand(Device $device, DeviceCommand $command): bool
    {
        try {
            $payload = json_encode([
                'command_id' => $command->id,
                'command' => $command->command,
                'enabled' => true,
                'title' => $command->title,
            ], JSON_THROW_ON_ERROR);

            return self::publishToDeviceTopic(
                $device,
                'commands',
                'leaf/devices/{device_id}/commands',
                $payload,
                retain: false,
                successMessage: 'MQTT command published',
                context: ['command' => $command->command, 'command_id' => $command->id]
            );
        } catch (\Throwable $e) {
            Log::error('MQTT command payload error: '.$e->getMessage(), [
                'device_id' => $device->device_id,
                'command_id' => $command->id,
            ]);

            return false;
        }
    }

    /**
     * Publish the retained runtime configuration to a device's settings topic.
     */
    public static function publishDeviceConfiguration(Device $device, string $payload): bool
    {
        return self::publishMessage(
            self::deviceSettingsTopic($device),
            $payload,
            retain: true,
            successMessage: 'MQTT runtime configuration published',
            context: ['device_id' => $device->device_id, 'topic' => self::deviceSettingsTopic($device)]
        );
    }

    public static function deviceSettingsTopic(Device $device): string
    {
        return self::topicForDevice($device, 'settings', 'leaf/devices/{device_id}/settings');
    }

    /**
     * Enable an in-memory MQTT publisher for tests.
     */
    public static function fake(): void
    {
        self::$fakePublishedMessages = [];
    }

    /**
     * Disable the in-memory MQTT publisher.
     */
    public static function restore(): void
    {
        self::$fakePublishedMessages = null;
        self::disconnect();
    }

    /**
     * @return array<int, array{topic:string,payload:string,qos:int,retain:bool}>
     */
    public static function publishedMessages(): array
    {
        return self::$fakePublishedMessages ?? [];
    }

    /**
     * Publish to a configured per-device topic.
     *
     * @param  array<string, mixed>  $context
     */
    private static function publishToDeviceTopic(
        Device $device,
        string $topicKey,
        string $fallbackTopic,
        string $payload,
        bool $retain,
        string $successMessage,
        array $context = []
    ): bool {
        $topic = self::topicForDevice($device, $topicKey, $fallbackTopic);

        return self::publishMessage($topic, $payload, $retain, $successMessage, array_merge([
            'device_id' => $device->device_id,
            'topic' => $topic,
        ], $context));
    }

    private static function topicForDevice(Device $device, string $topicKey, string $fallbackTopic): string
    {
        $topicTemplate = config("leaf.mqtt.topics.{$topicKey}", $fallbackTopic);

        return str_replace('{device_id}', $device->device_id, $topicTemplate);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private static function publishMessage(string $topic, string $payload, bool $retain, string $successMessage, array $context = []): bool
    {
        $qos = (int) config('leaf.mqtt.qos', 0);

        if (self::$fakePublishedMessages !== null) {
            self::$fakePublishedMessages[] = [
                'topic' => $topic,
                'payload' => $payload,
                'qos' => $qos,
                'retain' => $retain,
            ];

            return true;
        }

        try {
            $client = self::getClient();
            if ($client === null) {
                Log::error('MQTT publish skipped: no client connection', array_merge($context, [
                    'topic' => $topic,
                    'retain' => $retain,
                    'qos' => $qos,
                    'payload_length' => strlen($payload),
                ]));

                return false;
            }

            $client->publish($topic, $payload, $qos, $retain);

            Log::debug($successMessage, array_merge($context, [
                'retain' => $retain,
                'qos' => $qos,
                'payload_length' => strlen($payload),
            ]));

            return true;
        } catch (\Throwable $e) {
            Log::error('MQTT publish error: '.$e->getMessage(), array_merge($context, [
                'retain' => $retain,
                'qos' => $qos,
            ]));

            return false;
        }
    }

    /**
     * Get or create an MQTT client connection for publishing.
     */
    private static function getClient(): ?MqttClient
    {
        if (self::$client !== null && self::$client->isConnected()) {
            return self::$client;
        }

        $host = config('leaf.mqtt.host', '127.0.0.1');
        $port = (int) config('leaf.mqtt.port', 1883);
        $clientId = 'leaf-web-publisher-'.getmypid();

        try {
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
            self::$client = $client;

            return $client;
        } catch (\Throwable $e) {
            Log::error('MQTT connection failed for publish: '.$e->getMessage(), [
                'host' => $host,
                'port' => $port,
                'client_id' => $clientId,
                'exception' => $e,
            ]);

            return null;
        }
    }

    /**
     * Disconnect the MQTT client. Call on shutdown or when done publishing.
     */
    public static function disconnect(): void
    {
        if (self::$client !== null && self::$client->isConnected()) {
            try {
                self::$client->disconnect();
            } catch (\Throwable) {
                // Ignore disconnect errors
            }
            self::$client = null;
        }
    }
}
