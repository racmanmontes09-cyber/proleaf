<?php

namespace App\Repositories;

use App\Models\Alert;
use App\Models\Device;
use App\Models\Telemetry;

class AlertRepository
{
    public function create(array $attributes): Alert
    {
        return Alert::create($attributes);
    }

    public function findActiveBySensor(Device $device, string $sensor, string $title): ?Alert
    {
        return Alert::query()
            ->forDevice($device)
            ->active()
            ->where('sensor', $sensor)
            ->where('title', $title)
            ->first();
    }

    public function activeAlertsForDevice(Device $device, int $limit = 50)
    {
        return Alert::query()
            ->forDevice($device)
            ->active()
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function activeAlertCountForDevice(Device $device): int
    {
        return Alert::query()
            ->forDevice($device)
            ->active()
            ->count();
    }

    public function historyForDevice(Device $device, int $limit = 100)
    {
        return Alert::query()
            ->forDevice($device)
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function latestAlerts(int $limit = 20)
    {
        return Alert::query()
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function resolve(Alert $alert): Alert
    {
        if ($alert->status === 'resolved') {
            return $alert;
        }

        $alert->status = 'resolved';
        $alert->resolved_at = now();
        $alert->save();

        return $alert;
    }

    public function acknowledge(Alert $alert, ?int $userId = null): Alert
    {
        if ($alert->status === 'acknowledged') {
            return $alert;
        }

        $alert->status = 'acknowledged';
        $alert->acknowledged_at = now();
        $alert->acknowledged_by = $userId;
        $alert->save();

        return $alert;
    }

    public function resolveOfflineAlerts(Device $device): void
    {
        Alert::query()
            ->forDevice($device)
            ->active()
            ->where('sensor', 'device_offline')
            ->get()
            ->each(fn (Alert $alert) => $this->resolve($alert));
    }

    public function findActiveOfflineAlert(Device $device): ?Alert
    {
        return Alert::query()
            ->forDevice($device)
            ->active()
            ->where('sensor', 'device_offline')
            ->first();
    }
}
