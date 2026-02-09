<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PinController;
use App\Http\Controllers\Api\LockController as ApiLockController;
use App\Http\Controllers\Api\AlertController as ApiAlertController;

/*
|--------------------------------------------------------------------------
| API Routes for CRM Integration
|--------------------------------------------------------------------------
|
| These endpoints are used by the external CRM to manage lock access
|
*/

Route::post('/bookings', [App\Http\Controllers\Api\BookingController::class, 'store']);
Route::put('/bookings/{id}', [App\Http\Controllers\Api\BookingController::class, 'update']);
Route::delete('/bookings/{id}', [App\Http\Controllers\Api\BookingController::class, 'destroy']);

Route::middleware('api')->group(function () {

    // PIN Management Endpoints
    Route::post('/pins', [PinController::class, 'store']);
    Route::patch('/pins/{id}', [PinController::class, 'update']);
    Route::delete('/pins/{id}', [PinController::class, 'destroy']);
    Route::get('/pins/{id}', [PinController::class, 'show']);

    // Lock Status and Logs
    Route::get('/locks/{id}/status', [ApiLockController::class, 'status']);
    Route::get('/locks/{id}/logs', [ApiLockController::class, 'logs']);

    // Alerts
    Route::get('/alerts', [ApiAlertController::class, 'index']);
    Route::get('/alerts/pending', [ApiAlertController::class, 'pending']);
});
