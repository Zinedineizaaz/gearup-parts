<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\Product;
use App\Models\StockMove;
use Illuminate\Support\Str;

class POSController extends Controller
{
    /**
     * Memproses transaksi dari kasir toko fisik
     */
    public function processTransaction(Request $request)
    {
        // Kasir mengirimkan array item berdasarkan SKU yang di-scan
        // Format request: ['items' => [['sku' => 'BRK-001', 'qty' => 2], ...]]
        $scannedItems = $request->input('items');

        DB::beginTransaction();

        try {
            // 1. Buat Header Transaksi POS
            $salesOrder = SalesOrder::create([
                'so_number' => 'POS-' . strtoupper(Str::random(8)),
                'partner_id' => null, // Pelanggan offline (walk-in) biasanya anonim
                'source' => 'pos_cashier',
                'status' => 'completed', // Langsung selesai karena bayar di tempat
                'total_amount' => 0,
            ]);

            $totalAmount = 0;

            // 2. Proses Setiap Item yang Di-scan Kasir
            foreach ($scannedItems as $item) {
                // Cari produk berdasarkan SKU (Barcode)
                $product = Product::where('sku', $item['sku'])->lockForUpdate()->first();

                if (!$product) {
                    throw new \Exception("Produk dengan SKU {$item['sku']} tidak ditemukan.");
                }

                if ($product->current_stock < $item['qty']) {
                    throw new \Exception("Stok fisik {$product->name} tidak cukup di sistem!");
                }

                $subtotal = $product->sale_price * $item['qty'];
                $totalAmount += $subtotal;

                // A. Catat Detail Struk Belanja
                SalesOrderLine::create([
                    'sales_order_id' => $salesOrder->id,
                    'product_id' => $product->id,
                    'qty' => $item['qty'],
                    'unit_price' => $product->sale_price,
                    'subtotal' => $subtotal,
                ]);

                // B. Kurangi Stok secara Real-time (Otomatis sinkron dengan Web)
                $product->decrement('current_stock', $item['qty']);

                // C. Catat Log Pergerakan Gudang
                StockMove::create([
                    'product_id' => $product->id,
                    'move_type' => 'delivery',
                    'quantity' => -$item['qty'],
                    'reference' => $salesOrder->so_number,
                ]);
            }

            // Update Total Pembayaran Struk
            $salesOrder->update(['total_amount' => $totalAmount]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi kasir berhasil dicatat.',
                'receipt_number' => $salesOrder->so_number,
                'total_paid' => $totalAmount
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }
}