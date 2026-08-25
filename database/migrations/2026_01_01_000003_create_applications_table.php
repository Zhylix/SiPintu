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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('application_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('base_url');
            $table->string('icon')->nullable();
            $table->string('client_id')->unique();
            $table->string('client_secret');
            $table->text('redirect_uri');
            $table->text('logout_uri')->nullable();
            $table->string('scopes')->default('openid profile email');
            $table->string('status')->default('active')->index()->comment('active, maintenance, inactive');
            $table->string('health_check_url')->nullable();
            $table->string('last_health_status')->nullable()->index()->comment('online, offline, warning');
            $table->integer('last_health_latency_ms')->nullable();
            $table->text('last_health_message')->nullable();
            $table->timestamp('last_health_check_at')->nullable();
            $table->timestamp('last_connected_at')->nullable();
            $table->string('last_connected_ip', 45)->nullable();
            $table->string('connection_status')->default('never_connected')->index();
            $table->unsignedBigInteger('total_api_requests')->default(0);
            $table->timestamps();
        });

        Schema::create('application_role', function (Blueprint $table) {
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->foreignId('role_id')->constrained()->onDelete('cascade');
            $table->primary(['application_id', 'role_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_role');
        Schema::dropIfExists('applications');
    }
};
