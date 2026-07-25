<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\Dashboard\DeviceStatus;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DeviceStatus::class)
        ->name('dashboard');
    Route::view('/monitoring', 'monitoring')->name('monitoring');
    Route::view('/analytics', 'analytics')->name('analytics');
    Route::view('/device-management', 'device-management')->name('device-management');
    Route::view('/alerts-logs', 'alerts-logs')->name('alerts-logs');
    Route::view('/reports', 'reports')->name('reports');
    Route::view('/settings', 'settings')->name('settings');
    Route::get('/audit-logs', \App\Livewire\Audit\AuditLogList::class)
        ->name('audit-logs')
        ->middleware('permission:audit.view');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';