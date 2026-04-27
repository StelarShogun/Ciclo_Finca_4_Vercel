<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_user_id')->nullable();
            $table->string('admin_email_snapshot')->nullable();
            $table->string('action_type', 64);
            $table->string('module', 64);
            $table->string('description', 255);
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('admin_user_id')
                ->references('user_id')
                ->on('admins')
                ->nullOnDelete();

            $table->index('created_at');
            $table->index(['admin_user_id', 'created_at'], 'audit_logs_admin_created_at_idx');
            $table->index(['action_type', 'module', 'created_at'], 'audit_logs_action_module_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
