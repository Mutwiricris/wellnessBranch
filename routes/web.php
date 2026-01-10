<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Note: Public booking flow removed - all booking management now handled in Filament admin panel
// Note: Payment webhook routes should be added in routes/api.php if needed (e.g., M-Pesa callbacks)
