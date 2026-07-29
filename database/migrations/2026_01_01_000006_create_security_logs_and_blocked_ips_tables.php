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
        Schema::create('security_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('target_identifier')->nullable()->comment('Username/Email/NIS attempted');
            $table->string('event_type')->index()->comment('e.g. login_failed, brute_force_detected, unauthorized_access, account_locked');
            $table->string('severity')->default('warning')->comment('info, warning, critical');
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('blocked_ips', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45)->unique();
            $table->string('reason')->nullable();
            $table->foreignId('blocked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('expires_at')->nullable()->comment('Null means permanently blocked');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blocked_ips');
        Schema::dropIfExists('security_logs');
    }
};
