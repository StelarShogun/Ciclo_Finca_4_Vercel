<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timeline_order_state', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('num_order');
            $table->unsignedBigInteger('user_id');
            $table->string('state', 32);
            $table->string('reason', 500)->nullable();
            $table->timestamp('changed_at')->useCurrent();

            $table->foreign('num_order')
                ->references('num_order')
                ->on('orders')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('user_id')
                ->on('admins')
                ->restrictOnDelete();

            $table->index('num_order', 'idx_timeline_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('timeline_order_state');
    }
};
