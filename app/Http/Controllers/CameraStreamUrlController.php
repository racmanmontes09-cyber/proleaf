<?php

namespace App\Http\Controllers;

use App\Models\Device;
use Illuminate\Http\JsonResponse;

class CameraStreamUrlController extends Controller
{
    /**
     * Resolve the relay (tunnel) URLs for the most recently seen camera.
     * The camera holds an outbound connection to the VPS relay, so the
     * browser connects through the relay instead of to the camera's LAN
     * address. A short-lived signed token scopes each stream/audio request
     * to the camera and the logged-in user.
     */
    public function __invoke(): JsonResponse
    {
        $camera = Device::query()
            ->where('type', Device::TYPE_CAMERA)
            ->where('status', Device::STATUS_ACTIVE)
            ->orderByDesc('last_seen_at')
            ->first();

        $relayUrl = rtrim((string) config('leaf.camera.relay_url'), '/');
        $token = $camera ? $this->relayToken($camera) : null;

        return response()->json([
            'success' => true,
            'available' => $camera !== null,
            'online' => $camera?->is_online ?? false,
            'device_id' => $camera?->device_id,
            'name' => $camera?->name,
            'last_seen_at' => $camera?->last_seen_at?->toIso8601String(),
            'url' => $camera ? $relayUrl.'/camera/stream?token='.$token : null,
            'audio_url' => $camera ? $relayUrl.'/camera/audio?token='.$token : null,
        ])->header('Cache-Control', 'no-store, max-age=0');
    }

    /**
     * JWT spec base64url: URL-safe alphabet (- and _), no padding (= removed).
     * The Go relay decodes with base64.RawURLEncoding, so standard base64
     * (+ / =) would be rejected.
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * HMAC-SHA256 signed token validated by the relay without a DB lookup.
     */
    private function relayToken(Device $camera): string
    {
        $secret = (string) config('leaf.camera.relay_jwt_secret');
        if ($secret === '') {
            return '';
        }

        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $now = now()->timestamp;
        $payload = $this->base64UrlEncode(json_encode([
            'user_id' => auth()->id(),
            'device_id' => $camera->device_id,
            'iat' => $now,
            'exp' => $now + (int) config('leaf.camera.relay_token_ttl', 300),
        ]));

        $signature = $this->base64UrlEncode(hash_hmac('sha256', $header.'.'.$payload, $secret, true));

        return $header.'.'.$payload.'.'.$signature;
    }
}
