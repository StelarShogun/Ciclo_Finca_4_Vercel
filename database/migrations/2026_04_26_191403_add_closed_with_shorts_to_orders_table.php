<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega el campo `closed_with_shorts` a la tabla `orders`.
 *
 * Este booleano se activa cuando un administrador cierra manualmente un pedido
 * que estaba en estado `partial_received`, indicando que el proveedor no entregó
 * la totalidad de los productos y que el equipo decidió cerrar el pedido sin
 * continuar esperando la mercancía faltante.
 *
 * El campo es nullable con default false para ser compatible con registros históricos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('closed_with_shorts')
                  ->default(false)
                  ->nullable()
                  ->after('received_at')
                  ->comment('true si el pedido se cerró manualmente desde partial_received con faltantes del proveedor');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('closed_with_shorts');
        });
    }
};