<?php

namespace App\Http\Controllers\Dudi;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class DudiDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Applications accessible to DUDI
        $applications = Application::where('status', 'active')
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['dudi']);
            })
            ->get();

        return view('dudi.dashboard', compact('user', 'applications'));
    }

    public function evaluations()
    {
        $user = Auth::user();

        $evaluations = [
            ['student_name' => 'Ahmad Rizky', 'division' => 'Network & Software', 'period' => '2026', 'score' => 94, 'status' => 'Selesai'],
            ['student_name' => 'Budi Pratama', 'division' => 'Backend Development', 'period' => '2026', 'score' => 92, 'status' => 'Selesai'],
        ];

        return view('dudi.evaluations', compact('user', 'evaluations'));
    }

    public function apps()
    {
        $user = Auth::user();
        $applications = Application::where('status', 'active')
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['dudi']);
            })
            ->get();

        return view('dudi.apps', compact('user', 'applications'));
    }
}
