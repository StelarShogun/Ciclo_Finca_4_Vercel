<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->timestamp('client_history_seen_at')
                ->nullable()
                ->after('ready_at')
                ->comment('When the client last acknowledged this completed sale in Historial.');
        });

        // Existing completed orders should not show as "new" after deploy.
        DB::table('sales')
            ->where('status', 'completed')
            ->whereNull('client_history_seen_at')
            ->update([
                'client_history_seen_at' => DB::raw('COALESCE(updated_at, sale_date, NOW())'),
            ]);
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('client_history_seen_at');
        });
    }
};
