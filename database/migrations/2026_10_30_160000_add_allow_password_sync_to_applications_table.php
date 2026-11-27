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
            if (! Schema::hasColumn('applications', 'allow_password_sync')) {
                $table->boolean('allow_password_sync')
                    ->default(false)
                    ->after('scopes')
                    ->comment('Apakah aplikasi ini diizinkan menerima sinkronisasi hash password pengguna');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            if (Schema::hasColumn('applications', 'allow_password_sync')) {
                $table->dropColumn('allow_password_sync');
            }
        });
    }
};
