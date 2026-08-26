<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\JsonResponse;

class CameraStreamUrlController extends Controller
{
    /**
     * Resolve the live MJPEG URL of the most recently seen camera so the
     * dashboard can connect directly to the device on the local network.
     */
    public function __invoke(): JsonResponse
    {
        $camera = Device::query()
            ->where('type', Device::TYPE_CAMERA)
            ->where('status', Device::STATUS_ACTIVE)
            ->whereNotNull('local_ip_address')
            ->orderByDesc('last_seen_at')
            ->first();

        $port = (int) config('leaf.camera.stream_port', 80);
        $audioPort = (int) config('leaf.camera.audio_port', 82);

        return response()->json([
            'success' => true,
            'available' => $camera !== null,
            'online' => $camera?->is_online ?? false,
            'device_id' => $camera?->device_id,
            'name' => $camera?->name,
            'last_seen_at' => $camera?->last_seen_at?->toIso8601String(),
            'url' => $camera ? sprintf(
                '%s://%s%s%s',
                config('leaf.camera.stream_scheme', 'http'),
                $camera->local_ip_address,
                $port === 80 ? '' : ':'.$port,
                config('leaf.camera.stream_path', '/stream'),
            ) : null,
            'audio_url' => $camera ? sprintf(
                '%s://%s%s%s',
                config('leaf.camera.stream_scheme', 'http'),
                $camera->local_ip_address,
                $audioPort === 80 ? '' : ':'.$audioPort,
                config('leaf.camera.audio_path', '/audio'),
            ) : null,
        ])->header('Cache-Control', 'no-store, max-age=0');
    }
}
