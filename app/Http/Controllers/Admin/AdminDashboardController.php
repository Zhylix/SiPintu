<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\OAuthAccessToken;
use App\Models\SyncLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $stats = Cache::remember('admin_dashboard_stats', 30, function () {
            return [
                'total_users' => User::count(),
                'students_count' => User::where('role', 'student')->count(),
                'alumni_count' => User::where('role', 'alumni')->count(),
                'teachers_count' => User::where('role', 'teacher')->count(),
                'dudi_count' => User::where('role', 'dudi')->count(),
                'admin_count' => User::where('role', 'admin')->count(),
                'applications_count' => Application::count(),
                'active_apps_count' => Application::where('status', 'active')->count(),
                'sso_tokens_count' => OAuthAccessToken::where('revoked', false)->count(),
            ];
        });

        $latestAuditLogs = AuditLog::with('user')->latest()->limit(10)->get();
        $latestSync = SyncLog::latest()->first();

        // Applications registered in Gateway for admin management view
        $registeredApps = Application::with(['roles'])->get();

        // Active applications for user catalog view
        $applications = Application::where('status', 'active')->get();

        $favoriteAppIds = $user ? $user->favoriteApplications()->pluck('applications.id')->toArray() : [];
        $favoriteApps = $applications->whereIn('id', $favoriteAppIds);

        return view('admin.dashboard', compact(
            'user',
            'stats',
            'latestAuditLogs',
            'latestSync',
            'registeredApps',
            'applications',
            'favoriteAppIds',
            'favoriteApps'
        ));
    }

    public function apps()
    {
        $user = Auth::user();

        $applications = Application::where('status', 'active')->get();
        $favoriteAppIds = $user ? $user->favoriteApplications()->pluck('applications.id')->toArray() : [];

        return view('admin.apps', compact('user', 'applications', 'favoriteAppIds'));
    }
}
