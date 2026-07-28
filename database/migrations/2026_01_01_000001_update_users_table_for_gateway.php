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
        Schema::table('users', function (Blueprint $table) {
            $table->string('external_id')->nullable()->unique()->after('id')->comment('Identifier for SIJUNA students');
            $table->string('username')->nullable()->unique()->after('email');
            $table->string('user_type')->default('student')->after('password')->comment('student, teacher, dudi, admin');
            $table->string('phone')->nullable()->after('user_type');
            $table->string('avatar')->nullable()->after('phone');
            $table->string('status')->default('active')->after('avatar')->comment('active, inactive, suspended');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['external_id', 'username', 'user_type', 'phone', 'avatar', 'status']);
        });
    }
};
