<?php

use App\Http\Controllers\Admin\PurchaseOrderController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InventoryController;

// Semua staf internal bisa akses
Route::middleware(['auth', 'is_admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
});

// HANYA Admin dan Manajer yang bisa akses
Route::middleware(['auth', 'is_admin:admin,manager'])->group(function () {
    Route::post('/purchase-order/approve/{id}', [PurchaseOrderController::class, 'approve']);
});

// HANYA Staf Gudang yang bisa terima barang
Route::middleware(['auth', 'is_admin:warehouse'])->group(function () {
    Route::post('/inventory/receive/{poNumber}', [InventoryController::class, 'processGoodsReceipt']);
});

Route::middleware(['auth', 'is_admin:admin,manager'])->group(function () {

    // Menampilkan daftar PO yang butuh persetujuan
    Route::get('/purchase-order/pending', [PurchaseOrderController::class, 'pendingApprovals']);

    // Mengeksekusi persetujuan (Ubah status dari 'draft' ke 'confirmed')
    Route::post('/purchase-order/approve/{poNumber}', [PurchaseOrderController::class, 'approve']);

});

Route::middleware(['auth', 'is_admin'])->group(function () {
    // Endpoint utama Dashboard
    Route::get('/dashboard/metrics', [DashboardController::class, 'index']);
});

?>