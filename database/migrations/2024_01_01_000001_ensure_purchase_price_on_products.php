<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ensures the products table has a purchase_price column.
 * If your products table already has this column, this migration
 * will skip it safely via hasColumn().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'purchase_price')) {
                $table->decimal('purchase_price', 12, 2)->default(0)->after('sale_price');
            }
        });
    }

    public function down(): void
    {
        // Intentionally left empty — do not drop purchase_price,
        // it may be used by the rest of the system.
    }
};
