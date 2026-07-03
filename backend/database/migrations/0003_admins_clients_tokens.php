<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('name');
            $table->string('first_surname');
            $table->string('second_surname')->nullable();
            $table->string('gmail')->unique();
            $table->string('password');
            $table->timestamp('last_access')->nullable();
            $table->timestamps();
        });

        Schema::create('client_table', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('name');
            $table->string('first_surname');
            $table->string('second_surname')->nullable();
            $table->string('gmail')->unique();
            $table->string('password');
            $table->string('verification_code', 6)->nullable();
            $table->timestamp('verification_code_expires_at')->nullable();
            $table->boolean('email_verified')->default(false);
            $table->boolean('active')->default(true);
            $table->string('provider')->default('local');
            $table->string('provider_id')->nullable();
            $table->string('avatar_url')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('client_password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_password_reset_tokens');
        Schema::dropIfExists('client_table');
        Schema::dropIfExists('admins');
    }
};
