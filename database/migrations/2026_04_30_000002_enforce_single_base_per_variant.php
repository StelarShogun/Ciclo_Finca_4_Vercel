<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            // Enforce: a variant can be linked to only one base product.
            $table->unique('variant_product_id', 'uq_product_variants_variant_single_base');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique('uq_product_variants_variant_single_base');
        });
    }
};

