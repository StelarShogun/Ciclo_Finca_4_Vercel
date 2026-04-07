<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Asegura que `sales` tenga los campos necesarios para el módulo CF4 web_cart.
     *
     * Motivo: error runtime al insertar `order_source` en `sales` cuando la columna
     * no existe en el esquema de la base de datos.
     */
    public function up(): void
    {
        if (! Schema::hasTable('sales')) {
            return;
        }

        Schema::table('sales', function (Blueprint $table) {
            if (! Schema::hasColumn('sales', 'buyer_name')) {
                $table->string('buyer_name', 120)->nullable()->after('notes');
            }
            if (! Schema::hasColumn('sales', 'buyer_email')) {
                $table->string('buyer_email', 150)->nullable()->after('buyer_name');
            }
            if (! Schema::hasColumn('sales', 'order_source')) {
                $table->string('order_source', 20)->nullable()->after('status');
            }
            if (! Schema::hasColumn('sales', 'seller_admin_id')) {
                $table->unsignedBigInteger('seller_admin_id')->nullable()->after('seller_id');
            }
        });

        // Backfill de order_source según origen (si la columna existe).
        if (Schema::hasColumn('sales', 'order_source')) {
            DB::statement("UPDATE sales SET order_source='web_cart' WHERE client_id IS NOT NULL");
            DB::statement("UPDATE sales SET order_source='walk_in' WHERE client_id IS NULL");
        }

        // FK para seller_admin_id (si aplica).
        if (Schema::hasColumn('sales', 'seller_admin_id') && Schema::hasTable('admins')) {
            try {
                Schema::table('sales', function (Blueprint $table) {
                    $table->foreign('seller_admin_id')
                        ->references('user_id')->on('admins')
                        ->nullOnDelete();
                });
            } catch (Throwable $e) {
                // Si el FK ya existe o hay diferencias de esquema, no rompemos la migración.
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('sales')) {
            return;
        }

        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'seller_admin_id')) {
                try {
                    $table->dropForeign(['seller_admin_id']);
                } catch (Throwable $e) {
                    // ignore
                }
            }
            if (Schema::hasColumn('sales', 'buyer_email')) {
                $table->dropColumn('buyer_email');
            }
            if (Schema::hasColumn('sales', 'buyer_name')) {
                $table->dropColumn('buyer_name');
            }
            if (Schema::hasColumn('sales', 'order_source')) {
                $table->dropColumn('order_source');
            }
            if (Schema::hasColumn('sales', 'seller_admin_id')) {
                $table->dropColumn('seller_admin_id');
            }
        });
    }
};
