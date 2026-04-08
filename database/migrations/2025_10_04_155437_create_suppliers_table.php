<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id('supplier_id');

            // String fields with specific lengths
            $table->string('name', 150);
            $table->string('primary_contact', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();

            // Numeric fields with default values
            $table->integer('delivery_time')->default(0)->comment('Delivery time in days');
            $table->decimal('rating', 3, 2)->default(0.00)->comment('Rating from 0.00 to 5.00');

            // ENUM status field
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');

            // Custom timestamps
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // Indexes
            $table->index('name', 'idx_supplier_name');
            $table->index('email', 'idx_supplier_email');
            $table->index('status', 'idx_supplier_status');
        });

        // CHECK constraints (MySQL; SQLite no soporta ADD CONSTRAINT CHECK igual en tests :memory:)
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE suppliers ADD CONSTRAINT chk_rating CHECK (rating >= 0.00 AND rating <= 5.00)');
            DB::statement('ALTER TABLE suppliers ADD CONSTRAINT chk_delivery_time CHECK (delivery_time >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
