<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeviceUpdateRequest;
use App\Models\Device;
use Illuminate\Http\JsonResponse;

class DeviceController extends Controller
{
    /**
     * Receive a heartbeat from the ESP32.
     */
    public function heartbeat(DeviceUpdateRequest $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $validated = $request->validated();
        $heartbeatFields = [
            'type',
            'name',
            'firmware_version',
            'local_ip_address',
            'wifi_rssi',
            'uptime_seconds',
            'free_heap',
            'last_boot_at',
        ];

        $updates = array_intersect_key($validated, array_flip($heartbeatFields));
        $now = now();
        $updates['last_seen_at'] = $now;
        $updates['device_token_last_used_at'] = $now;

        $device->forceFill($updates)->save();

        return response()->json([
            'success' => true,
            'message' => 'Heartbeat received successfully.',
            'data' => [
                'uuid' => $device->uuid,
                'device_id' => $device->device_id,
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }
}
