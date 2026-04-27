<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedInteger('received_quantity')
                  ->nullable()
                  ->default(null)
                  ->after('quantity')
                  ->comment('Cantidad efectivamente recibida al registrar la recepción del pedido.');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('received_quantity');
        });
    }
};