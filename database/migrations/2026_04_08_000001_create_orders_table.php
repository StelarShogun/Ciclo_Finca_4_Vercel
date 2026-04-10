<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('num_order');
            $table->unsignedBigInteger('supplier_id');
            $table->json('products');  // [{ product_id, name, quantity, unit_price, total }, ...]
            $table->timestamp('date');
            $table->enum('state', ['pending', 'confirmed', 'delivered', 'cancelled'])->default('pending');
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('supplier_id')
                ->references('supplier_id')
                ->on('suppliers')
                ->onDelete('cascade');

            $table->index('supplier_id', 'idx_orders_supplier');
            $table->index('state', 'idx_orders_state');
            $table->index('date', 'idx_orders_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
