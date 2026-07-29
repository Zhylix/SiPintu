<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Support\Facades\Auth;

class StudentDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Get applications accessible to student role
        $applications = Application::where('status', 'active')
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['student', 'siswa']);
            })
            ->get();

        return view('student.dashboard', compact('user', 'applications'));
    }

    public function apps()
    {
        $user = Auth::user();
        $applications = Application::where('status', 'active')
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['student', 'siswa']);
            })
            ->get();

        return view('student.apps', compact('user', 'applications'));
    }
}
