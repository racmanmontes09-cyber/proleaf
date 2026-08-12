<?php

namespace App\Events;

use App\Models\Telemetry;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TelemetryReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Telemetry $telemetry;

    /**
     * Create a new event instance.
     */
    public function __construct(Telemetry $telemetry)
    {
        $this->telemetry = $telemetry;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('devices.'.$this->telemetry->device_id.'.telemetry'),
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
        $measuredAt = $this->telemetry->measured_at;
        if ($measuredAt instanceof \DateTimeInterface) {
            $measuredAt = $measuredAt->toIso8601String();
        }

        return [
            'id' => (int) $this->telemetry->id,
            'device_id' => $this->telemetry->device_id,
            'air_temperature' => $this->telemetry->air_temperature !== null ? (float) $this->telemetry->air_temperature : null,
            'humidity' => $this->telemetry->humidity !== null ? (float) $this->telemetry->humidity : null,
            'water_temperature' => $this->telemetry->water_temperature !== null ? (float) $this->telemetry->water_temperature : null,
            'ph' => $this->telemetry->ph !== null ? (float) $this->telemetry->ph : null,
            'ec' => $this->telemetry->ec !== null ? (float) $this->telemetry->ec : null,
            'water_flow' => $this->telemetry->water_flow !== null ? (float) $this->telemetry->water_flow : null,
            'water_level' => $this->telemetry->water_level !== null ? (float) $this->telemetry->water_level : null,
            'measured_at' => $measuredAt,
        ];
    }
}
