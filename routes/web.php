<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\LockController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AlertController;

Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected admin routes
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Building management
    Route::resource('buildings', BuildingController::class);

    // Apartment management
    Route::resource('apartments', ApartmentController::class);

    // Lock management
    Route::resource('locks', LockController::class);
    Route::get('/locks/{lock}/status', [LockController::class, 'status'])->name('locks.status');
    Route::get('/locks/{lock}/logs', [LockController::class, 'logs'])->name('locks.logs');

    // Lock Codes
    Route::get('/locks/{lock}/codes', [LockController::class, 'codes'])->name('locks.codes');
    Route::post('/locks/{lock}/codes', [LockController::class, 'storeCode'])->name('locks.codes.store');
    Route::put('/locks/{lock}/codes/{code}', [LockController::class, 'updateCode'])->name('locks.codes.update');
    Route::delete('/locks/{lock}/codes/{code}', [LockController::class, 'destroyCode'])->name('locks.codes.destroy');
    Route::post('/locks/{lock}/codes/{code}/early', [LockController::class, 'early'])->name('locks.codes.early');
    Route::post('/locks/{lock}/codes/{code}/late', [LockController::class, 'late'])->name('locks.codes.late');

    // Alerts
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    // Playground
    Route::get('/playground', [App\Http\Controllers\PlaygroundController::class, 'index'])->name('playground.index');
    Route::post('/playground', [App\Http\Controllers\PlaygroundController::class, 'run'])->name('playground.run');
});
