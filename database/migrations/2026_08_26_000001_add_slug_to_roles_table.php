<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $rolesTable = $tableNames['roles'] ?? 'roles';

        if (Schema::hasTable($rolesTable) && ! Schema::hasColumn($rolesTable, 'slug')) {
            Schema::table($rolesTable, function (Blueprint $table) {
                $table->string('slug')->nullable()->after('name');
            });

            // Populate slug for existing roles
            $roles = DB::table($rolesTable)->get();
            foreach ($roles as $role) {
                DB::table($rolesTable)
                    ->where('id', $role->id)
                    ->update(['slug' => Str::slug($role->name)]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $rolesTable = $tableNames['roles'] ?? 'roles';

        if (Schema::hasTable($rolesTable) && Schema::hasColumn($rolesTable, 'slug')) {
            Schema::table($rolesTable, function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }
    }
};
