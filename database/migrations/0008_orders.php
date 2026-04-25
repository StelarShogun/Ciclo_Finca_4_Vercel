<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('num_order');
            $table->string('po_number', 32)->nullable();
            $table->unsignedBigInteger('supplier_id');
            $table->timestamp('date')->nullable();
            $table->date('estimated_delivery_date')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->enum('state', ['draft', 'pending', 'confirmed', 'delivered', 'cancelled'])->default('pending');
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('supplier_id')->references('supplier_id')->on('suppliers')->cascadeOnDelete();
            $table->unique('po_number', 'uq_orders_po_number');
            $table->index('supplier_id', 'idx_orders_supplier');
            $table->index('state', 'idx_orders_state');
            $table->index('date', 'idx_orders_date');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_num_order');
            $table->unsignedBigInteger('product_id');
            $table->string('name', 255);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('order_num_order')->references('num_order')->on('orders')->cascadeOnDelete();
            $table->foreign('product_id')->references('product_id')->on('products')->restrictOnDelete();
            $table->index('order_num_order', 'idx_order_items_order');
            $table->index('product_id', 'idx_order_items_product');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            try {
                DB::statement('ALTER TABLE order_items ADD FULLTEXT KEY ft_order_items_name (`name`)');
            } catch (\Throwable) {
                // optional
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
