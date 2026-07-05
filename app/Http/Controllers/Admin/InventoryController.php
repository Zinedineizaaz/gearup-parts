<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PurchaseOrder;
use App\Models\Product;
use App\Models\StockMove;

class InventoryController extends Controller
{
    public function processGoodsReceipt(Request $request, $poNumber)
    {
        // Validasi input dari staf gudang
        $request->validate([
            'received_items' => 'required|array',
            'received_items.*.product_id' => 'required|uuid',
            'received_items.*.qty_received' => 'required|integer|min:1',
        ]);

        $receivedItems = $request->input('received_items');

        DB::beginTransaction();

        try {
            // 1. Cari Dokumen PO yang valid
            // Gunakan lockForUpdate() agar tidak ada staf lain yang memproses PO yang sama bersamaan
            $purchaseOrder = PurchaseOrder::where('po_number', $poNumber)
                ->whereIn('status', ['draft', 'confirmed'])
                ->lockForUpdate()
                ->firstOrFail();

            // 2. Proses penambahan stok untuk setiap barang yang diterima
            foreach ($receivedItems as $item) {

                $product = Product::where('id', $item['product_id'])->lockForUpdate()->first();

                if (!$product) {
                    throw new \Exception("Suku cadang tidak ditemukan di sistem.");
                }

                // A. Tambah angka stok riil di pangkalan data
                $product->increment('current_stock', $item['qty_received']);

                // B. Catat pergerakan logistik (Stock Move)
                // Ini akan menjadi fondasi data yang sangat solid untuk Data Warehouse nantinya
                StockMove::create([
                    'product_id' => $product->id,
                    'move_type' => 'receipt', // Tipe masuk
                    'quantity' => $item['qty_received'], // Angka positif
                    'reference' => $purchaseOrder->po_number,
                ]);
            }

            // 3. Ubah status PO menjadi Selesai / Diterima
            $purchaseOrder->update(['status' => 'received']);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Penerimaan barang untuk {$poNumber} berhasil dicatat. Stok sistem telah diperbarui.",
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses penerimaan barang: ' . $e->getMessage()
            ], 400);
        }
    }
}