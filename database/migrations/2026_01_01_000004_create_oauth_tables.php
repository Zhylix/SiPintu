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
        Schema::create('oauth_auth_codes', function (Blueprint $table) {
            $table->string('id', 100)->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->text('redirect_uri');
            $table->text('scopes')->nullable();
            $table->string('code_challenge')->nullable();
            $table->string('code_challenge_method')->nullable();
            $table->boolean('revoked')->default(false);
            $table->dateTime('expires_at');
            $table->timestamps();
        });

        Schema::create('oauth_access_tokens', function (Blueprint $table) {
            $table->string('id', 100)->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('application_id')->constrained()->onDelete('cascade');
            $table->string('token', 500)->unique();
            $table->text('scopes')->nullable();
            $table->boolean('revoked')->default(false);
            $table->dateTime('expires_at');
            $table->timestamps();
        });

        Schema::create('oauth_refresh_tokens', function (Blueprint $table) {
            $table->string('id', 100)->primary();
            $table->string('access_token_id', 100);
            $table->string('token', 500)->unique();
            $table->boolean('revoked')->default(false);
            $table->dateTime('expires_at');
            $table->timestamps();

            $table->foreign('access_token_id')->references('id')->on('oauth_access_tokens')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_refresh_tokens');
        Schema::dropIfExists('oauth_access_tokens');
        Schema::dropIfExists('oauth_auth_codes');
    }
};
