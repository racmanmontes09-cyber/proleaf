<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\Dashboard\DeviceStatus;

Route::view('/', 'welcome');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DeviceStatus::class)
        ->name('dashboard');

    Route::view('/profile', 'profile')
        ->name('profile');
});

require __DIR__.'/auth.php';