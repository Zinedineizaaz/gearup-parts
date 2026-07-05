<?php

namespace App\Http\Controllers\Consumer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\Product;
use App\Models\StockMove;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function processCheckout(Request $request)
    {
        // Asumsi data keranjang didapat dari request (bisa juga dari Session/Database Cart)
        $cartItems = $request->input('items'); // array of ['product_id' => x, 'qty' => y]
        $partnerId = auth()->user()->partner->id; // Ambil ID profil pelanggan

        DB::beginTransaction();

        try {
            // 1. Buat Header Sales Order
            $salesOrder = SalesOrder::create([
                'so_number' => 'SO-' . strtoupper(Str::random(8)),
                'partner_id' => $partnerId,
                'source' => 'web_ecommerce',
                'status' => 'paid', // Asumsi pembayaran langsung berhasil
                'total_amount' => 0,
            ]);

            $totalAmount = 0;

            // 2. Proses Setiap Item di Keranjang
            foreach ($cartItems as $item) {
                // Lock row product untuk mencegah Race Condition (Penting untuk sistem dengan traffic tinggi)
                $product = Product::where('id', $item['product_id'])->lockForUpdate()->first();

                if ($product->current_stock < $item['qty']) {
                    throw new \Exception("Stok tidak mencukupi untuk produk: {$product->name}");
                }

                $subtotal = $product->sale_price * $item['qty'];
                $totalAmount += $subtotal;

                // A. Catat Detail Pesanan (Sales Order Line)
                SalesOrderLine::create([
                    'sales_order_id' => $salesOrder->id,
                    'product_id' => $product->id,
                    'qty' => $item['qty'],
                    'unit_price' => $product->sale_price,
                    'subtotal' => $subtotal,
                ]);

                // B. Kurangi Stok Aktual di Master Product
                $product->decrement('current_stock', $item['qty']);

                // C. Catat Riwayat Pergerakan Barang (Stock Move)
                StockMove::create([
                    'product_id' => $product->id,
                    'move_type' => 'delivery',
                    'quantity' => -$item['qty'], // Negatif karena barang keluar
                    'reference' => $salesOrder->so_number,
                ]);

                // D. Cek Reorder Point (Otomatisasi Pengadaan P2P)
                $this->checkAndTriggerReorder($product);
            }

            $salesOrder->update(['total_amount' => $totalAmount]);

            // Jika semua proses di atas berhasil, simpan permanen ke database
            DB::commit();

            return response()->json([
                'message' => 'Checkout berhasil diproses!',
                'so_number' => $salesOrder->so_number
            ], 200);

        } catch (\Exception $e) {
            // Batalkan semua perubahan database jika ada 1 saja proses yang gagal
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }

    }

    private function checkAndTriggerReorder(Product $product)
    {
        $minimumStockLimit = 5; // Batas minimum stok (idealnya dinamis dari database)

        if ($product->current_stock <= $minimumStockLimit) {

            // Cek apakah produk ini sudah ada Draft PO yang belum diproses untuk menghindari duplikasi pesanan
            $existingPO = PurchaseOrder::whereHas('lines', function ($query) use ($product) {
                $query->where('product_id', $product->id);
            })->where('status', 'draft')->exists();

            if (!$existingPO) {
                // Buat Draft PO Baru ke Supplier Utama (Asumsi vendor_id statis untuk contoh)
                $po = PurchaseOrder::create([
                    'po_number' => 'PO-' . strtoupper(Str::random(8)),
                    'partner_id' => 'masukkan-uuid-supplier-disini',
                    'status' => 'draft',
                    'total_amount' => $product->cost_price * 20 // Asumsi otomatis pesan 20 pcs
                ]);

                PurchaseOrderLine::create([
                    'purchase_order_id' => $po->id,
                    'product_id' => $product->id,
                    'qty' => 20,
                    'unit_price' => $product->cost_price,
                    'subtotal' => $product->cost_price * 20,
                ]);
            }
        }
    }
}
