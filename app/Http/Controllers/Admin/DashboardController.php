<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SalesOrder;
use App\Models\PurchaseOrder;
use App\Models\Product;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Mengambil data agregasi untuk ditampilkan di Dashboard Admin/Manajer
     */
    public function index()
    {
        // Tetapkan waktu saat ini
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // 1. METRIK PENJUALAN: Total Pendapatan Bulan Ini (Web & POS)
        // Hanya menghitung transaksi yang sudah dibayar atau selesai
        $monthlyRevenue = SalesOrder::whereMonth('created_at', $currentMonth)
            ->whereYear('created_at', $currentYear)
            ->whereIn('status', ['paid', 'completed'])
            ->sum('total_amount');

        // 2. METRIK PENGADAAN: Hitung PO yang butuh persetujuan (Draft)
        $pendingPOCount = PurchaseOrder::where('status', 'draft')->count();

        // 3. METRIK INVENTARIS: Peringatan Stok Menipis (Low Stock Alert)
        // Menarik daftar produk yang stoknya 5 atau ke bawah
        $lowStockProducts = Product::where('current_stock', '<=', 5)
            ->select('id', 'sku', 'name', 'current_stock')
            ->orderBy('current_stock', 'asc') // Urutkan dari stok yang paling kritis (0)
            ->take(10) // Tampilkan maksimal 10 produk di dashboard
            ->get();

        // 4. METRIK AKTIVITAS: 5 Transaksi Penjualan Terakhir
        $recentTransactions = SalesOrder::with('partner:id,name') // Ambil nama pelanggan jika ada
            ->select('id', 'so_number', 'partner_id', 'source', 'total_amount', 'created_at')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Kembalikan semua metrik dalam satu respons JSON yang rapi
        return response()->json([
            'status' => 'success',
            'message' => 'Data metrik dashboard berhasil dimuat.',
            'data' => [
                'summary' => [
                    'revenue_this_month' => $monthlyRevenue,
                    'pending_po_count' => $pendingPOCount,
                ],
                'alerts' => [
                    'low_stock_items' => $lowStockProducts
                ],
                'recent_activity' => $recentTransactions
            ]
        ], 200);
    }
}