<?php

namespace App\Http\Controllers\Consumer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Motorcycle;


class FitmentController extends Controller
{
    public function search(Request $request)
    {
        // 1. Inisialisasi Query Builder Produk
        // Gunakan eager loading (with) untuk kategori agar tidak terjadi N+1 query problem
        $query = Product::with('category')->where('current_stock', '>', 0);

        // 2. Terapkan Filter Fitment Motor Jika Parameter Dikirim oleh Frontend
        if ($request->filled('brand') || $request->filled('model') || $request->filled('year')) {

            $query->whereHas('fitments', function ($fitmentQuery) use ($request) {
                if ($request->filled('brand')) {
                    $fitmentQuery->where('brand', $request->brand);
                }

                if ($request->filled('model')) {
                    $fitmentQuery->where('model', $request->model);
                }

                if ($request->filled('year')) {
                    $fitmentQuery->where('year', $request->year);
                }
            });
        }

        // 3. Tambahan: Filter Berdasarkan Jenis Suku Cadang (OEM / Aftermarket)
        if ($request->filled('part_origin')) {
            $query->where('part_origin', $request->part_origin);
        }

        // 4. Eksekusi Query dan Lakukan Pagination (menampilkan 12 produk per halaman)
        $products = $query->paginate(12);

        // 5. Kembalikan Response dalam format JSON untuk dirender oleh Frontend
        return response()->json([
            'status' => 'success',
            'data' => $products,
            'message' => 'Berhasil memuat katalog produk sesuai fitment.'
        ]);
    }

    public function getMotorcycleOptions(Request $request)
    {
        // Jika frontend meminta daftar Merek
        if ($request->step === 'brand') {
            $brands = Motorcycle::select('brand')->distinct()->pluck('brand');
            return response()->json(['brands' => $brands]);
        }

        // Jika frontend meminta daftar Model berdasarkan Merek yang dipilih
        if ($request->step === 'model' && $request->filled('brand')) {
            $models = Motorcycle::where('brand', $request->brand)
                ->select('model')
                ->distinct()
                ->pluck('model');
            return response()->json(['models' => $models]);
        }

        // Jika frontend meminta daftar Tahun berdasarkan Model yang dipilih
        if ($request->step === 'year' && $request->filled('model')) {
            $years = Motorcycle::where('model', $request->model)
                ->select('year')
                ->distinct()
                ->orderBy('year', 'desc')
                ->pluck('year');
            return response()->json(['years' => $years]);
        }

        return response()->json(['error' => 'Parameter tidak valid'], 400);
    }
}
