<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the product_purchase_price_histories table.
 *
 * This table records every confirmed change to a product's purchase_price,
 * including the source XML file name, the admin who approved it, and the
 * deviation threshold that was configured at the time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_purchase_price_histories', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')
                ->references('product_id')
                ->on('products')
                ->cascadeOnDelete();

            // Prices at the moment of the change.
            $table->decimal('previous_price', 12, 2);
            $table->decimal('new_price', 12, 2);

            // Calculated deviation.
            $table->decimal('difference_amount', 12, 2)->comment('new_price - previous_price');
            $table->decimal('difference_percentage', 8, 4)->comment('Signed percentage relative to previous_price');

            // Context of the analysis.
            $table->decimal('threshold_percentage', 8, 4)->default(10.00)
                ->comment('Threshold configured when the XML was analysed');

            // Origin information.
            $table->string('source', 50)->default('xml_upload')
                ->comment('Identifies the origin: xml_upload, manual, etc.');
            $table->string('xml_file_name', 255)->nullable()
                ->comment('Original file name of the XML uploaded by the supplier');

            // Free-text reason the admin may provide.
            $table->text('reason')->nullable();

            // Who confirmed the change.
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->foreign('changed_by')
                ->references('user_id')
                ->on('admins')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_purchase_price_histories');
    }
};
