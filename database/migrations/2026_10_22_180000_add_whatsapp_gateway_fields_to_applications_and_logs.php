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
        Schema::table('applications', function (Blueprint $table) {
            $table->string('wa_session_id')->nullable()->index()->after('total_api_requests');
            $table->string('wa_phone_number')->nullable()->after('wa_session_id');
            $table->string('wa_status')->default('disconnected')->index()->after('wa_phone_number');
            $table->boolean('wa_auto_fallback')->default(true)->after('wa_status');
        });

        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->foreignId('application_id')->nullable()->after('announcement_id')->constrained('applications')->nullOnDelete();
            $table->string('session_id')->nullable()->index()->after('application_id');
            $table->string('type')->default('announcement')->index()->after('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->dropForeign(['application_id']);
            $table->dropColumn(['application_id', 'session_id', 'type']);
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['wa_session_id', 'wa_phone_number', 'wa_status', 'wa_auto_fallback']);
        });
    }
};
