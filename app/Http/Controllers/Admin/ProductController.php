<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Product;

class ProductController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi Input Data Master
        $validated = $request->validate([
            'sku' => 'required|string|unique:products,sku',
            'name' => 'required|string|max:255',
            'category_id' => 'required|uuid|exists:product_categories,id',
            'part_origin' => 'required|in:OEM,Aftermarket',
            'cost_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'motorcycle_ids' => 'nullable|array', // Array ID motor yang cocok
            'motorcycle_ids.*' => 'uuid|exists:motorcycles,id'
        ]);

        DB::beginTransaction();

        try {
            // 2. Simpan Data Inti Produk
            $product = Product::create([
                'sku' => $validated['sku'],
                'name' => $validated['name'],
                'category_id' => $validated['category_id'],
                'part_origin' => $validated['part_origin'],
                'cost_price' => $validated['cost_price'],
                'sale_price' => $validated['sale_price'],
                'current_stock' => 0, // Stok awal selalu 0, harus diisi via Goods Receipt
            ]);

            // 3. Simpan Relasi Fitment (Many-to-Many)
            // Fungsi 'sync' otomatis menyisipkan data ke tabel pivot 'product_fitments'
            if (!empty($validated['motorcycle_ids'])) {
                $product->fitments()->sync($validated['motorcycle_ids']);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Data suku cadang berhasil ditambahkan.',
                'data' => $product->load('fitments') // Kembalikan data beserta relasinya
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Gagal menyimpan produk: ' . $e->getMessage()], 400);
        }
    }
}
