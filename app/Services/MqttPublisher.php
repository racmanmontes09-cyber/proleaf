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
     * Publish a command to a device's MQTT command topic.
     */
    public static function publishCommand(Device $device, DeviceCommand $command): bool
    {
        try {
            $client = self::getClient();
            if ($client === null) {
                return false;
            }

            $topicTemplate = config('leaf.mqtt.topics.commands', 'leaf/devices/{device_id}/commands');
            $topic = str_replace('{device_id}', $device->device_id, $topicTemplate);

            $payload = json_encode([
                'command_id' => $command->id,
                'command' => $command->command,
                'enabled' => true,
                'title' => $command->title,
            ], JSON_THROW_ON_ERROR);

            $qos = (int) config('leaf.mqtt.qos', 0);
            $result = $client->publish($topic, $payload, $qos);

            if ($result) {
                Log::info('MQTT command published', [
                    'device_id' => $device->device_id,
                    'topic' => $topic,
                    'command' => $command->command,
                ]);
            } else {
                Log::warning('MQTT command publish failed', [
                    'device_id' => $device->device_id,
                    'topic' => $topic,
                ]);
            }

            return $result;
        } catch (\Throwable $e) {
            Log::error('MQTT publish error: '.$e->getMessage(), [
                'device_id' => $device->device_id,
                'command_id' => $command->id,
            ]);

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
            Log::warning('MQTT connection failed for publish: '.$e->getMessage());

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
