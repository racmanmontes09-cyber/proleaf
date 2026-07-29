<?php

namespace Database\Factories;

use App\Models\Telemetry;
use Illuminate\Database\Eloquent\Factories\Factory;

class TelemetryFactory extends Factory
{
    protected $model = Telemetry::class;

    public function definition(): array
    {
        return [
            'air_temperature' => $this->faker->randomFloat(1, 24, 28),
            'humidity' => $this->faker->randomFloat(1, 60, 75),
            'water_temperature' => $this->faker->randomFloat(1, 20, 24),
            'ph' => $this->faker->randomFloat(2, 5.8, 6.3),
            'ec' => $this->faker->randomFloat(1, 1.2, 1.8),
            'water_flow' => $this->faker->randomFloat(1, 1.0, 2.0),
            'water_level' => $this->faker->randomFloat(0, 85, 100),
            'measured_at' => now()->subMinutes($this->faker->numberBetween(0, 1440)),
            'received_at' => now(),
            'sequence_number' => $this->faker->numberBetween(1000, 5000),
            'firmware_version' => 'v1.2.3',
            'signal_strength' => $this->faker->numberBetween(-80, -50),
            'battery_voltage' => $this->faker->randomFloat(2, 3.8, 4.2),
            'payload_version' => 1,
        ];
    }
}
