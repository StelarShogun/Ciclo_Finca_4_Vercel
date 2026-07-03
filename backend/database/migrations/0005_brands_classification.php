<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });

        Schema::create('products_brand', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('brand_id');
            $table->primary(['product_id', 'brand_id']);
            $table->foreign('product_id')->references('product_id')->on('products')->cascadeOnDelete();
            $table->foreign('brand_id')->references('id')->on('brands')->cascadeOnDelete();
        });

        Schema::create('classification_dimensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories', 'category_id')->cascadeOnDelete();
            $table->string('slug', 64);
            $table->string('label', 255);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['category_id', 'slug']);
        });

        Schema::create('classification_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classification_dimension_id')
                ->constrained('classification_dimensions')
                ->cascadeOnDelete();
            $table->string('value', 255);
            $table->string('normalized_value', 255);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['classification_dimension_id', 'normalized_value'], 'classification_values_dimension_normalized_unique');
        });

        Schema::create('classification_product', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained('products', 'product_id')->cascadeOnDelete();
            $table->foreignId('classification_dimension_id')
                ->constrained('classification_dimensions')
                ->cascadeOnDelete();
            $table->foreignId('classification_value_id')
                ->constrained('classification_values')
                ->cascadeOnDelete();
            $table->primary(['product_id', 'classification_dimension_id'], 'classification_product_primary');
            $table->index('classification_value_id', 'classification_product_classification_value_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classification_product');
        Schema::dropIfExists('classification_values');
        Schema::dropIfExists('classification_dimensions');
        Schema::dropIfExists('products_brand');
        Schema::dropIfExists('brands');
    }
};
