<?php

use App\Models\Device;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('devices.{deviceId}.telemetry', function ($user, int $deviceId) {
    return $user?->canPerform('telemetry.view') === true
        && Device::query()->whereKey($deviceId)->exists();
});
