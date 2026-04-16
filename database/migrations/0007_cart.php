<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->foreign('client_id')->references('user_id')->on('client_table')->cascadeOnDelete();
            $table->foreign('product_id')->references('product_id')->on('products')->cascadeOnDelete();
            $table->unique(['client_id', 'product_id'], 'cart_items_client_id_product_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
