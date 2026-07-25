<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\ThresholdService;
use Illuminate\Http\JsonResponse;

class SystemSettingsController extends Controller
{
    public function __construct(
        protected ThresholdService $thresholdService
    ) {}

    public function index(): JsonResponse
    {
        $t = $this->thresholdService->getAllThresholds();

        return response()->json([
            'temperature' => [
                'min' => $t['temperatureLow'],
                'max' => $t['temperatureHigh'],
            ],
            'humidity' => [
                'min' => $t['humidityLow'],
                'max' => $t['humidityHigh'],
            ],
            'water_temperature' => [
                'min' => $t['waterTemperatureLow'],
                'max' => $t['waterTemperatureHigh'],
            ],
            'ph' => [
                'min' => $t['phLow'],
                'max' => $t['phHigh'],
            ],
            'ec' => [
                'min' => $t['ecLow'],
                'max' => $t['ecHigh'],
            ],
            'water_flow' => [
                'min' => $t['waterFlowLow'],
                'max' => $t['waterFlowHigh'],
            ],
            'water_level' => [
                'min' => $t['waterLevelLow'],
                'max' => $t['waterLevelHigh'],
            ],
            'heartbeat_interval' => (int) SystemSetting::getValue('heartbeat_interval', 30),
            'upload_interval' => (int) SystemSetting::getValue('sensor_upload_interval', 5),
        ]);
    }
}
