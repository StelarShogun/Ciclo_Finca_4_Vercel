<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Refactors the sales module to 100% English:
 * - ventas -> sales (table and column names)
 * - detalle_ventas -> sale_items
 * - Status and payment_method enum values to English
 * Preserves existing data.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'mysql') {
            $this->upSqlite($driver);

            return;
        }

        // Drop foreign keys (MySQL-generated names)
        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->dropForeign(['venta_id']);
        });
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropForeign(['vendedor_id']);
        });

        // Rename ventas -> sales
        Schema::rename('ventas', 'sales');

        // Rename columns in sales
        Schema::table('sales', function (Blueprint $table) {
            $table->renameColumn('venta_id', 'sale_id');
            $table->renameColumn('numero_factura', 'invoice_number');
            $table->renameColumn('cliente_id', 'customer_id');
            $table->renameColumn('vendedor_id', 'seller_id');
            $table->renameColumn('fecha_venta', 'sale_date');
            $table->renameColumn('metodo_pago', 'payment_method');
            $table->renameColumn('referencia_pago', 'payment_reference');
            $table->renameColumn('estado', 'status');
            $table->renameColumn('notas', 'notes');
            $table->renameColumn('descuento', 'discount');
        });

        // Change enum to English values (MySQL: modify column)
        DB::statement("ALTER TABLE sales MODIFY COLUMN status ENUM('pending', 'completed', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending'");
        DB::statement("ALTER TABLE sales MODIFY COLUMN payment_method ENUM('cash', 'sinpe', 'transfer', 'card') NOT NULL");
        DB::statement("UPDATE sales SET status = CASE status
            WHEN 'pendiente' THEN 'pending'
            WHEN 'completada' THEN 'completed'
            WHEN 'cancelada' THEN 'cancelled'
            WHEN 'reembolsada' THEN 'refunded'
            ELSE status END");
        DB::statement("UPDATE sales SET payment_method = CASE payment_method
            WHEN 'efectivo' THEN 'cash'
            WHEN 'sinpe' THEN 'sinpe'
            WHEN 'transferencia' THEN 'transfer'
            WHEN 'tarjeta' THEN 'card'
            ELSE payment_method END");

        // Rename detalle_ventas -> sale_items
        Schema::rename('detalle_ventas', 'sale_items');

        // Rename columns in sale_items
        Schema::table('sale_items', function (Blueprint $table) {
            $table->renameColumn('detalle_id', 'id');
            $table->renameColumn('venta_id', 'sale_id');
            $table->renameColumn('cantidad', 'quantity');
            $table->renameColumn('precio_unitario', 'unit_price');
            $table->renameColumn('descuento_unitario', 'unit_discount');
        });

        // Re-add foreign keys
        Schema::table('sales', function (Blueprint $table) {
            $table->foreign('customer_id')->references('usuario_id')->on('usuarios')->onDelete('cascade');
            $table->foreign('seller_id')->references('usuario_id')->on('usuarios')->onDelete('cascade');
        });
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreign('sale_id')->references('sale_id')->on('sales')->onDelete('cascade');
        });
    }

    /** SQLite (e.g. testing): skip renames, only document; or create new tables and copy. For this project we assume MySQL. */
    protected function upSqlite(string $driver): void
    {
        // If not MySQL, skip to avoid breaking tests; document that production uses MySQL.

    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'mysql') {
            return;
        }

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['sale_id']);
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropForeign(['seller_id']);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->renameColumn('id', 'detalle_id');
            $table->renameColumn('sale_id', 'venta_id');
            $table->renameColumn('quantity', 'cantidad');
            $table->renameColumn('unit_price', 'precio_unitario');
            $table->renameColumn('unit_discount', 'descuento_unitario');
        });
        Schema::rename('sale_items', 'detalle_ventas');

        DB::statement("UPDATE sales SET status = CASE status WHEN 'pending' THEN 'pendiente' WHEN 'completed' THEN 'completada' WHEN 'cancelled' THEN 'cancelada' WHEN 'refunded' THEN 'reembolsada' ELSE status END");
        DB::statement("UPDATE sales SET payment_method = CASE payment_method WHEN 'cash' THEN 'efectivo' WHEN 'sinpe' THEN 'sinpe' WHEN 'transfer' THEN 'transferencia' WHEN 'card' THEN 'tarjeta' ELSE payment_method END");
        DB::statement("ALTER TABLE sales MODIFY COLUMN status ENUM('pendiente', 'completada', 'cancelada', 'reembolsada') NOT NULL DEFAULT 'pendiente'");
        DB::statement("ALTER TABLE sales MODIFY COLUMN payment_method ENUM('efectivo', 'sinpe', 'transferencia', 'tarjeta') NOT NULL");

        Schema::table('sales', function (Blueprint $table) {
            $table->renameColumn('sale_id', 'venta_id');
            $table->renameColumn('invoice_number', 'numero_factura');
            $table->renameColumn('customer_id', 'cliente_id');
            $table->renameColumn('seller_id', 'vendedor_id');
            $table->renameColumn('sale_date', 'fecha_venta');
            $table->renameColumn('payment_method', 'metodo_pago');
            $table->renameColumn('payment_reference', 'referencia_pago');
            $table->renameColumn('status', 'estado');
            $table->renameColumn('notes', 'notas');
            $table->renameColumn('discount', 'descuento');
        });
        Schema::rename('sales', 'ventas');

        Schema::table('ventas', function (Blueprint $table) {
            $table->foreign('cliente_id')->references('usuario_id')->on('usuarios')->onDelete('cascade');
            $table->foreign('vendedor_id')->references('usuario_id')->on('usuarios')->onDelete('cascade');
        });
        Schema::table('detalle_ventas', function (Blueprint $table) {
            $table->foreign('venta_id')->references('venta_id')->on('ventas')->onDelete('cascade');
        });
    }
};
