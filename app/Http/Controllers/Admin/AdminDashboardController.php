<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\OAuthAccessToken;
use App\Models\SyncLog;
use App\Models\User;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users' => User::count(),
            'students_count' => User::where('role', 'student')->count(),
            'teachers_count' => User::where('role', 'teacher')->count(),
            'dudi_count' => User::where('role', 'dudi')->count(),
            'admin_count' => User::where('role', 'admin')->count(),
            'applications_count' => Application::count(),
            'active_apps_count' => Application::where('status', 'active')->count(),
            'sso_tokens_count' => OAuthAccessToken::where('revoked', false)->count(),
        ];

        $latestAuditLogs = AuditLog::with('user')->latest()->limit(10)->get();
        $latestSync = SyncLog::latest()->first();
        $applications = Application::with('roles')->get();

        return view('admin.dashboard', compact('stats', 'latestAuditLogs', 'latestSync', 'applications'));
    }
}
