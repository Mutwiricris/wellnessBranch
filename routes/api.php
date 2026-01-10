<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MpesaWebhookController;
use App\Http\Controllers\AvailabilityController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Availability API routes
Route::prefix('availability')->group(function () {
    Route::get('dates', [AvailabilityController::class, 'getDates']);
    Route::get('time-slots', [AvailabilityController::class, 'getTimeSlots']);
    Route::get('staff', [AvailabilityController::class, 'getStaff']);
    Route::post('check-time-slot', [AvailabilityController::class, 'checkTimeSlot']);
});

// M-Pesa Webhook routes (no authentication required - called by M-Pesa)
Route::prefix('mpesa')->group(function () {
    Route::post('callback', [MpesaWebhookController::class, 'handleCallback']);
    Route::post('validation', [MpesaWebhookController::class, 'handleValidation']);
    Route::post('confirmation', [MpesaWebhookController::class, 'handleConfirmation']);
});