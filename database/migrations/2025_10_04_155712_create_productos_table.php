<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('productos', function(Blueprint $table){
            $table->id('producto_id');
            $table->foreignId('categoria_id')->nullable()->constrained('categorias','categoria_id')->onDelete('set null');
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores','proveedor_id')->onDelete('set null');
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            $table->decimal('precio_venta', 10, 2)->default(0.00);
            $table->decimal('precio_compra', 10, 2)->default(0.00);
            $table->integer('stock_actual')->default(0);
            $table->integer('stock_minimo')->default(0);
            $table->enum('estado', ['activo', 'inactivo', 'agotado', 'descontinuado'])->default('activo');
            $table->timestamp('fecha_creacion')->useCurrent();
            $table->timestamp('fecha_modificacion')->useCurrent()->useCurrentOnUpdate();
            
            // Índices
            $table->index('nombre');
            $table->index('categoria_id');
            $table->index('proveedor_id');
            $table->index('estado');
            $table->index('stock_actual');
            // fullText no es compatible con SQLite, se omite para las pruebas
        });
    }
    public function down(): void { Schema::dropIfExists('productos'); }
};
