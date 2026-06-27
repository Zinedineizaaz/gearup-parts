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
        // Tabel Header SO
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('so_number')->unique();
            $table->foreignUuid('partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->enum('source', ['pos_cashier', 'web_ecommerce']);
            $table->enum('status', ['pending', 'paid', 'shipped', 'completed']);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamps();
        });

        // Tabel Detail Item SO
        Schema::create('sales_order_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->integer('qty');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
