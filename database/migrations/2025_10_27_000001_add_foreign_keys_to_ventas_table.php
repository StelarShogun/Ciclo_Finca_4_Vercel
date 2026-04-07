<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            // Asegurar que no existan FKs previas con nombres distintos
            // y agregar las claves foráneas correctas
            $table->foreign('cliente_id')
                ->references('usuario_id')->on('usuarios')
                ->onDelete('cascade');

            $table->foreign('vendedor_id')
                ->references('usuario_id')->on('usuarios')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropForeign(['vendedor_id']);
        });
    }
};
