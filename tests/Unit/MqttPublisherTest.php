<?php

namespace Tests\Unit;

use App\Models\Device;
use App\Services\MqttPublisher;
use Mockery;
use PhpMqtt\Client\MqttClient;
use Tests\TestCase;

class MqttPublisherTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->setClient(null);
        Mockery::close();

        parent::tearDown();
    }

    public function test_device_configuration_publish_succeeds_when_mqtt_client_publish_returns_void(): void
    {
        config([
            'leaf.mqtt.qos' => 0,
            'leaf.mqtt.topics.settings' => 'leaf/devices/{device_id}/settings',
        ]);

        $client = Mockery::mock(MqttClient::class);
        $client->shouldReceive('isConnected')->andReturnTrue();
        $client->shouldReceive('publish')
            ->once()
            ->with('leaf/devices/ESP-PUBLISH-01/settings', '{"ok":true}', 0, true)
            ->andReturnUsing(function (): void {});

        $this->setClient($client);

        $device = new Device(['device_id' => 'ESP-PUBLISH-01']);

        $this->assertTrue(MqttPublisher::publishDeviceConfiguration($device, '{"ok":true}'));
    }

    private function setClient(?MqttClient $client): void
    {
        $property = new \ReflectionProperty(MqttPublisher::class, 'client');
        $property->setAccessible(true);
        $property->setValue(null, $client);
    }
}
