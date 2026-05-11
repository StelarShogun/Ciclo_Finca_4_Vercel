<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('base_product_id');
            $table->unsignedBigInteger('variant_product_id');
            $table->timestamps();

            $table->foreign('base_product_id')
                ->references('product_id')
                ->on('products')
                ->cascadeOnDelete();

            $table->foreign('variant_product_id')
                ->references('product_id')
                ->on('products')
                ->restrictOnDelete();

            $table->unique(['base_product_id', 'variant_product_id'], 'uq_product_variants_pair');
            $table->index('base_product_id', 'idx_product_variants_base');
            $table->index('variant_product_id', 'idx_product_variants_variant');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
