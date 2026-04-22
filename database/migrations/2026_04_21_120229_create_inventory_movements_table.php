<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: tabla de historial de movimientos de inventario.
 *
 * Cada fila representa UN movimiento atómico sobre el stock de un producto.
 * Los campos stock_before / stock_after permiten auditar el estado del inventario
 * en cualquier punto del tiempo sin recalcular acumulados, y detectar inconsistencias
 * si el stock_current del producto no coincide con el último stock_after registrado.
 *
 * Historia de Usuario: auditoría de movimientos de inventario por producto.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            // ── Identificador ──────────────────────────────────────────────
            $table->id();

            // ── Relaciones ─────────────────────────────────────────────────
            /**
             * Producto afectado.
             * ON DELETE RESTRICT: nunca se debe eliminar un producto con historial;
             * el destroy() del ProductController ya hace soft-delete (status=inactive).
             */
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')
                  ->references('product_id')
                  ->on('products')
                  ->restrictOnDelete();

            /**
             * Administrador que originó el movimiento.
             * Nullable para movimientos automáticos (web_cart, jobs futuros).
             * ON DELETE SET NULL: si se elimina el admin el historial se conserva.
             *
             * CORRECCIÓN: referencia admins.user_id (PK del modelo AdminUser),
             * no users.id, ya que los administradores se gestionan en la tabla admins.
             */
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')
                  ->references('user_id')   // PK de la tabla admins ($primaryKey = 'user_id')
                  ->on('admins')
                  ->nullOnDelete();

            // ── Clasificación del movimiento ────────────────────────────────
            /**
             * Tipo de movimiento (dirección):
             *   entrada    → incrementa stock (compras, ajustes positivos, devolución de cliente)
             *   salida     → decrementa stock (ventas, ajustes negativos, daños)
             *   ajuste     → fija el stock a un valor absoluto (corrección manual)
             *   devolucion → alias semántico de entrada cuando proviene de un cliente
             *
             * Se almacena como string para que sea legible en consultas SQL directas
             * sin tener que unir con un catálogo de tipos.
             */
            $table->string('type', 20)->comment('entrada | salida | ajuste | devolucion');

            /**
             * Origen / motivo del movimiento (quién lo disparó):
             *   venta               → SalesController::store()
             *   cancelacion_venta   → SalesController::cancel() — libera stock de venta pendiente
             *   devolucion_cliente  → SalesController::refund()
             *   entrada_proveedor   → SupplierOrderController::updateState() cuando pasa a 'delivered'
             *   ajuste_manual_entrada → ProductController::addManualStock()
             *   ajuste_manual_salida  → ProductController::removeManualStock()
             *
             * Campo string libre para que sea extensible sin alterar Enum ni migrar.
             */
            $table->string('origin', 60)->comment('venta | devolucion_cliente | entrada_proveedor | ajuste_manual_entrada | ajuste_manual_salida | cancelacion_venta');

            // ── Cantidades ──────────────────────────────────────────────────
            /**
             * Cantidad afectada en este movimiento.
             * Siempre POSITIVA; la dirección la da el campo `type`.
             * Para ajuste absoluto se guarda la cantidad final, no una diferencia.
             */
            $table->unsignedInteger('quantity')->comment('Siempre positivo; la dirección la indica type');

            /**
             * Snapshot del stock justo ANTES de aplicar este movimiento.
             * Permite reconstruir el historial completo y detectar inconsistencias.
             */
            $table->integer('stock_before')->comment('Stock del producto antes del movimiento');

            /**
             * Snapshot del stock resultante DESPUÉS de aplicar este movimiento.
             * stock_after del último registro debe coincidir con products.stock_current.
             */
            $table->integer('stock_after')->comment('Stock del producto después del movimiento');

            // ── Referencia externa (opcional) ───────────────────────────────
            /**
             * ID del documento que generó el movimiento (sale_id, num_order, etc.).
             * Nullable: los ajustes manuales no tienen documento de referencia.
             */
            $table->unsignedBigInteger('reference_id')->nullable()
                  ->comment('sale_id, num_order, etc. según origin');

            // ── Notas ───────────────────────────────────────────────────────
            $table->text('notes')->nullable()
                  ->comment('Observaciones adicionales ingresadas por el usuario');

            // ── Timestamps ──────────────────────────────────────────────────
            $table->timestamps(); // created_at = fecha/hora del movimiento; updated_at raramente cambia

            // ── Índices ─────────────────────────────────────────────────────
            // Consulta más frecuente: historial cronológico de un producto
            $table->index(['product_id', 'created_at'], 'idx_inv_mov_product_date');

            // Filtro por tipo o por origen en reportes de auditoría
            $table->index('type',   'idx_inv_mov_type');
            $table->index('origin', 'idx_inv_mov_origin');

            // Trazabilidad hacia el documento original
            $table->index(['origin', 'reference_id'], 'idx_inv_mov_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};