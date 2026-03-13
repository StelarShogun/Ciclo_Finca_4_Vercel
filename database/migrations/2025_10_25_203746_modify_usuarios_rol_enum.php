<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    ##initialize
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primero necesitamos cambiar la columna a string temporalmente
        Schema::table('usuarios', function (Blueprint $table) {
            $table->string('rol', 20)->change();
        });
        
        // Luego la cambiamos de vuelta a enum con los nuevos valores
        Schema::table('usuarios', function (Blueprint $table) {
            $table->enum('rol', ['admin', 'cliente', 'tecnico', 'vendedor', 'proveedor'])->default('cliente')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table) {
            $table->enum('rol', ['admin', 'cliente', 'tecnico', 'vendedor'])->default('cliente')->change();
        });
    }
};
