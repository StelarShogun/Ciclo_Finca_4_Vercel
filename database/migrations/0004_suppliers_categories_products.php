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
            $table->string('name', 150);
            $table->string('primary_contact', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('address')->nullable();
            $table->integer('delivery_time')->default(0);
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->index('name', 'idx_supplier_name');
            $table->index('email', 'idx_supplier_email');
            $table->index('status', 'idx_supplier_status');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE suppliers ADD CONSTRAINT chk_rating CHECK (rating >= 0.00 AND rating <= 5.00)');
            DB::statement('ALTER TABLE suppliers ADD CONSTRAINT chk_delivery_time CHECK (delivery_time >= 0)');
            try {
                DB::statement('ALTER TABLE suppliers ADD FULLTEXT KEY ft_suppliers_name (`name`)');
            } catch (Throwable) {
                // optional
            }
        }

        Schema::create('categories', function (Blueprint $table) {
            $table->id('category_id');
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_category_id')->nullable();
            $table->timestamps();
            $table->index('name', 'categorias_nombre_index');
            $table->index('parent_category_id', 'categorias_categoria_padre_id_index');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreign('parent_category_id')
                ->references('category_id')->on('categories')
                ->nullOnDelete();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id('product_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('name', 200);
            $table->string('sku', 64)->nullable()->unique('products_sku_unique');
            $table->text('description')->nullable();
            $table->string('image', 255)->nullable();
            $table->json('images')->nullable();
            $table->decimal('sale_price', 10, 2)->default(0);
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->integer('stock_current')->default(0);
            $table->integer('stock_minimum')->default(0);
            $table->enum('status', ['active', 'inactive', 'out_of_stock', 'discontinued'])->default('active');
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->foreign('category_id')->references('category_id')->on('categories')->nullOnDelete();
            $table->foreign('supplier_id')->references('supplier_id')->on('suppliers')->nullOnDelete();
            $table->index('name', 'productos_nombre_index');
            $table->index('category_id', 'productos_categoria_id_index');
            $table->index('supplier_id', 'productos_supplier_id_index');
            $table->index('status', 'productos_estado_index');
            $table->index('stock_current', 'productos_stock_actual_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('suppliers');
    }
};
