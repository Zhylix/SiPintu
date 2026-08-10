<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'wa_notify')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('wa_notify')->default(true)->after('avatar');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'wa_notify')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('wa_notify');
            });
        }
    }
};
