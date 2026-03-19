<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('client_table', function (Blueprint $table) {
            $table->string('provider')->default('local');
            // valores: 'local' | 'google'
        });
    }

    public function down()
    {
        Schema::table('client_table', function (Blueprint $table) {
            $table->dropColumn('provider');
        });
    }
};
