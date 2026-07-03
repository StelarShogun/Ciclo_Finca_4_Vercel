<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_purchase_price_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->decimal('previous_price', 12, 2);
            $table->decimal('new_price', 12, 2);
            $table->decimal('difference_amount', 12, 2);
            $table->decimal('difference_percentage', 8, 4);
            $table->decimal('threshold_percentage', 8, 4)->default(10.00);
            $table->string('source', 50)->default('xml_upload');
            $table->string('xml_file_name', 255)->nullable();
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamps();

            $table->foreign('product_id')->references('product_id')->on('products')->cascadeOnDelete();
            $table->foreign('changed_by')->references('user_id')->on('admins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_purchase_price_histories');
    }
};
