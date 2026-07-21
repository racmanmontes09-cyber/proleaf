<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';


use App\Livewire\Dashboard\DeviceStatus;

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', DeviceStatus::class)
        ->name('dashboard');
});