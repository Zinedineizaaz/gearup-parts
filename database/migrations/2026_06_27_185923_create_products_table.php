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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('sku')->unique();
            $table->string('name');
            $table->foreignUuid('category_id')->constrained('product_categories')->cascadeOnDelete();
            $table->enum('part_origin', ['OEM', 'Aftermarket']);
            $table->decimal('cost_price', 15, 2); // Harga modal
            $table->decimal('sale_price', 15, 2); // Harga jual
            $table->integer('current_stock')->default(0); // Stok aktual hasil kalkulasi
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
