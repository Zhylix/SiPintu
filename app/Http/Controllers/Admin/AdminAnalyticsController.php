<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\OAuthAccessToken;
use App\Models\SyncLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $range = $request->query('range', '30'); // 7, 30, all
        $days = (int) $range > 0 ? (int) $range : 30;

        $startDate = now()->subDays($days);

        // 1. User Role Distribution
        $userDistribution = Cache::remember("analytics_user_dist_{$range}", 60, function () {
            $total = User::count();
            return [
                'total' => $total,
                'students' => [
                    'count' => User::where('role', 'student')->count(),
                    'percentage' => $total > 0 ? round((User::where('role', 'student')->count() / $total) * 100, 1) : 0,
                ],
                'teachers' => [
                    'count' => User::where('role', 'teacher')->count(),
                    'percentage' => $total > 0 ? round((User::where('role', 'teacher')->count() / $total) * 100, 1) : 0,
                ],
                'dudi' => [
                    'count' => User::where('role', 'dudi')->count(),
                    'percentage' => $total > 0 ? round((User::where('role', 'dudi')->count() / $total) * 100, 1) : 0,
                ],
                'admin' => [
                    'count' => User::where('role', 'admin')->count(),
                    'percentage' => $total > 0 ? round((User::where('role', 'admin')->count() / $total) * 100, 1) : 0,
                ],
            ];
        });

        // 2. SSO Token & Access Analytics
        $ssoMetrics = Cache::remember("analytics_sso_metrics_{$range}", 60, function () use ($startDate) {
            return [
                'total_issued_tokens' => OAuthAccessToken::count(),
                'active_tokens' => OAuthAccessToken::where('revoked', false)->where('expires_at', '>', now())->count(),
                'recent_tokens' => OAuthAccessToken::where('created_at', '>=', $startDate)->count(),
            ];
        });

        // 3. Top Most Accessed SSO Applications (calculated from OAuth tokens & Audit logs)
        $topApps = Cache::remember("analytics_top_apps_{$range}", 60, function () {
            return Application::withCount(['accessTokens'])
                ->with('category')
                ->orderBy('access_tokens_count', 'desc')
                ->limit(6)
                ->get();
        });

        // 4. Activity Logs Metrics
        $logMetrics = Cache::remember("analytics_logs_{$range}", 60, function () use ($startDate) {
            $logins = AuditLog::whereIn('activity', ['user_login', 'sso_authorize_granted', 'sso_access_denied'])
                ->where('created_at', '>=', $startDate)
                ->count();

            $failedLogins = AuditLog::whereIn('activity', ['user_login_failed', 'token_exchange_invalid_secret', 'token_exchange_invalid_code'])
                ->where('created_at', '>=', $startDate)
                ->count();

            $totalActivity = AuditLog::where('created_at', '>=', $startDate)->count();

            return [
                'total_activity' => $totalActivity,
                'successful_logins' => $logins,
                'failed_logins' => $failedLogins,
                'security_events' => AuditLog::where('activity', 'like', '%blocked%')->orWhere('activity', 'like', '%invalid%')->count(),
            ];
        });

        // 5. SIJUNA Sync Health Metrics
        $syncMetrics = Cache::remember("analytics_sync_metrics", 60, function () {
            $totalSyncs = SyncLog::count();
            $successfulSyncs = SyncLog::where('status', 'success')->count();

            return [
                'total_syncs' => $totalSyncs,
                'successful_syncs' => $successfulSyncs,
                'success_rate' => $totalSyncs > 0 ? round(($successfulSyncs / $totalSyncs) * 100, 1) : 100,
                'latest_sync' => SyncLog::latest()->first(),
            ];
        });

        // 6. Integrated Applications Health Status Breakdown
        $appHealthBreakdown = [
            'online' => Application::where('last_health_status', 'online')->count(),
            'offline' => Application::where('last_health_status', 'offline')->count(),
            'warning' => Application::where('last_health_status', 'warning')->count(),
            'untested' => Application::whereNull('last_health_status')->count(),
        ];

        return view('admin.analytics.index', compact(
            'range',
            'days',
            'userDistribution',
            'ssoMetrics',
            'topApps',
            'logMetrics',
            'syncMetrics',
            'appHealthBreakdown'
        ));
    }
}
