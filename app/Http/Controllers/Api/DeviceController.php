<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeviceUpdateRequest;
use App\Models\Device;

class DeviceController extends Controller
{
    /**
     * Receive a heartbeat from the ESP32.
     */
    public function heartbeat(DeviceUpdateRequest $request)
    {
        $device = Device::updateOrCreate(
            [
                'device_id' => $request->device_id,
            ],
            [
                'name'             => $request->name,
                'firmware_version' => $request->firmware_version,
                'local_ip_address' => $request->local_ip_address,
                'wifi_rssi'        => $request->wifi_rssi,
                'uptime_seconds'   => $request->uptime_seconds,
                'free_heap'        => $request->free_heap,
                'last_boot_at'     => $request->last_boot_at,
                'last_seen_at'     => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Heartbeat received successfully.',
            'data' => [
                'device_id' => $device->device_id,
                'server_time' => now()->toIso8601String(),
            ],
        ]);
    }
}