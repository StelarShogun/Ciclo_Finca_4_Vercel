<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Creates the inventory movement audit table.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            // Primary key.
            $table->id();

            // Product affected by the inventory movement.
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')
                ->references('product_id')
                ->on('products')
                ->restrictOnDelete();

            // Admin user who triggered the movement, when applicable.
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')
                ->references('user_id')   // Primary key of the admins table.
                ->on('admins')
                ->nullOnDelete();

            // Movement direction stored as a readable string value.
            $table->string('type', 20)->comment('entrada | salida | ajuste | devolucion');

            // Movement origin used for audit and reporting purposes.
            $table->string('origin', 60)->comment('venta | devolucion_cliente | entrada_proveedor | ajuste_manual_entrada | ajuste_manual_salida | cancelacion_venta');

            // Positive quantity affected by the movement.
            $table->unsignedInteger('quantity')->comment('Siempre positivo; la dirección la indica type');

            // Stock snapshot before applying the movement.
            $table->integer('stock_before')->comment('Stock del producto antes del movimiento');

            // Stock snapshot after applying the movement.
            $table->integer('stock_after')->comment('Stock del producto después del movimiento');

            // Optional reference to the source document.
            $table->unsignedBigInteger('reference_id')->nullable()
                ->comment('sale_id, num_order, etc. según origin');

            // Optional user-provided notes.
            $table->text('notes')->nullable()
                ->comment('Observaciones adicionales ingresadas por el usuario');

            // Movement timestamps.
            $table->timestamps(); // created_at = fecha/hora del movimiento; updated_at raramente cambia

            // Supports chronological history queries per product.
            $table->index(['product_id', 'created_at'], 'idx_inv_mov_product_date');

            // Supports filtering by movement type and origin.
            $table->index('type', 'idx_inv_mov_type');
            $table->index('origin', 'idx_inv_mov_origin');

            // Supports traceability to the originating document.
            $table->index(['origin', 'reference_id'], 'idx_inv_mov_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
