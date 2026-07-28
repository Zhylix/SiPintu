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
        Schema::create('pkl_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->string('company_name')->default('PT Telekomunikasi Indonesia / Technopark');
            $table->string('division')->default('Software & Network Engineering');
            $table->string('mentor_name')->default('Bpk. Ahmad Fauzi, M.Kom');
            $table->string('dudi_supervisor')->default('Ir. Hendra Wijaya');
            $table->string('status')->default('Aktif Berjalan');
            $table->text('notes')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pkl_statuses');
    }
};
