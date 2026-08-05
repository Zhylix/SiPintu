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
            $table->timestamp('last_connected_at')->nullable()->after('last_health_check_at');
            $table->string('last_connected_ip')->nullable()->after('last_connected_at');
            $table->string('connection_status')->default('never_connected')->after('last_connected_ip')->comment('connected, disconnected, never_connected');
            $table->unsignedBigInteger('total_api_requests')->default(0)->after('connection_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['last_connected_at', 'last_connected_ip', 'connection_status', 'total_api_requests']);
        });
    }
};
