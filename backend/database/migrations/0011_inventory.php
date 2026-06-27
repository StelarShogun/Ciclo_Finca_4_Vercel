<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('type', 20);
            $table->string('origin', 60);
            $table->unsignedInteger('quantity');
            $table->integer('stock_before');
            $table->integer('stock_after');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('product_id')->on('products')->restrictOnDelete();
            $table->foreign('user_id')->references('user_id')->on('admins')->nullOnDelete();
            $table->index(['product_id', 'created_at'], 'idx_inv_mov_product_date');
            $table->index('type', 'idx_inv_mov_type');
            $table->index('origin', 'idx_inv_mov_origin');
            $table->index(['origin', 'reference_id'], 'idx_inv_mov_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
