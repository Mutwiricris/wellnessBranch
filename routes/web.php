<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\AvailabilityController;

Route::get('/', function () {
    return view('welcome');
});

// Public Booking Routes
Route::prefix('booking')->name('booking.')->group(function () {
    Route::get('/', [BookingController::class, 'index'])->name('index');
    Route::get('/branches', [BookingController::class, 'branches'])->name('branches');
    Route::post('/select-branch', [BookingController::class, 'selectBranch'])->name('select-branch');
    Route::get('/services', [BookingController::class, 'services'])->name('services');
    Route::post('/select-service', [BookingController::class, 'selectService'])->name('select-service');
    Route::get('/staff', [BookingController::class, 'staff'])->name('staff');
    Route::post('/select-staff', [BookingController::class, 'selectStaff'])->name('select-staff');
    Route::get('/datetime', [BookingController::class, 'datetime'])->name('datetime');
    Route::get('/get-time-slots', [BookingController::class, 'getTimeSlots'])->name('get-time-slots');
    Route::post('/select-datetime', [BookingController::class, 'selectDateTime'])->name('select-datetime');
    Route::get('/client-info', [BookingController::class, 'clientInfo'])->name('client-info');
    Route::post('/save-client-info', [BookingController::class, 'saveClientInfo'])->name('save-client-info');
    Route::get('/payment', [BookingController::class, 'payment'])->name('payment');
    Route::post('/confirm', [BookingController::class, 'confirmBooking'])->name('confirm');
    Route::get('/confirmation/{reference}', [BookingController::class, 'confirmation'])->name('confirmation');
    Route::post('/go-back', [BookingController::class, 'goBack'])->name('go-back');
});

// API Routes for Availability
Route::prefix('api')->group(function () {
    Route::get('/availability/dates', [AvailabilityController::class, 'getDates'])->name('api.availability.dates');
    Route::get('/availability/time-slots', [AvailabilityController::class, 'getTimeSlots'])->name('api.availability.time-slots');
    Route::get('/availability/staff', [AvailabilityController::class, 'getStaff'])->name('api.availability.staff');
    Route::post('/availability/check-time-slot', [AvailabilityController::class, 'checkTimeSlot'])->name('api.availability.check-time-slot');
});
