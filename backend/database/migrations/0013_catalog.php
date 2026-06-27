<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorite_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('product_id');
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('client_table')->cascadeOnDelete();
            $table->foreign('product_id')->references('product_id')->on('products')->cascadeOnDelete();
            $table->unique(['user_id', 'product_id'], 'favorite_products_user_product_unique');
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('base_product_id');
            $table->unsignedBigInteger('variant_product_id');
            $table->timestamps();

            $table->foreign('base_product_id')->references('product_id')->on('products')->cascadeOnDelete();
            $table->foreign('variant_product_id')->references('product_id')->on('products')->restrictOnDelete();
            $table->unique(['base_product_id', 'variant_product_id'], 'uq_product_variants_pair');
            $table->unique('variant_product_id', 'uq_product_variants_variant_single_base');
            $table->index('base_product_id', 'idx_product_variants_base');
            $table->index('variant_product_id', 'idx_product_variants_variant');
        });

        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id('review_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedTinyInteger('stars')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('product_id')->on('products')->cascadeOnDelete();
            $table->foreign('client_id')->references('user_id')->on('client_table')->cascadeOnDelete();
            $table->unique(['client_id', 'product_id'], 'product_reviews_client_product_unique');
            $table->index('product_id', 'product_reviews_product_idx');
        });

        Schema::create('catalog_product_search_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('query_normalized', 255)->nullable();
            $table->timestamp('created_at')->index();

            $table->index(['product_id', 'created_at']);
            $table->foreign('product_id')->references('product_id')->on('products')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_product_search_events');
        Schema::dropIfExists('product_reviews');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('favorite_products');
    }
};
