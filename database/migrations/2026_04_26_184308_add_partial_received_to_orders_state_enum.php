<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Amplía el ENUM de `state` en la tabla `orders` para incluir `partial_received`.
        // Se usa DB::statement porque Laravel Schema Builder no expone un helper directo
        // para modificar columnas ENUM en MySQL sin reescribir toda la definición.
        DB::statement("
            ALTER TABLE `orders`
            MODIFY COLUMN `state` ENUM(
                'draft',
                'pending',
                'confirmed',
                'partial_received',
                'delivered',
                'cancelled'
            ) NOT NULL DEFAULT 'draft'
        ");
    }

    public function down(): void
    {
        // Ojo: si existen filas con state = 'partial_received' este rollback fallará
        // o truncará datos. Asegúrate de migrarlas antes de revertir.
        DB::statement("
            ALTER TABLE `orders`
            MODIFY COLUMN `state` ENUM(
                'draft',
                'pending',
                'confirmed',
                'delivered',
                'cancelled'
            ) NOT NULL DEFAULT 'draft'
        ");
    }
};
