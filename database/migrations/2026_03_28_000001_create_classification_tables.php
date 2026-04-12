<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CF4-84 — Option A: dimensions per subcategory, values per dimension, product assignments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classification_dimensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')
                ->constrained('categories', 'category_id')
                ->cascadeOnDelete();
            /** Stable key within the subcategory (e.g. color, size, design). */
            $table->string('slug', 64);
            $table->string('label', 255);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['category_id', 'slug']);
        });

        Schema::create('classification_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classification_dimension_id')
                ->constrained('classification_dimensions')
                ->cascadeOnDelete();
            $table->string('value', 255);
            /** Normalized for duplicate checks (e.g. mb_strtolower + trim). */
            $table->string('normalized_value', 255);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['classification_dimension_id', 'normalized_value'], 'classification_values_dimension_normalized_unique');
        });

        Schema::create('classification_product', function (Blueprint $table) {
            $table->foreignId('product_id')
                ->constrained('products', 'product_id')
                ->cascadeOnDelete();
            $table->foreignId('classification_dimension_id')
                ->constrained('classification_dimensions')
                ->cascadeOnDelete();
            $table->foreignId('classification_value_id')
                ->constrained('classification_values')
                ->cascadeOnDelete();

            $table->primary(['product_id', 'classification_dimension_id'], 'classification_product_primary');
            $table->index('classification_value_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classification_product');
        Schema::dropIfExists('classification_values');
        Schema::dropIfExists('classification_dimensions');
    }
};
