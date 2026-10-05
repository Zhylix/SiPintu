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
        Schema::create('error_logs', function (Blueprint $table) {
            $table->id();
            $table->string('incident_code', 36)->unique()->index();
            $table->integer('status_code')->default(500)->index();
            $table->string('error_type')->index();
            $table->text('message');
            $table->string('file', 500)->nullable();
            $table->integer('line')->nullable();
            $table->longText('trace')->nullable();
            $table->text('url');
            $table->string('route_name')->nullable()->index();
            $table->string('method', 10)->default('GET')->index();
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('user_role', 50)->nullable()->index();
            $table->json('request_data')->nullable();
            $table->json('headers')->nullable();
            $table->string('status', 20)->default('unresolved')->index(); // unresolved, resolved, ignored
            $table->integer('occurrence_count')->default(1);
            $table->string('fingerprint', 64)->nullable()->index();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('resolution_notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['status_code', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('error_logs');
    }
};
