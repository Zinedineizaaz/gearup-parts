<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Buat tabel dan kolomnya terlebih dahulu
        Schema::create('product_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->uuid('parent_id')->nullable(); // Hanya buat kolom, jangan beri constraint dulu
            $table->timestamps();
        });

        // 2. Tambahkan Foreign Key setelah tabel selesai dibuat
        Schema::table('product_categories', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('product_categories')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
