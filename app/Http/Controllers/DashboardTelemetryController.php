<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Services\TelemetryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;

class DashboardTelemetryController extends Controller
{
    public function __invoke(Request $request, TelemetryService $telemetryService): JsonResponse
    {
        $maxLimit = $this->configuredTelemetryLimitMax();

        $validated = $request->validate([
            'device_id' => ['nullable', 'integer', 'min:1'],
            'after_id' => ['nullable', 'integer', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:'.$maxLimit],
            'from' => ['nullable', 'date_format:Y-m-d\\TH:i'],
            'to' => ['nullable', 'date_format:Y-m-d\\TH:i', 'after_or_equal:from'],
        ]);

        $from = isset($validated['from']) ? Carbon::createFromFormat('Y-m-d\\TH:i', $validated['from']) : null;
        $to = isset($validated['to']) ? Carbon::createFromFormat('Y-m-d\\TH:i', $validated['to']) : null;

        $device = $this->resolveDevice($validated['device_id'] ?? null);

        if ($device === null) {
            return response()->json([
                'success' => true,
                'device_id' => null,
                'after_id' => (int) ($validated['after_id'] ?? 0),
                'latest_id' => (int) ($validated['after_id'] ?? 0),
                'latest_kpis' => null,
                'readings' => [],
            ])->header('Cache-Control', 'no-store, max-age=0');
        }

        $afterId = (int) ($validated['after_id'] ?? 0);
        $configuredMaxPoints = (int) config('leaf.dashboard.live_chart.max_points', 720);
        $configuredBatchLimit = (int) config('leaf.dashboard.live_chart.poll_batch_limit', 120);
        $limit = (int) ($validated['limit'] ?? ($afterId > 0 ? $configuredBatchLimit : $configuredMaxPoints));
        $limit = max(1, min($limit, $maxLimit));

        $readings = $this->getTelemetryReadings($device, $telemetryService, $afterId, $limit, $from, $to);

        $latestReading = $readings->last();

        return response()->json([
            'success' => true,
            'device_id' => (int) $device->id,
            'after_id' => $afterId,
            'latest_id' => (int) ($readings->max('id') ?? $afterId),
            'latest_kpis' => $latestReading ? $telemetryService->serializeTelemetryKpis($latestReading) : null,
            'readings' => $telemetryService->serializeTelemetryReadings($readings),
        ])->header('Cache-Control', 'no-store, max-age=0');
    }

    private function configuredTelemetryLimitMax(): int
    {
        return max(
            1,
            (int) config('leaf.dashboard.live_chart.max_points', 720),
            (int) config('leaf.dashboard.live_chart.buffer_points', 1000),
            (int) config('leaf.dashboard.live_chart.poll_batch_limit', 120),
        );
    }

    private function resolveDevice(?int $deviceId): ?Device
    {
        if ($deviceId !== null) {
            $device = Device::query()->find($deviceId);

            abort_if($device === null, 404);

            return $device;
        }

        $configuredDeviceId = (int) config('leaf.dashboard.device_db_id', 358);
        if ($configuredDeviceId > 0) {
            $configuredDevice = Device::query()->find($configuredDeviceId);

            if ($configuredDevice instanceof Device) {
                return $configuredDevice;
            }
        }

        return Device::query()->latest('last_seen_at')->first();
    }

    private function getTelemetryReadings(
        Device $device,
        TelemetryService $telemetryService,
        int $afterId,
        int $limit,
        ?Carbon $from = null,
        ?Carbon $to = null
    ): Collection {
        if ($from !== null || $to !== null) {
            return $telemetryService->getTelemetryHistoryBetween($device, $from, $to, $limit);
        }

        return $afterId > 0
            ? $telemetryService->getTelemetryAfterId($device, $afterId, $limit)
            : $telemetryService->getTelemetryHistory($device, $limit);
    }
}
