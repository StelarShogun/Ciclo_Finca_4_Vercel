<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('favorite_products')) {
            Schema::create('favorite_products', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('product_id');
                $table->foreign('user_id')->references('user_id')->on('client_table')->onDelete('cascade');
                $table->foreign('product_id')->references('product_id')->on('products')->onDelete('cascade');
                $table->unique(['user_id', 'product_id'], 'favorite_products_user_product_unique');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorite_products');
    }
};
