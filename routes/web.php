<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardTelemetryController;
use App\Http\Controllers\CameraStreamUrlController;
use App\Livewire\Dashboard\DeviceStatus;
use App\Livewire\Admin\ActivityLogs;
use App\Livewire\Admin\AdminDashboard;
use App\Livewire\Admin\Users;

Route::view('/', 'welcome');

Broadcast::routes(['middleware' => ['web']]);

Route::middleware(['auth', 'active', 'verified'])->group(function () {
    Route::get('/dashboard', DeviceStatus::class)
        ->name('dashboard');

    Route::middleware('can:admin-panel')->group(function () {
        Route::get('/admin', AdminDashboard::class)->name('admin.dashboard');
        Route::get('/admin/users', Users::class)->name('admin.users');
        Route::get('/admin/activity-logs', ActivityLogs::class)->name('admin.activity-logs');
    });

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

    Route::get('/dashboard/camera/stream-url', CameraStreamUrlController::class)
        ->name('dashboard.camera.stream-url');

});

require __DIR__.'/auth.php';
