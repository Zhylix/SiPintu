<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationCategory;
use Illuminate\Support\Facades\Auth;

class TeacherDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $applications = Application::with('category')
            ->where('status', 'active')
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['teacher', 'guru']);
            })
            ->get();

        $categories = ApplicationCategory::where('is_active', true)
            ->orderBy('display_order')
            ->get();

        $favoriteAppIds = $user->favoriteApplications()->pluck('applications.id')->toArray();
        $favoriteApps = $applications->whereIn('id', $favoriteAppIds);

        $stats = [
            'total_apps' => $applications->count(),
            'favorite_apps' => count($favoriteAppIds),
        ];

        return view('teacher.dashboard', compact('user', 'applications', 'categories', 'favoriteAppIds', 'favoriteApps', 'stats'));
    }

    public function apps()
    {
        $user = Auth::user();
        $applications = Application::with('category')
            ->where('status', 'active')
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['teacher', 'guru']);
            })
            ->get();

        $categories = ApplicationCategory::where('is_active', true)
            ->orderBy('display_order')
            ->get();

        $favoriteAppIds = $user->favoriteApplications()->pluck('applications.id')->toArray();

        return view('teacher.apps', compact('user', 'applications', 'categories', 'favoriteAppIds'));
    }
}
