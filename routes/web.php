<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\POSController;
use App\Http\Controllers\Consumer\FitmentController;
use App\Http\Controllers\Admin\InventoryController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'is_admin'])->group(function () {
    // Rute untuk transaksi kasir offline
    Route::post('/pos/transaction', [POSController::class, 'processTransaction']);
});

// Rute untuk mendapatkan data dropdown progresif di Homepage
Route::get('/api/fitment/options', [FitmentController::class, 'getMotorcycleOptions']);

// Rute untuk mengeksekusi pencarian katalog produk
Route::get('/api/catalog/search', [FitmentController::class, 'search']);

Route::middleware(['auth', 'is_admin'])->group(function () {
    // Rute sebelumnya...
    Route::post('/pos/transaction', [POSController::class, 'processTransaction']);

    // Rute untuk proses penerimaan barang di gudang
    Route::post('/inventory/receive/{poNumber}', [InventoryController::class, 'processGoodsReceipt']);
});

require __DIR__ . '/auth.php';
