<?php

namespace App\Events;

use App\Models\Telemetry;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Carbon;

class TelemetryReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public int $deviceId;

    /**
     * @var array<string, mixed>
     */
    public array $payload;

    /**
     * Create a new event instance.
     */
    public function __construct(Telemetry $telemetry)
    {
        $this->deviceId = (int) $telemetry->device_id;

        $measuredAt = $telemetry->measured_at ?? $telemetry->updated_at ?? $telemetry->created_at;

        $sequenceNumber = $telemetry->sequence_number;

        $this->payload = [
            'id' => (int) $telemetry->id,
            'device_id' => $this->deviceId,
            'timestamp' => $this->serializeTimestamp($measuredAt),
            'measured_at' => $this->serializeTimestamp($telemetry->measured_at),
            'received_at' => $this->serializeTimestamp($telemetry->received_at),
            'air_temperature' => $telemetry->air_temperature !== null ? (float) $telemetry->air_temperature : null,
            'humidity' => $telemetry->humidity !== null ? (float) $telemetry->humidity : null,
            'water_temperature' => $telemetry->water_temperature !== null ? (float) $telemetry->water_temperature : null,
            'ph' => $telemetry->ph !== null ? (float) $telemetry->ph : null,
            'ec' => $telemetry->ec !== null ? (float) $telemetry->ec : null,
            'water_flow' => $telemetry->water_flow !== null ? (float) $telemetry->water_flow : null,
            'water_level' => $telemetry->water_level !== null ? (float) $telemetry->water_level : null,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('devices.'.$this->deviceId.'.telemetry'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'TelemetryReceived';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->payload;
    }

    private function serializeTimestamp(mixed $timestamp): ?string
    {
        if ($timestamp === null || $timestamp === '') {
            return null;
        }

        $carbon = $timestamp instanceof \DateTimeInterface
            ? Carbon::instance($timestamp)
            : Carbon::parse($timestamp);

        return $carbon->utc()->format('Y-m-d\TH:i:s.u\Z');
    }
}
