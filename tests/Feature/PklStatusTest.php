<?php

namespace Tests\Feature;

use App\Models\PklStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PklStatusTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dudi_can_update_pkl_status(): void
    {
        $dudi = User::where('user_type', 'dudi')->first();
        $student = User::where('user_type', 'student')->first();

        $pklStatus = PklStatus::create([
            'student_id' => $student->id,
            'status' => 'Pengajuan',
            'company_name' => 'PT Telkom Indonesia',
        ]);

        $response = $this->actingAs($dudi)->putJson("/api/pkl-status/{$pklStatus->id}", [
            'status' => 'Diterima',
            'notes' => 'Selamat, anda diterima magang.'
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('pkl_statuses', [
            'id' => $pklStatus->id,
            'status' => 'Diterima',
            'notes' => 'Selamat, anda diterima magang.',
            'updated_by' => $dudi->id
        ]);
    }

    public function test_admin_can_update_pkl_status(): void
    {
        $admin = User::where('user_type', 'admin')->first();
        $student = User::where('user_type', 'student')->first();

        $pklStatus = PklStatus::create([
            'student_id' => $student->id,
            'status' => 'Pengajuan',
            'company_name' => 'PT Telkom Indonesia',
        ]);

        $response = $this->actingAs($admin)->putJson("/api/pkl-status/{$pklStatus->id}", [
            'status' => 'Selesai PKL',
            'notes' => 'Disetujui oleh Admin.'
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('pkl_statuses', [
            'id' => $pklStatus->id,
            'status' => 'Selesai PKL',
        ]);
    }

    public function test_student_cannot_update_pkl_status(): void
    {
        $student = User::where('user_type', 'student')->first();

        $pklStatus = PklStatus::create([
            'student_id' => $student->id,
            'status' => 'Aktif Berjalan',
        ]);

        $response = $this->actingAs($student)->putJson("/api/pkl-status/{$pklStatus->id}", [
            'status' => 'Selesai PKL',
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
    }

    public function test_teacher_cannot_update_pkl_status(): void
    {
        $teacher = User::where('user_type', 'teacher')->first();
        $student = User::where('user_type', 'student')->first();

        $pklStatus = PklStatus::create([
            'student_id' => $student->id,
            'status' => 'Aktif Berjalan',
        ]);

        $response = $this->actingAs($teacher)->putJson("/api/pkl-status/{$pklStatus->id}", [
            'status' => 'Ditolak',
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
    }
}
