<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; 

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proveedores', function (Blueprint $table) {
            $table->id('proveedor_id');
            
            // Campos con longitudes específicas y nullable
            $table->string('nombre', 150);
            $table->string('contacto_principal', 100)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('correo_electronico', 100)->nullable();
            $table->text('direccion')->nullable();
            
            // Campos numéricos con valores por defecto
            $table->integer('tiempo_entrega')->default(0)->comment('Tiempo en días');
            $table->decimal('evaluacion', 3, 2)->default(0.00)->comment('Evaluación de 0.00 a 5.00');
            
            // Campo estado con ENUM
            $table->enum('estado', ['activo', 'inactivo', 'suspendido'])->default('activo');
            
            // Timestamps personalizados
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_modificacion')->useCurrent()->useCurrentOnUpdate();
            
            // Índices
            $table->index('nombre', 'idx_proveedor_nombre');
            $table->index('correo_electronico', 'idx_proveedor_email');
            $table->index('estado', 'idx_proveedor_estado');
        });
        
        // Agregar restricciones CHECK después de crear la tabla
        DB::statement('ALTER TABLE proveedores ADD CONSTRAINT chk_evaluacion CHECK (evaluacion >= 0.00 AND evaluacion <= 5.00)');
        DB::statement('ALTER TABLE proveedores ADD CONSTRAINT chk_tiempo_entrega CHECK (tiempo_entrega >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('proveedores');
    }
};