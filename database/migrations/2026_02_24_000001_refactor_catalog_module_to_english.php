<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'mysql') {
            return;
        }

        // Drop foreign keys before renaming (condicional - pueden no existir)
        try {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropForeign(['categoria_id']);
            });
        } catch (\Exception $e) {
            // FK ya no existe
        }

        try {
            Schema::table('productos', function (Blueprint $table) {
                $table->dropForeign(['supplier_id']);
            });
        } catch (\Exception $e) {
            // FK ya no existe
        }

        try {
            Schema::table('categorias', function (Blueprint $table) {
                $table->dropForeign(['categoria_padre_id']);
            });
        } catch (\Exception $e) {
            // FK ya no existe
        }

        try {
            Schema::table('sale_items', function (Blueprint $table) {
                $table->dropForeign(['producto_id']);
            });
        } catch (\Exception $e) {
            // FK ya no existe
        }

        // Rename categorias -> categories
        Schema::rename('categorias', 'categories');

        Schema::table('categories', function (Blueprint $table) {
            $table->renameColumn('categoria_id', 'category_id');
            $table->renameColumn('nombre', 'name');
            $table->renameColumn('descripcion', 'description');
            $table->renameColumn('categoria_padre_id', 'parent_category_id');
            $table->renameColumn('fecha_creacion', 'created_at');
            $table->renameColumn('fecha_modificacion', 'updated_at');
        });

        // Rename productos -> products
        Schema::rename('productos', 'products');

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('producto_id', 'product_id');
            $table->renameColumn('categoria_id', 'category_id');
            $table->renameColumn('nombre', 'name');
            $table->renameColumn('descripcion', 'description');
            $table->renameColumn('imagen', 'image');
            $table->renameColumn('precio_venta', 'sale_price');
            $table->renameColumn('precio_compra', 'purchase_price');
            $table->renameColumn('stock_actual', 'stock_current');
            $table->renameColumn('stock_minimo', 'stock_minimum');
            $table->renameColumn('estado', 'status');
            $table->renameColumn('fecha_creacion', 'created_at');
            $table->renameColumn('fecha_modificacion', 'updated_at');
        });

        // Update status enum and translate values
        DB::statement("ALTER TABLE products MODIFY COLUMN status ENUM('active', 'inactive', 'out_of_stock', 'discontinued') NOT NULL DEFAULT 'active'");
        DB::statement("UPDATE products SET status = CASE status
            WHEN 'activo' THEN 'active'
            WHEN 'inactivo' THEN 'inactive'
            WHEN 'agotado' THEN 'out_of_stock'
            WHEN 'descontinuado' THEN 'discontinued'
            ELSE status END");

        // Update sale_items legacy FK column
        Schema::table('sale_items', function (Blueprint $table) {
            $table->renameColumn('producto_id', 'product_id');
        });

        // Re-add foreign keys
        Schema::table('categories', function (Blueprint $table) {
            $table->foreign('parent_category_id')
                ->references('category_id')->on('categories')
                ->onDelete('set null');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('category_id')
                ->references('category_id')->on('categories')
                ->onDelete('set null');
            $table->foreign('supplier_id')
                ->references('supplier_id')->on('suppliers')
                ->onDelete('set null');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreign('product_id')
                ->references('product_id')->on('products')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver !== 'mysql') {
            return;
        }

        // Drop English foreign keys
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['supplier_id']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['parent_category_id']);
        });

        // Revert sale_items column
        Schema::table('sale_items', function (Blueprint $table) {
            $table->renameColumn('product_id', 'producto_id');
        });

        // Revert enum values (Spanish)
        DB::statement("UPDATE products SET status = CASE status
            WHEN 'active' THEN 'activo'
            WHEN 'inactive' THEN 'inactivo'
            WHEN 'out_of_stock' THEN 'agotado'
            WHEN 'discontinued' THEN 'descontinuado'
            ELSE status END");
        DB::statement("ALTER TABLE products MODIFY COLUMN status ENUM('activo', 'inactivo', 'agotado', 'descontinuado') NOT NULL DEFAULT 'activo'");

        // Revert products table/columns
        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('product_id', 'producto_id');
            $table->renameColumn('category_id', 'categoria_id');
            $table->renameColumn('name', 'nombre');
            $table->renameColumn('description', 'descripcion');
            $table->renameColumn('image', 'imagen');
            $table->renameColumn('sale_price', 'precio_venta');
            $table->renameColumn('purchase_price', 'precio_compra');
            $table->renameColumn('stock_current', 'stock_actual');
            $table->renameColumn('stock_minimum', 'stock_minimo');
            $table->renameColumn('status', 'estado');
            $table->renameColumn('created_at', 'fecha_creacion');
            $table->renameColumn('updated_at', 'fecha_modificacion');
        });

        Schema::rename('products', 'productos');

        // Revert categories table/columns
        Schema::table('categories', function (Blueprint $table) {
            $table->renameColumn('category_id', 'categoria_id');
            $table->renameColumn('name', 'nombre');
            $table->renameColumn('description', 'descripcion');
            $table->renameColumn('parent_category_id', 'categoria_padre_id');
            $table->renameColumn('created_at', 'fecha_creacion');
            $table->renameColumn('updated_at', 'fecha_modificacion');
        });

        Schema::rename('categories', 'categorias');

        // Re-add Spanish foreign keys
        Schema::table('categorias', function (Blueprint $table) {
            $table->foreign('categoria_padre_id')
                ->references('categoria_id')->on('categorias')
                ->onDelete('set null');
        });

        Schema::table('productos', function (Blueprint $table) {
            $table->foreign('categoria_id')
                ->references('categoria_id')->on('categorias')
                ->onDelete('set null');
            $table->foreign('supplier_id')
                ->references('supplier_id')->on('suppliers')
                ->onDelete('set null');
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreign('producto_id')
                ->references('producto_id')->on('productos')
                ->onDelete('cascade');
        });
    }
};
