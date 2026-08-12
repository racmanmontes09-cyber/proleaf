<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardTelemetryController;
use App\Livewire\Dashboard\DeviceStatus;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DeviceStatus::class)
        ->name('dashboard');

    Route::get('/dashboard/esp32/status', function () {
        $latestDevice = App\Models\Device::query()->latest('last_seen_at')->first();
        $isOnline = (bool) ($latestDevice?->is_online ?? false);

        return response()->json([
            'online' => $isOnline,
            'statusLabel' => $latestDevice ? ($isOnline ? 'ESP32 Online' : 'ESP32 Offline') : 'ESP32 Offline',
        ]);
    })->name('dashboard.esp32.status');

    Route::get('/dashboard/telemetry/readings', DashboardTelemetryController::class)
        ->name('dashboard.telemetry.readings');

});

require __DIR__.'/auth.php';
