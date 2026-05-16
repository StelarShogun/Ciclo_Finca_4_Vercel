<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->date('estimated_delivery_date')->nullable()->change();
        });

        if (Schema::hasTable('app_settings')) {
            DB::table('app_settings')->updateOrInsert(
                ['key' => 'supplier_order_default_delivery_days'],
                ['value' => '7']
            );
        }
    }

    public function down(): void
    {
        DB::table('orders')
            ->whereNull('estimated_delivery_date')
            ->update(['estimated_delivery_date' => now()->addDays(7)->toDateString()]);

        Schema::table('orders', function (Blueprint $table) {
            $table->date('estimated_delivery_date')->nullable(false)->change();
        });

        if (Schema::hasTable('app_settings')) {
            DB::table('app_settings')
                ->where('key', 'supplier_order_default_delivery_days')
                ->delete();
        }
    }
};
