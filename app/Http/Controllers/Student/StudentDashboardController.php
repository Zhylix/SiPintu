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

        $allowedRoles = $user->isAlumni() ? ['alumni', 'student', 'siswa'] : ['student', 'siswa'];

        $applications = Application::where('status', 'active')
            ->whereHas('roles', function ($query) use ($allowedRoles) {
                $query->whereIn('name', $allowedRoles);
            })
            ->get();

        $favoriteAppIds = $user->favoriteApplications()->pluck('applications.id')->toArray();
        $favoriteApps = $applications->whereIn('id', $favoriteAppIds);

        return view('student.dashboard', compact('user', 'applications', 'favoriteAppIds', 'favoriteApps'));
    }

    public function apps()
    {
        $user = Auth::user();

        $allowedRoles = $user->isAlumni() ? ['alumni', 'student', 'siswa'] : ['student', 'siswa'];

        $applications = Application::where('status', 'active')
            ->whereHas('roles', function ($query) use ($allowedRoles) {
                $query->whereIn('name', $allowedRoles);
            })
            ->get();

        $favoriteAppIds = $user->favoriteApplications()->pluck('applications.id')->toArray();

        return view('student.apps', compact('user', 'applications', 'favoriteAppIds'));
    }
}
