<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('received_at')
                ->nullable()
                ->default(null)
                ->after('delivered_at')
                ->comment('Fecha en que se registró la recepción de mercancía vía receiveOrder().');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('received_at');
        });
    }
};
