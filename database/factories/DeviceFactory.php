<?php

namespace Database\Factories;

use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DeviceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Device::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'device_id' => 'LEAF-ESP32-'.Str::upper($this->faker->bothify('??-###')),
            'name' => 'ESP32 Node '.$this->faker->numberBetween(1, 99),
            'firmware_version' => 'v1.2.3',
            'local_ip_address' => $this->faker->ipv4,
            'wifi_rssi' => $this->faker->numberBetween(-75, -45),
            'uptime_seconds' => $this->faker->numberBetween(3600, 86400),
            'free_heap' => $this->faker->numberBetween(50000, 200000),
            'last_boot_at' => now()->subMinutes($this->faker->numberBetween(10, 720)),
            'last_seen_at' => now(),
        ];
    }
}
