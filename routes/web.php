<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\Dashboard\DeviceStatus;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DeviceStatus::class)
        ->name('dashboard');
});

require __DIR__.'/auth.php';