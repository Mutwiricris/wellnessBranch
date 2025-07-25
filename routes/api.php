<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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