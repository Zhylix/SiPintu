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
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
            $table->index('sent_at');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['user_id', 'created_at']);
            $table->index(['activity', 'created_at']);
        });

        Schema::table('security_logs', function (Blueprint $table) {
            $table->index(['event_type', 'created_at']);
            $table->index(['ip_address', 'created_at']);
        });

        Schema::table('blocked_ips', function (Blueprint $table) {
            $table->index(['is_active', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropIndex(['sent_at']);
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'created_at']);
            $table->dropIndex(['activity', 'created_at']);
        });

        Schema::table('security_logs', function (Blueprint $table) {
            $table->dropIndex(['event_type', 'created_at']);
            $table->dropIndex(['ip_address', 'created_at']);
        });

        Schema::table('blocked_ips', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'expires_at']);
        });
    }
};
