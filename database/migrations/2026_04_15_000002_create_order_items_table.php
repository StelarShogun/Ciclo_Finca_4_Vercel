<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_items')) {
            return;
        }

        Schema::create('order_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('order_num_order');
            $table->unsignedBigInteger('product_id');
            $table->string('name', 255);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('order_num_order')
                ->references('num_order')
                ->on('orders')
                ->onDelete('cascade');

            $table->foreign('product_id')
                ->references('product_id')
                ->on('products')
                ->onDelete('restrict');

            $table->index(['order_num_order'], 'idx_order_items_order');
            $table->index(['product_id'], 'idx_order_items_product');
            $table->index(['name'], 'idx_order_items_name');
        });

        // Backfill from orders.products JSON (best-effort; only on MySQL).
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }

        // Insert each JSON array element as a row; avoids application-level loops.
        DB::statement(<<<'SQL'
INSERT INTO order_items (order_num_order, product_id, name, quantity, unit_price, total, created_at, updated_at)
SELECT
  o.num_order,
  CAST(jt.product_id AS UNSIGNED) AS product_id,
  jt.name,
  CAST(jt.quantity AS UNSIGNED) AS quantity,
  CAST(jt.unit_price AS DECIMAL(12,2)) AS unit_price,
  CAST(jt.total AS DECIMAL(12,2)) AS total,
  NOW(),
  NOW()
FROM orders o
JOIN JSON_TABLE(
  o.products,
  '$[*]' COLUMNS (
    product_id INT PATH '$.product_id',
    name VARCHAR(255) PATH '$.name',
    quantity INT PATH '$.quantity',
    unit_price DECIMAL(12,2) PATH '$.unit_price',
    total DECIMAL(12,2) PATH '$.total'
  )
) AS jt
WHERE o.products IS NOT NULL
  AND JSON_LENGTH(o.products) > 0
SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
