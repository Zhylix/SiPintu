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
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'wa_notify')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('wa_notify')->default(true)->after('phone');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'wa_notify')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('wa_notify');
            });
        }
    }
};
