<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_notification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->unsignedBigInteger('client_id')->nullable();
            $table->string('channel', 20);
            $table->string('status', 20);
            $table->string('reason', 180);
            $table->timestamp('cancelled_at');
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('sale_id')->references('sale_id')->on('sales')->cascadeOnDelete();
            $table->foreign('client_id')->references('user_id')->on('client_table')->nullOnDelete();
            $table->index(['sale_id', 'channel', 'status'], 'order_notification_logs_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_notification_logs');
    }
};
