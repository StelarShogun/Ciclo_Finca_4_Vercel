<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // provider already added by 2026_03_18_000000_add_oauth_to_client_table
        if (!Schema::hasColumn('client_table', 'provider')) {
            Schema::table('client_table', function (Blueprint $table) {
                $table->string('provider')->default('local');
            });
        }
    }

    public function down()
    {
        // Only drop if this migration was the one that created the column
        // (handled by the other migration's down())
    }
};
