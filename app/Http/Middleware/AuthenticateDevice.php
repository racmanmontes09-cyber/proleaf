<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! is_string($token) || $token === '') {
            return response()->json([
                'success' => false,
                'message' => 'Missing device token.',
            ], 401);
        }

        $device = Device::findForDeviceToken($token);

        if (! $device) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid device token.',
            ], 401);
        }

        if ($device->hasExpiredDeviceToken()) {
            return response()->json([
                'success' => false,
                'message' => 'Device token expired.',
            ], 401);
        }

        if ($device->hasRevokedDeviceToken()) {
            return response()->json([
                'success' => false,
                'message' => 'Device token revoked.',
            ], 403);
        }

        if (! $device->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Device disabled.',
            ], 403);
        }

        $request->attributes->set('device', $device);

        $response = $next($request);

        if ($response->getStatusCode() < 400 && ! $request->is('api/devices/telemetry')) {
            $device->forceFill([
                'device_token_last_used_at' => now(),
            ])->save();
        }

        return $response;
    }
}
