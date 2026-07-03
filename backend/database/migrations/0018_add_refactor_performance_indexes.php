<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index('is_featured', 'idx_products_is_featured');
            $table->index('created_at', 'idx_products_created_at');
            $table->index(['status', 'is_featured'], 'idx_products_status_featured');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->index('client_id', 'idx_sales_client_id');
            $table->index('order_source', 'idx_sales_order_source');
            $table->index('payment_method', 'idx_sales_payment_method');
            $table->index('created_at', 'idx_sales_created_at');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->index(['product_id', 'sale_id'], 'idx_sale_items_product_sale');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index('read_at', 'idx_notifications_read_at');
            $table->index('created_at', 'idx_notifications_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_notifications_read_at');
            $table->dropIndex('idx_notifications_created_at');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropIndex('idx_sale_items_product_sale');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('idx_sales_client_id');
            $table->dropIndex('idx_sales_order_source');
            $table->dropIndex('idx_sales_payment_method');
            $table->dropIndex('idx_sales_created_at');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_is_featured');
            $table->dropIndex('idx_products_created_at');
            $table->dropIndex('idx_products_status_featured');
        });
    }
};
