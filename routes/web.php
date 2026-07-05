<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PosReceiptController;

Route::get('/', function () {
    return view('welcome');
});

// POS Receipt Routes
Route::get('/pos/receipt/{transaction}/print', [PosReceiptController::class, 'print'])->name('pos.receipt.print');
Route::get('/pos/receipt/{transaction}/view', [PosReceiptController::class, 'view'])->name('pos.receipt.view');
Route::get('/pos/receipt/{transaction}/download', [PosReceiptController::class, 'download'])->name('pos.receipt.download');

// Note: Public booking flow removed - all booking management now handled in Filament admin panel
// Note: Payment webhook routes should be added in routes/api.php if needed (e.g., M-Pesa callbacks)
