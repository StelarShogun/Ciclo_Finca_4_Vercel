<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id('sale_id');
            $table->string('invoice_number', 50)->unique();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->unsignedBigInteger('seller_admin_id')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('iva', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->enum('payment_method', ['cash', 'sinpe', 'transfer', 'card']);
            $table->string('payment_reference', 100)->nullable();
            // Keep the base schema compatible with SQLite tests (CHECK constraint),
            // while remaining a valid MySQL enum definition.
            $table->enum('status', ['pending', 'ready_to_pickup', 'completed', 'cancelled', 'refunded', 'returned'])
                ->default('pending');
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('client_history_seen_at')->nullable();
            $table->string('order_source', 20)->nullable();
            $table->text('notes')->nullable();
            $table->string('buyer_name', 120)->nullable();
            $table->string('buyer_email', 150)->nullable();
            $table->timestamp('sale_date')->useCurrent();
            $table->timestamps();

            $table->foreign('client_id')->references('user_id')->on('client_table')->nullOnDelete();
            $table->foreign('seller_admin_id')->references('user_id')->on('admins')->nullOnDelete();

            $table->index('invoice_number', 'ventas_numero_factura_index');
            $table->index('status', 'ventas_estado_index');
            $table->index('sale_date', 'ventas_fecha_venta_index');
            $table->index(['client_id', 'status', 'client_history_seen_at'], 'sales_client_status_history_seen_idx');
        });

        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('unit_discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->timestamps();

            $table->foreign('sale_id')->references('sale_id')->on('sales')->cascadeOnDelete();
            $table->foreign('product_id')->references('product_id')->on('products')->cascadeOnDelete();
            $table->index('sale_id', 'detalle_ventas_venta_id_index');
            $table->index('product_id', 'detalle_ventas_producto_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
