<?php

use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\DeviceCommandController;
use App\Http\Controllers\Api\TelemetryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['auth:sanctum', 'active']);

Route::post('/devices/heartbeat', [DeviceController::class, 'heartbeat'])
    ->middleware(['device.auth', 'throttle:device-heartbeat']);
Route::post('/devices/telemetry', [TelemetryController::class, 'store'])
    ->middleware(['device.auth', 'throttle:device-telemetry']);
Route::get('/devices/commands', [DeviceCommandController::class, 'index'])
    ->middleware(['device.auth', 'throttle:device-commands']);
Route::patch('/devices/commands/{commandId}', [DeviceCommandController::class, 'update'])
    ->middleware(['device.auth', 'throttle:device-commands']);
Route::get('/device/settings', [\App\Http\Controllers\Api\SystemSettingsController::class, 'index'])
    ->middleware(['device.auth', 'throttle:device-commands']);
