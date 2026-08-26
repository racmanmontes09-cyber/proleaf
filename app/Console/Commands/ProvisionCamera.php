<?php

namespace App\Console\Commands;

use App\Models\Device;
use Illuminate\Console\Command;

class ProvisionCamera extends Command
{
    protected $signature = 'leaf:provision-camera
        {device_id : Unique device identifier, e.g. esp32-cam-001}
        {--name= : Friendly display name shown in the dashboard}';

    protected $description = 'Create or update a camera device and print a fresh device token for the firmware';

    public function handle(): int
    {
        $deviceId = trim((string) $this->argument('device_id'));

        if ($deviceId === '') {
            $this->error('Device id must not be empty.');

            return self::FAILURE;
        }

        $existing = Device::query()->where('device_id', $deviceId)->first();

        if ($existing !== null && $existing->type !== Device::TYPE_CAMERA) {
            $this->error("Device [{$deviceId}] already exists as type [{$existing->type}]. Choose another device id.");

            return self::FAILURE;
        }

        $device = $existing ?? new Device(['device_id' => $deviceId]);
        $device->type = Device::TYPE_CAMERA;
        $device->status = Device::STATUS_ACTIVE;
        $device->name = (string) ($this->option('name') ?: $device->name ?: $deviceId);
        $device->save();

        $token = $device->issueDeviceToken();

        $this->info("Camera [{$deviceId}] provisioned as \"{$device->name}\" (id #{$device->id}).");
        $this->warn('Store this token now — it is shown only once:');
        $this->line($token);
        $this->newLine();
        $this->line('Flash the camera with LEAF_CAMERA_DEVICE_TOKEN set to this value.');

        return self::SUCCESS;
    }
}
