<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = $user->getAccessibleApplicationsQuery();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category_id', $request->category);
        }

        $applications = $query->get();

        $favoriteAppIds = $user->favoriteApplications()->pluck('applications.id')->toArray();
        $favoriteApps = $applications->whereIn('id', $favoriteAppIds);

        $stats = [
            'total_apps' => $user->getAccessibleApplications()->count(),
            'favorite_apps' => count($favoriteAppIds),
        ];

        return view('teacher.dashboard', compact('user', 'applications', 'favoriteAppIds', 'favoriteApps', 'stats'));
    }

    public function apps(Request $request)
    {
        $user = Auth::user();

        $query = $user->getAccessibleApplicationsQuery();

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category_id', $request->category);
        }

        $applications = $query->get();

        $favoriteAppIds = $user->favoriteApplications()->pluck('applications.id')->toArray();

        return view('teacher.apps', compact('user', 'applications', 'favoriteAppIds'));
    }
}
