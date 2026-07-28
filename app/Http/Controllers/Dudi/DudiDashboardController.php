<?php

namespace App\Http\Controllers\Dudi;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DudiDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Applications accessible to DUDI
        $applications = Application::where('status', 'active')
            ->whereHas('roles', function ($query) {
                $query->whereIn('slug', ['dudi']);
            })
            ->get();

        $stats = [
            'active_interns' => 8,
            'completed_interns' => 12,
            'attendance_rate' => '98.5%',
            'pending_evaluations' => 2,
        ];

        $interns = User::where('user_type', 'student')->take(4)->get();

        return view('dudi.dashboard', compact('user', 'applications', 'stats', 'interns'));
    }

    public function interns()
    {
        $user = Auth::user();
        $interns = User::where('user_type', 'student')
            ->orWhereHas('roles', function ($q) {
                $q->where('slug', 'student');
            })
            ->with('pklStatus')
            ->paginate(10);

        $allowedStatuses = \App\Models\PklStatus::allowedStatuses();

        return view('dudi.interns', compact('user', 'interns', 'allowedStatuses'));
    }

    public function evaluations()
    {
        $user = Auth::user();

        $evaluations = [
            ['student_name' => 'Ahmad Rizky', 'division' => 'Network & Software', 'period' => 'Juli - Des 2026', 'score' => 94, 'status' => 'Sudah Dinilai'],
            ['student_name' => 'Budi Pratama', 'division' => 'Backend Development', 'period' => 'Juli - Des 2026', 'score' => 92, 'status' => 'Sudah Dinilai'],
            ['student_name' => 'Eka Saputra', 'division' => 'UI/UX Design', 'period' => 'Juli - Des 2026', 'score' => null, 'status' => 'Perlu Penilaian'],
        ];

        return view('dudi.evaluations', compact('user', 'evaluations'));
    }

    public function apps()
    {
        $user = Auth::user();
        $applications = Application::where('status', 'active')
            ->whereHas('roles', function ($query) {
                $query->whereIn('slug', ['dudi']);
            })
            ->get();

        return view('dudi.apps', compact('user', 'applications'));
    }
}
