<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add walk-in support fields to `sales` without breaking OAuth/login.
     *
     * - `buyer_name` / `buyer_email` are optional (admin can register without selecting a customer).
     * - `order_source` marks origin so CF4-4 can list the correct purchases.
     * - `seller_admin_id` links the sale registrar (auth:admin) when available.
     */
    public function up(): void
    {
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

            // FK solo si existe la tabla referenciada.
            if (Schema::hasTable('admins')) {
                try {
                    $table->foreign('seller_admin_id')
                        ->references('user_id')->on('admins')
                        ->nullOnDelete();
                } catch (Throwable $e) {
                    // Si el FK ya existe (o difiere), no rompemos.
                }
            }
        });

        // Backfill order_source:
        // - If it came from the web cart, `client_id` is present.
        // - Otherwise it is treated as walk-in/admin-created for this module.
        DB::statement("UPDATE sales SET order_source='web_cart' WHERE client_id IS NOT NULL");
        DB::statement("UPDATE sales SET order_source='walk_in' WHERE client_id IS NULL");
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'seller_admin_id')) {
                try {
                    $table->dropForeign(['seller_admin_id']);
                } catch (Throwable $e) {
                    // ignore
                }
            }

            $columns = ['buyer_name', 'buyer_email', 'order_source', 'seller_admin_id'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('sales', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
