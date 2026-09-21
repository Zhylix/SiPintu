<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Support\Facades\Auth;

class TeacherDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $applications = Application::where('status', 'active')
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['teacher', 'guru']);
            })
            ->get();

        $favoriteAppIds = $user->favoriteApplications()->pluck('applications.id')->toArray();
        $favoriteApps = $applications->whereIn('id', $favoriteAppIds);

        $stats = [
            'total_apps' => $applications->count(),
            'favorite_apps' => count($favoriteAppIds),
        ];

        return view('teacher.dashboard', compact('user', 'applications', 'favoriteAppIds', 'favoriteApps', 'stats'));
    }

    public function apps()
    {
        $user = Auth::user();
        $applications = Application::where('status', 'active')
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['teacher', 'guru']);
            })
            ->get();

        $favoriteAppIds = $user->favoriteApplications()->pluck('applications.id')->toArray();

        return view('teacher.apps', compact('user', 'applications', 'favoriteAppIds'));
    }
}
