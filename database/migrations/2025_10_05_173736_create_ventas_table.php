<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ventas', function (Blueprint $table) {
            $table->id('venta_id');
            $table->string('numero_factura', 50)->unique();
            $table->unsignedBigInteger('cliente_id');
            $table->unsignedBigInteger('vendedor_id');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('iva', 10, 2)->default(0);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->enum('metodo_pago', ['efectivo', 'sinpe', 'transferencia', 'tarjeta']);
            $table->string('referencia_pago', 100)->nullable();
            $table->enum('estado', ['pendiente', 'completada', 'cancelada', 'reembolsada'])->default('pendiente');
            $table->text('notas')->nullable();
            $table->timestamp('fecha_venta')->useCurrent();
            $table->timestamps();
            
            // Índices
            $table->index('numero_factura');
            $table->index('cliente_id');
            $table->index('estado');
            $table->index('fecha_venta');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};