<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PurchaseOrder;

class PurchaseOrderController extends Controller
{
    /**
     * Menampilkan daftar Draf PO yang butuh tinjauan/persetujuan Manajer
     */
    public function pendingApprovals()
    {
        // Menggunakan Eager Loading (with) untuk menarik relasi Partner (Supplier) 
        // dan baris detail produk (lines) agar kinerja database tetap optimal
        $draftPOs = PurchaseOrder::with(['partner', 'lines.product'])
            ->where('status', 'draft')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $draftPOs,
            'message' => 'Menampilkan daftar PO yang berstatus Draft.'
        ], 200);
    }

    /**
     * Mengeksekusi persetujuan PO oleh Manajer
     */
    public function approve($poNumber)
    {
        DB::beginTransaction();

        try {
            // 1. Cari PO yang masih draft dan kunci baris datanya (Pessimistic Locking)
            // Ini mencegah 2 manajer menyetujui PO yang sama di detik yang persis sama
            $po = PurchaseOrder::where('po_number', $poNumber)
                ->where('status', 'draft')
                ->lockForUpdate()
                ->firstOrFail();

            // 2. Ubah status menjadi 'confirmed'
            $po->update(['status' => 'confirmed']);

            // --- INFO TAMBAHAN ---
            // Di titik ini, dalam sistem berskala penuh, Anda bisa memicu 
            // Laravel Event/Job (Queue) untuk meng-generate file PDF secara asinkron
            // lalu mengirimkannya otomatis ke email pihak Supplier.

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Purchase Order {$poNumber} berhasil disetujui. Tim Gudang kini dapat memproses penerimaan barang (Goods Receipt) untuk pesanan ini."
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'PO tidak ditemukan, atau statusnya sudah disetujui/diproses sebelumnya.'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()
            ], 500);
        }
    }
}