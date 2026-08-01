<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TelemetryStoreRequest;
use App\Models\Device;
use App\Services\TelemetryService;
use Illuminate\Http\JsonResponse;

class TelemetryController extends Controller
{
    public function __construct(
        protected TelemetryService $telemetryService
    ) {}

    /**
     * Store telemetry from the ESP32.
     */
    public function store(TelemetryStoreRequest $request): JsonResponse
    {
        /** @var Device $device */
        $device = $request->attributes->get('device');

        $result = $this->telemetryService->storeTelemetry(
            $device,
            $request->telemetryPayload(),
            app()->environment('testing') || app()->runningUnitTests(),
        );

        $status = $result['created'] ? 201 : 200;

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
        ], $status);
    }
}
