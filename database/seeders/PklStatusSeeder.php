<?php

namespace Database\Seeders;

use App\Models\PklStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class PklStatusSeeder extends Seeder
{
    public function run(): void
    {
        $students = User::where('user_type', 'student')
            ->orWhereHas('roles', function ($query) {
                $query->where('slug', 'student');
            })
            ->get();

        $dudiUser = User::where('user_type', 'dudi')->first();

        $statuses = ['Aktif Berjalan', 'Diterima', 'Menunggu Konfirmasi', 'Pengajuan', 'Dievaluasi', 'Selesai PKL'];

        foreach ($students as $index => $student) {
            $statusChoice = $statuses[$index % count($statuses)];

            PklStatus::firstOrCreate(
                ['student_id' => $student->id],
                [
                    'company_name' => 'PT Telekomunikasi Indonesia / Technopark',
                    'division' => 'Software & Network Engineering',
                    'mentor_name' => 'Bpk. Ahmad Fauzi, M.Kom',
                    'dudi_supervisor' => 'Ir. Hendra Wijaya',
                    'status' => $statusChoice,
                    'notes' => 'Status otomatis dikonfirmasi oleh Sistem & Pembimbing DUDI.',
                    'start_date' => '2026-07-01',
                    'end_date' => '2026-12-31',
                    'updated_by' => $dudiUser?->id,
                ]
            );
        }
    }
}
