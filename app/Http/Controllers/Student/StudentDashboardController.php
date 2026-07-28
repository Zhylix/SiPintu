<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Get applications accessible to student role
        $applications = Application::where('status', 'active')
            ->whereHas('roles', function ($query) {
                $query->whereIn('slug', ['student', 'siswa']);
            })
            ->get();

        // Simulated Student PKL / Academic Stats & Status
        $pklInfo = [
            'status' => 'Aktif Berjalan',
            'company_name' => 'PT Telekomunikasi Indonesia / Technopark',
            'mentor_name' => 'Bpk. Ahmad Fauzi, M.Kom',
            'dudi_supervisor' => 'Ir. Hendra Wijaya',
            'start_date' => '01 Juli 2026',
            'end_date' => '31 Desember 2026',
            'attendance_count' => 18,
            'logbook_count' => 18,
            'evaluation_score' => 92.5,
        ];

        return view('student.dashboard', compact('user', 'applications', 'pklInfo'));
    }

    public function pkl()
    {
        $user = Auth::user();

        $pklStatus = \App\Models\PklStatus::firstOrCreate(
            ['student_id' => $user->id],
            [
                'company_name' => 'PT Telekomunikasi Indonesia / Technopark',
                'division' => 'Software & Network Engineering',
                'mentor_name' => 'Bpk. Ahmad Fauzi, M.Kom',
                'dudi_supervisor' => 'Ir. Hendra Wijaya',
                'status' => 'Aktif Berjalan',
                'notes' => 'Status aktif dalam masa pelaksanaan PKL industri.',
            ]
        );

        $pklDetails = [
            'id' => $pklStatus->id,
            'company_name' => $pklStatus->company_name,
            'address' => 'Jl. Jendral Sudirman No. 45, Jakarta Pusat',
            'division' => $pklStatus->division,
            'mentor' => $pklStatus->mentor_name,
            'dudi_supervisor' => $pklStatus->dudi_supervisor,
            'status' => $pklStatus->status,
            'notes' => $pklStatus->notes,
            'updated_by' => $pklStatus->updater?->name ?? 'DUDI / Admin',
            'updated_at' => $pklStatus->updated_at,
            'logs' => [
                ['date' => '28/07/2026', 'activity' => 'Pengembangan API Gateway & Integrasi SSO Laravel 13', 'status' => 'Disetujui'],
                ['date' => '27/07/2026', 'activity' => 'Setup Redis Cache Caching & Benchmarking Database', 'status' => 'Disetujui'],
                ['date' => '26/07/2026', 'activity' => 'Integrasi API SIJUNA dan Synchronize Data Siswa', 'status' => 'Disetujui'],
                ['date' => '25/07/2026', 'activity' => 'Pengujian Endpoint OAuth2 / OIDC Server', 'status' => 'Disetujui'],
            ]
        ];

        return view('student.pkl', compact('user', 'pklDetails', 'pklStatus'));
    }

    public function apps()
    {
        $user = Auth::user();
        $applications = Application::where('status', 'active')
            ->whereHas('roles', function ($query) {
                $query->whereIn('slug', ['student', 'siswa']);
            })
            ->get();

        return view('student.apps', compact('user', 'applications'));
    }
}
