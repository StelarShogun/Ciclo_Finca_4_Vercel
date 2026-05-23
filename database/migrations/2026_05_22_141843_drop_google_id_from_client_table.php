<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_table', function (Blueprint $table) {
            $table->dropColumn('google_id');
        });
    }

    public function down(): void
    {
        Schema::table('client_table', function (Blueprint $table) {
            $table->string('google_id')->nullable()->after('provider_id');
        });
    }
};
