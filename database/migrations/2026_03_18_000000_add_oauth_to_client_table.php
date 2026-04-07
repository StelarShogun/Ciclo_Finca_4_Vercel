<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('client_table', function (Blueprint $table) {
            $table->string('provider')->default('local')->after('email_verified');
            $table->string('provider_id')->nullable()->after('provider');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_table', function (Blueprint $table) {
            $table->dropColumn(['provider', 'provider_id']);
        });
    }
};
