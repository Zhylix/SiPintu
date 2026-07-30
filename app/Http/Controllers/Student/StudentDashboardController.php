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

        $applications = Application::with('category')
            ->where('status', 'active')
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['student', 'siswa']);
            })
            ->get();

        $categories = \App\Models\ApplicationCategory::where('is_active', true)
            ->orderBy('display_order')
            ->get();

        $favoriteAppIds = $user->favoriteApplications()->pluck('applications.id')->toArray();
        $favoriteApps = $applications->whereIn('id', $favoriteAppIds);

        return view('student.dashboard', compact('user', 'applications', 'categories', 'favoriteAppIds', 'favoriteApps'));
    }

    public function apps()
    {
        $user = Auth::user();
        $applications = Application::with('category')
            ->where('status', 'active')
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['student', 'siswa']);
            })
            ->get();

        $categories = \App\Models\ApplicationCategory::where('is_active', true)
            ->orderBy('display_order')
            ->get();

        $favoriteAppIds = $user->favoriteApplications()->pluck('applications.id')->toArray();

        return view('student.apps', compact('user', 'applications', 'categories', 'favoriteAppIds'));
    }
}
