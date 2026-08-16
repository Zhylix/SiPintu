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
        $startDate = $range === 'all' ? null : now()->subDays($days);

        // 1. User Role Distribution
        $userDistribution = Cache::remember("analytics_user_dist_{$range}", 60, function () {
            $total = User::count();
            $studentsCount = User::where('role', 'student')->count();
            $alumniCount = User::where('role', 'alumni')->count();
            $teachersCount = User::where('role', 'teacher')->count();
            $dudiCount = User::where('role', 'dudi')->count();
            $adminCount = User::where('role', 'admin')->count();

            return [
                'total' => $total,
                'students' => [
                    'count' => $studentsCount,
                    'percentage' => $total > 0 ? round(($studentsCount / $total) * 100, 1) : 0,
                ],
                'alumni' => [
                    'count' => $alumniCount,
                    'percentage' => $total > 0 ? round(($alumniCount / $total) * 100, 1) : 0,
                ],
                'teachers' => [
                    'count' => $teachersCount,
                    'percentage' => $total > 0 ? round(($teachersCount / $total) * 100, 1) : 0,
                ],
                'dudi' => [
                    'count' => $dudiCount,
                    'percentage' => $total > 0 ? round(($dudiCount / $total) * 100, 1) : 0,
                ],
                'admin' => [
                    'count' => $adminCount,
                    'percentage' => $total > 0 ? round(($adminCount / $total) * 100, 1) : 0,
                ],
            ];
        });

        // 2. SSO Token & Access Analytics
        $ssoMetrics = Cache::remember("analytics_sso_metrics_{$range}", 60, function () use ($startDate) {
            $recentTokensQuery = OAuthAccessToken::query();
            if ($startDate) {
                $recentTokensQuery->where('created_at', '>=', $startDate);
            }

            return [
                'total_issued_tokens' => OAuthAccessToken::count(),
                'active_tokens' => OAuthAccessToken::where('revoked', false)->where('expires_at', '>', now())->count(),
                'recent_tokens' => $recentTokensQuery->count(),
            ];
        });

        // 3. Top Most Accessed SSO Applications (calculated from OAuth tokens & Audit logs)
        $topApps = Cache::remember("analytics_top_apps_{$range}", 60, function () {
            return Application::withCount(['accessTokens'])
                ->with('category')
                ->orderBy('access_tokens_count', 'desc')
                ->limit(6)
                ->get()
                ->map(function ($app) {
                    return [
                        'id' => $app->id,
                        'name' => $app->name,
                        'category_name' => $app->category?->name ?? 'Umum',
                        'status' => $app->status,
                        'access_tokens_count' => (int) $app->access_tokens_count,
                    ];
                })
                ->all();
        });

        // 4. Activity Logs Metrics
        $logMetrics = Cache::remember("analytics_logs_{$range}", 60, function () use ($startDate) {
            $baseQuery = AuditLog::query();
            if ($startDate) {
                $baseQuery->where('created_at', '>=', $startDate);
            }

            $successfulLogins = (clone $baseQuery)->where(function ($q) {
                $q->where('activity', 'like', 'login_success%')
                  ->orWhere('activity', 'user_login')
                  ->orWhere('activity', 'sso_authorize_granted');
            })->count();

            $failedLogins = (clone $baseQuery)->where(function ($q) {
                $q->where('activity', 'like', 'login_failed%')
                  ->orWhere('activity', 'like', '%invalid%');
            })->count();

            $totalActivity = (clone $baseQuery)->count();

            $securityEvents = (clone $baseQuery)->where(function ($q) {
                $q->where('activity', 'like', '%blocked%')
                  ->orWhere('activity', 'like', '%failed%')
                  ->orWhere('activity', 'like', '%invalid%');
            })->count();

            return [
                'total_activity' => $totalActivity,
                'successful_logins' => $successfulLogins,
                'failed_logins' => $failedLogins,
                'security_events' => $securityEvents,
            ];
        });

        // 5. SIJUNA Sync Health Metrics
        $syncMetrics = Cache::remember('analytics_sync_metrics', 60, function () {
            $totalSyncs = SyncLog::count();
            $successfulSyncs = SyncLog::where('status', 'success')->count();
            $latestSync = SyncLog::latest()->first();

            return [
                'total_syncs' => $totalSyncs,
                'successful_syncs' => $successfulSyncs,
                'success_rate' => $totalSyncs > 0 ? round(($successfulSyncs / $totalSyncs) * 100, 1) : 100,
                'has_latest' => $latestSync !== null,
                'latest_sync_status' => $latestSync?->status,
                'latest_sync_records' => $latestSync?->records_processed ?? 0,
                'latest_sync_time' => $latestSync?->created_at?->diffForHumans() ?? 'Belum ada sync',
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
