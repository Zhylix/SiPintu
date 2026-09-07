<?php

namespace App\Services;

use App\Models\Application;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Throwable;

class SsoDiagnosticsService
{
    /**
     * Jalankan diagnosa lengkap koneksi SSO untuk aplikasi tertentu
     */
    public function diagnose(Application $application, ?string $inputSecret = null): array
    {
        $application->loadMissing('roles', 'category');

        $checks = [];
        $issues = [];

        // 1. Periksa Integritas Konfigurasi di SiPintu Gateway
        $configCheck = $this->checkGatewayConfiguration($application, $inputSecret);
        $checks[] = $configCheck['check'];
        if (! empty($configCheck['issues'])) {
            $issues = array_merge($issues, $configCheck['issues']);
        }

        // 2. Periksa Kesiapan OAuth Engine SiPintu
        $oauthEngineCheck = $this->checkOAuthEngine($application);
        $checks[] = $oauthEngineCheck['check'];
        if (! empty($oauthEngineCheck['issues'])) {
            $issues = array_merge($issues, $oauthEngineCheck['issues']);
        }

        // 3. Periksa Jaringan & Host Downstream (Base URL Ping)
        $hostCheck = $this->checkHostReachability($application);
        $checks[] = $hostCheck['check'];
        if (! empty($hostCheck['issues'])) {
            $issues = array_merge($issues, $hostCheck['issues']);
        }

        // 4. Periksa Endpoint Callback SSO di Downstream (Redirect URI)
        $callbackCheck = $this->checkCallbackEndpoint($application);
        $checks[] = $callbackCheck['check'];
        if (! empty($callbackCheck['issues'])) {
            $issues = array_merge($issues, $callbackCheck['issues']);
        }

        // 5. Periksa Endpoint Health Check Downstream (/health)
        $healthCheck = $this->checkHealthEndpoint($application);
        $checks[] = $healthCheck['check'];
        if (! empty($healthCheck['issues'])) {
            $issues = array_merge($issues, $healthCheck['issues']);
        }

        // 6. Periksa Endpoint Webhook Sinkronisasi Password
        $webhookCheck = $this->checkPasswordSyncWebhook($application);
        $checks[] = $webhookCheck['check'];
        if (! empty($webhookCheck['issues'])) {
            $issues = array_merge($issues, $webhookCheck['issues']);
        }

        // Sintesis Hasil Diagnosa
        $passedCount = count(array_filter($checks, fn ($c) => $c['status'] === 'PASS'));
        $warnCount = count(array_filter($checks, fn ($c) => $c['status'] === 'WARN'));
        $failCount = count(array_filter($checks, fn ($c) => $c['status'] === 'FAIL'));
        $totalChecks = count($checks);

        $hasCriticalFailures = count(array_filter($issues, fn ($i) => $i['severity'] === 'CRITICAL')) > 0;

        if ($hasCriticalFailures || $failCount > 0) {
            $overallStatus = 'CRITICAL';
            $statusText = 'Terjadi Masalah Kritis (SSO Terganggu)';
            $badgeColor = 'rose';
        } elseif ($warnCount > 0) {
            $overallStatus = 'WARNING';
            $statusText = 'Berfungsi dengan Peringatan (Ada Konfigurasi Belum Lengkap)';
            $badgeColor = 'amber';
        } else {
            $overallStatus = 'HEALTHY';
            $statusText = 'Koneksi SSO Normal & Siap Digunakan (100% Sehat)';
            $badgeColor = 'emerald';
        }

        $healthScore = max(0, min(100, round((($passedCount + ($warnCount * 0.5)) / $totalChecks) * 100)));

        return [
            'application' => [
                'id' => $application->id,
                'name' => $application->name,
                'client_id' => $application->client_id,
                'base_url' => $application->base_url,
                'redirect_uri' => $application->redirect_uri,
                'health_check_url' => $application->health_check_url,
                'status' => $application->status,
                'roles' => $application->roles->pluck('name')->toArray(),
            ],
            'timestamp' => now()->toIso8601String(),
            'overall_status' => $overallStatus,
            'status_text' => $statusText,
            'badge_color' => $badgeColor,
            'health_score' => $healthScore,
            'summary' => [
                'total_checks' => $totalChecks,
                'passed' => $passedCount,
                'warnings' => $warnCount,
                'failed' => $failCount,
                'issues_count' => count($issues),
            ],
            'checks' => $checks,
            'issues' => $issues,
        ];
    }

    /**
     * Diagnosa massal untuk seluruh aplikasi downstream terdaftar
     */
    public function diagnoseAll(): array
    {
        $applications = Application::with('roles', 'category')->get();
        $results = [];

        foreach ($applications as $app) {
            $results[] = $this->diagnose($app);
        }

        $total = count($results);
        $healthy = count(array_filter($results, fn ($r) => $r['overall_status'] === 'HEALTHY'));
        $warning = count(array_filter($results, fn ($r) => $r['overall_status'] === 'WARNING'));
        $critical = count(array_filter($results, fn ($r) => $r['overall_status'] === 'CRITICAL'));

        return [
            'total_applications' => $total,
            'healthy_count' => $healthy,
            'warning_count' => $warning,
            'critical_count' => $critical,
            'results' => $results,
        ];
    }

    /**
     * 1. Periksa Konfigurasi di SiPintu Gateway
     */
    protected function checkGatewayConfiguration(Application $app, ?string $inputSecret = null): array
    {
        $issues = [];
        $status = 'PASS';
        $messages = [];

        // Cek status aplikasi
        if ($app->status !== 'active') {
            $status = 'FAIL';
            $messages[] = "Status aplikasi saat ini '{$app->status}' (Bukan ACTIVE).";
            $issues[] = [
                'id' => 'APP_INACTIVE',
                'title' => 'Aplikasi Berstatus Inaktif / Maintenance',
                'severity' => 'CRITICAL',
                'location' => 'GATEWAY_SIPINTU',
                'location_label' => 'SiPintu Gateway',
                'cause' => "Aplikasi '{$app->name}' sedang dinonaktifkan di registry SiPintu sehingga seluruh permintaan autentikasi SSO ditolak.",
                'solution_title' => 'Ubah Status Menjadi Active',
                'solution_steps' => [
                    "Buka panel Admin SiPintu > Menu 'Aplikasi Eksternal'.",
                    "Klik 'Edit' pada aplikasi {$app->name}.",
                    "Ubah status menjadi 'Active', lalu simpan.",
                ],
                'solution_code' => null,
            ];
        }

        // Cek Role Access
        if ($app->roles->isEmpty()) {
            $status = 'FAIL';
            $messages[] = 'Belum ada Role Pengguna (Siswa/Guru/Admin/DUDI) yang diizinkan.';
            $issues[] = [
                'id' => 'NO_ROLES_ASSIGNED',
                'title' => 'Belum Ada Role Pengguna yang Diizinkan',
                'severity' => 'CRITICAL',
                'location' => 'GATEWAY_SIPINTU',
                'location_label' => 'SiPintu Gateway',
                'cause' => "Aplikasi tidak memiliki hak akses role apapun. Pengguna yang mencoba login via SSO akan ditolak dengan galat 'Access Denied'.",
                'solution_title' => 'Beri Izin Role Pengguna',
                'solution_steps' => [
                    "Edit aplikasi {$app->name} di menu Admin SiPintu.",
                    'Centang role yang boleh menggunakan aplikasi ini (misal: Siswa, Guru).',
                ],
                'solution_code' => null,
            ];
        }

        // Cek Validitas Format Redirect URI
        if (! filter_var($app->redirect_uri, FILTER_VALIDATE_URL)) {
            $status = 'FAIL';
            $messages[] = "Redirect URI '{$app->redirect_uri}' bukan URL valid.";
            $issues[] = [
                'id' => 'INVALID_REDIRECT_URI_FORMAT',
                'title' => 'Format Redirect URI Tidak Valid',
                'severity' => 'CRITICAL',
                'location' => 'GATEWAY_SIPINTU',
                'location_label' => 'SiPintu Gateway',
                'cause' => "URL callback yang didaftarkan ('{$app->redirect_uri}') tidak memiliki skema http/https atau struktur domain yang benar.",
                'solution_title' => 'Perbaiki Redirect URI',
                'solution_steps' => [
                    "Pastikan menyertakan http:// atau https:// (contoh: http://localhost:8001/oauth/callback).",
                ],
                'solution_code' => "http://localhost:8001/oauth/callback",
            ];
        }

        // Cek Kesesuaian Secret jika diberikan
        if ($inputSecret !== null) {
            $secretValid = ($inputSecret === $app->client_secret);
            if (! $secretValid && (str_starts_with($app->client_secret, '$2y$') || str_starts_with($app->client_secret, '$2a$'))) {
                try {
                    $secretValid = Hash::check($inputSecret, $app->client_secret);
                } catch (Throwable $e) {
                    $secretValid = false;
                }
            }

            if (! $secretValid) {
                $status = 'FAIL';
                $messages[] = 'Client Secret yang diuji tidak cocok dengan database.';
                $issues[] = [
                    'id' => 'SECRET_MISMATCH',
                    'title' => 'Client Secret Tidak Cocok',
                    'severity' => 'CRITICAL',
                    'location' => 'APLIKASI_DOWNSTREAM',
                    'location_label' => 'Aplikasi Downstream',
                    'cause' => "Client Secret yang terpasang di file .env downstream tidak sama dengan kunci rahasia di SiPintu.",
                    'solution_title' => 'Perbarui SIPINTU_CLIENT_SECRET di .env Downstream',
                    'solution_steps' => [
                        "Buka file .env di aplikasi downstream.",
                        "Periksa nilai SIPINTU_CLIENT_SECRET, atau buat kunci baru melalui tombol 'Reset Secret' di SiPintu.",
                    ],
                    'solution_code' => "SIPINTU_CLIENT_SECRET=sec_xxxxxxxxxxxx",
                ];
            }
        }

        if (empty($messages)) {
            $messages[] = 'Status aplikasi ACTIVE, kredensial terdaftar, & role akses terdefinisi.';
        }

        return [
            'check' => [
                'id' => 'gateway_config',
                'name' => 'Integritas Registry & Role Akses',
                'status' => $status,
                'target' => $app->client_id,
                'message' => implode(' ', $messages),
                'latency_ms' => 0,
            ],
            'issues' => $issues,
        ];
    }

    /**
     * 2. Periksa Kesiapan OAuth 2.0 Engine SiPintu
     */
    protected function checkOAuthEngine(Application $app): array
    {
        $hasAuthorize = Route::has('oauth.authorize');
        $hasToken = Route::has('oauth.token');
        $hasUserApi = Route::has('api.v1.user');

        $allRoutesReady = $hasAuthorize && $hasToken && $hasUserApi;
        $status = $allRoutesReady ? 'PASS' : 'FAIL';
        $issues = [];

        if (! $allRoutesReady) {
            $missing = [];
            if (! $hasAuthorize) $missing[] = 'oauth.authorize';
            if (! $hasToken) $missing[] = 'oauth.token';
            if (! $hasUserApi) $missing[] = 'api.v1.user';

            $issues[] = [
                'id' => 'SIPINTU_ROUTES_MISSING',
                'title' => 'Endpoint OAuth Engine SiPintu Belum Lengkap',
                'severity' => 'CRITICAL',
                'location' => 'GATEWAY_SIPINTU',
                'location_label' => 'SiPintu Gateway',
                'cause' => 'Rute OAuth inti tidak terdaftar: '.implode(', ', $missing),
                'solution_title' => 'Pastikan File Routes Utuh',
                'solution_steps' => [
                    'Periksa routes/web.php dan routes/api.php di SiPintu.',
                ],
                'solution_code' => null,
            ];
            $message = 'Endpoint OAuth inti SiPintu hilang: '.implode(', ', $missing);
        } else {
            $message = 'Endpoint OAuth SiPintu (/oauth/authorize, /oauth/token, /api/v1/user) siap beroperasi.';
        }

        return [
            'check' => [
                'id' => 'oauth_engine',
                'name' => 'Kesiapan OAuth 2.0 Engine SiPintu',
                'status' => $status,
                'target' => config('app.url', 'http://localhost:8000').'/oauth/token',
                'message' => $message,
                'latency_ms' => 0,
            ],
            'issues' => $issues,
        ];
    }

    /**
     * 3. Periksa Jaringan & Host Downstream (Base URL Reachability)
     */
    protected function checkHostReachability(Application $app): array
    {
        $targetUrl = rtrim($app->base_url, '/');
        $startTime = microtime(true);
        $issues = [];

        try {
            $response = Http::timeout(3)->get($targetUrl);
            $latency = round((microtime(true) - $startTime) * 1000, 2);
            $code = $response->status();

            return [
                'check' => [
                    'id' => 'host_reachability',
                    'name' => 'Konektivitas Host Server Downstream',
                    'status' => 'PASS',
                    'target' => $targetUrl,
                    'http_code' => $code,
                    'latency_ms' => $latency,
                    'message' => "Host downstream berhasil merespons dalam {$latency} ms (HTTP {$code}).",
                ],
                'issues' => [],
            ];
        } catch (Throwable $e) {
            $latency = round((microtime(true) - $startTime) * 1000, 2);
            $err = $e->getMessage();

            // Analisis penyebab koneksi gagal
            if (str_contains($err, 'Connection refused') || str_contains($err, 'Failed to connect')) {
                $cause = "Server downstream tidak aktif atau port salah. SiPintu tidak dapat menghubungi {$targetUrl}.";
                $fixSteps = [
                    "Buka terminal di folder aplikasi downstream.",
                    "Nyalakan web server downstream (contoh: php artisan serve --port=".(parse_url($targetUrl, PHP_URL_PORT) ?: 8001).").",
                    "Pastikan port yang berjalan cocok dengan Base URL di SiPintu.",
                ];
                $fixCode = "php artisan serve --port=".(parse_url($targetUrl, PHP_URL_PORT) ?: 8001);
            } elseif (str_contains($err, 'Could not resolve host') || str_contains($err, 'Name or service not known')) {
                $cause = "Domain host downstream '{$targetUrl}' tidak dapat ditemukan oleh DNS.";
                $fixSteps = [
                    "Pastikan domain atau hostname downstream sudah benar dan dapat di-resolve.",
                    "Jika menggunakan domain lokal, periksa /etc/hosts pada server SiPintu.",
                ];
                $fixCode = null;
            } elseif (str_contains($err, 'timed out') || str_contains($err, 'Operation timed out')) {
                $cause = "Koneksi ke downstream time out (melebihi batas toleransi 3 detik). Kemungkinan terhalang firewall atau server downstream overload.";
                $fixSteps = [
                    "Periksa firewall port pada server downstream.",
                    "Pastikan downstream dapat diakses langsung dari server SiPintu.",
                ];
                $fixCode = null;
            } elseif (str_contains($err, 'SSL') || str_contains($err, 'certificate')) {
                $cause = "Sertifikat SSL/HTTPS di server downstream tidak valid atau kadaluarsa.";
                $fixSteps = [
                    "Perbarui sertifikat SSL di server downstream.",
                    "Jika di lingkungan development, gunakan HTTP sementara atau pasang SSL lokal tepercaya.",
                ];
                $fixCode = null;
            } else {
                $cause = "Terjadi kegagalan jaringan saat menghubungi downstream: {$err}";
                $fixSteps = [
                    "Periksa apakah aplikasi downstream dapat diakses via browser secara langsung.",
                ];
                $fixCode = null;
            }

            $issues[] = [
                'id' => 'DOWNSTREAM_HOST_UNREACHABLE',
                'title' => 'Server Downstream Tidak Menjawab (Host Unreachable)',
                'severity' => 'CRITICAL',
                'location' => 'APLIKASI_DOWNSTREAM',
                'location_label' => 'Aplikasi Downstream',
                'cause' => $cause,
                'solution_title' => 'Nyalakan Server Downstream atau Sesuaikan URL/Port',
                'solution_steps' => $fixSteps,
                'solution_code' => $fixCode,
            ];

            return [
                'check' => [
                    'id' => 'host_reachability',
                    'name' => 'Konektivitas Host Server Downstream',
                    'status' => 'FAIL',
                    'target' => $targetUrl,
                    'http_code' => null,
                    'latency_ms' => $latency,
                    'message' => "Host downstream gagal dihubungi: {$err}",
                ],
                'issues' => $issues,
            ];
        }
    }

    /**
     * 4. Periksa Endpoint Callback SSO di Downstream (/oauth/callback)
     */
    protected function checkCallbackEndpoint(Application $app): array
    {
        $callbackUrl = $app->redirect_uri;
        $startTime = microtime(true);
        $issues = [];

        try {
            // Lakukan HTTP GET uji coba ke callback tanpa parameter
            $response = Http::timeout(3)->get($callbackUrl);
            $latency = round((microtime(true) - $startTime) * 1000, 2);
            $code = $response->status();

            // Jika route ada, umumnya me-redirect (302) kembali ke login atau menampilkan respons 200/400
            if ($code === 404) {
                $issues[] = [
                    'id' => 'CALLBACK_ROUTE_NOT_FOUND',
                    'title' => 'Route Callback SSO Tidak Ditemukan (404 Not Found)',
                    'severity' => 'CRITICAL',
                    'location' => 'APLIKASI_DOWNSTREAM',
                    'location_label' => 'Aplikasi Downstream',
                    'cause' => "URL callback '{$callbackUrl}' mengembalikan 404 Not Found. Route /oauth/callback belum didaftarkan di routes/web.php aplikasi downstream.",
                    'solution_title' => 'Tambahkan Route Callback di routes/web.php Downstream',
                    'solution_steps' => [
                        "Buka file routes/web.php di aplikasi downstream.",
                        "Daftarkan route: Route::get('/oauth/callback', [OAuthController::class, 'callback']);",
                    ],
                    'solution_code' => "Route::get('/oauth/callback', [OAuthController::class, 'callback'])->name('oauth.callback');",
                ];

                return [
                    'check' => [
                        'id' => 'callback_endpoint',
                        'name' => 'Ketersediaan Route Callback SSO',
                        'status' => 'FAIL',
                        'target' => $callbackUrl,
                        'http_code' => 404,
                        'latency_ms' => $latency,
                        'message' => 'Route callback mengembalikan status 404 Not Found (Belum terdaftar di downstream).',
                    ],
                    'issues' => $issues,
                ];
            }

            if ($code >= 500) {
                $issues[] = [
                    'id' => 'CALLBACK_INTERNAL_ERROR',
                    'title' => 'Route Callback Downstream Crash (500 Server Error)',
                    'severity' => 'CRITICAL',
                    'location' => 'APLIKASI_DOWNSTREAM',
                    'location_label' => 'Aplikasi Downstream',
                    'cause' => "Route callback di downstream menghasilkan Internal Server Error (HTTP {$code}). Kemungkinan terjadi exception di kode OAuthController.",
                    'solution_title' => 'Periksa Log Error di Aplikasi Downstream',
                    'solution_steps' => [
                        "Buka file storage/logs/laravel.log di folder downstream.",
                        "Periksa exception di controller OAuthController.php.",
                    ],
                    'solution_code' => null,
                ];

                return [
                    'check' => [
                        'id' => 'callback_endpoint',
                        'name' => 'Ketersediaan Route Callback SSO',
                        'status' => 'FAIL',
                        'target' => $callbackUrl,
                        'http_code' => $code,
                        'latency_ms' => $latency,
                        'message' => "Route callback melempar galat server (HTTP {$code}).",
                    ],
                    'issues' => $issues,
                ];
            }

            return [
                'check' => [
                    'id' => 'callback_endpoint',
                    'name' => 'Ketersediaan Route Callback SSO',
                    'status' => 'PASS',
                    'target' => $callbackUrl,
                    'http_code' => $code,
                    'latency_ms' => $latency,
                    'message' => "Route callback SSO ditemukan dan aktif merespons (HTTP {$code}, {$latency} ms).",
                ],
                'issues' => [],
            ];
        } catch (Throwable $e) {
            $latency = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'check' => [
                    'id' => 'callback_endpoint',
                    'name' => 'Ketersediaan Route Callback SSO',
                    'status' => 'FAIL',
                    'target' => $callbackUrl,
                    'http_code' => null,
                    'latency_ms' => $latency,
                    'message' => 'Gagal menghubungi URL callback: '.$e->getMessage(),
                ],
                'issues' => [
                    [
                        'id' => 'CALLBACK_UNREACHABLE',
                        'title' => 'URL Callback SSO Tidak Dapat Dihubungi',
                        'severity' => 'CRITICAL',
                        'location' => 'APLIKASI_DOWNSTREAM',
                        'location_label' => 'Aplikasi Downstream',
                        'cause' => "SiPintu tidak dapat mencapai URL {$callbackUrl}: ".$e->getMessage(),
                        'solution_title' => 'Pastikan Aplikasi Downstream Berjalan & URL Sesuai',
                        'solution_steps' => [
                            "Pastikan web server downstream sedang menyala.",
                            "Pastikan Redirect URI di SiPintu sama persis dengan URL callback downstream.",
                        ],
                        'solution_code' => null,
                    ],
                ],
            ];
        }
    }

    /**
     * 5. Periksa Endpoint Health Check Downstream (/health)
     */
    protected function checkHealthEndpoint(Application $app): array
    {
        $targetUrl = $app->health_check_url ?: rtrim($app->base_url, '/').'/health';
        $startTime = microtime(true);
        $issues = [];

        try {
            $response = Http::timeout(3)->get($targetUrl);
            $latency = round((microtime(true) - $startTime) * 1000, 2);
            $code = $response->status();

            if ($response->successful()) {
                return [
                    'check' => [
                        'id' => 'health_endpoint',
                        'name' => 'Endpoint Pemantauan Health Check (/health)',
                        'status' => 'PASS',
                        'target' => $targetUrl,
                        'http_code' => $code,
                        'latency_ms' => $latency,
                        'message' => "Endpoint /health merespons sempurna (HTTP {$code}, {$latency} ms).",
                    ],
                    'issues' => [],
                ];
            }

            if ($code === 404) {
                $issues[] = [
                    'id' => 'HEALTH_CHECK_MISSING',
                    'title' => 'Endpoint /health Belum Dibuat di Downstream',
                    'severity' => 'WARNING',
                    'location' => 'APLIKASI_DOWNSTREAM',
                    'location_label' => 'Aplikasi Downstream',
                    'cause' => "Aplikasi downstream belum menyediakan route GET /health. SSO tetap bisa berjalan, namun status kesehatan otomatis tidak bisa dipantau SiPintu.",
                    'solution_title' => 'Tambahkan Route /health di routes/web.php Downstream',
                    'solution_steps' => [
                        "Tambahkan 1 baris route sederhana di routes/web.php aplikasi downstream.",
                    ],
                    'solution_code' => "Route::get('/health', fn () => response()->json(['status' => 'ok']));",
                ];

                return [
                    'check' => [
                        'id' => 'health_endpoint',
                        'name' => 'Endpoint Pemantauan Health Check (/health)',
                        'status' => 'WARN',
                        'target' => $targetUrl,
                        'http_code' => 404,
                        'latency_ms' => $latency,
                        'message' => 'Endpoint /health belum tersedia (404 Not Found). Disarankan menambahkannya untuk monitoring berkala.',
                    ],
                    'issues' => $issues,
                ];
            }

            return [
                'check' => [
                    'id' => 'health_endpoint',
                    'name' => 'Endpoint Pemantauan Health Check (/health)',
                    'status' => 'WARN',
                    'target' => $targetUrl,
                    'http_code' => $code,
                    'latency_ms' => $latency,
                    'message' => "Endpoint /health mengembalikan status HTTP {$code}.",
                ],
                'issues' => [],
            ];
        } catch (Throwable $e) {
            $latency = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'check' => [
                    'id' => 'health_endpoint',
                    'name' => 'Endpoint Pemantauan Health Check (/health)',
                    'status' => 'WARN',
                    'target' => $targetUrl,
                    'http_code' => null,
                    'latency_ms' => $latency,
                    'message' => 'Tidak dapat mengakses /health: '.$e->getMessage(),
                ],
                'issues' => [],
            ];
        }
    }

    /**
     * 6. Periksa Endpoint Webhook Sinkronisasi Data Pengguna & Kata Sandi
     */
    protected function checkPasswordSyncWebhook(Application $app): array
    {
        $userSyncUrl = rtrim($app->base_url, '/').'/api/sipintu/sync-user';
        $passwordSyncUrl = rtrim($app->base_url, '/').'/api/sipintu/sync-password';
        $startTime = microtime(true);
        $issues = [];

        try {
            // 1. Cek endpoint modern /api/sipintu/sync-user terlebih dahulu
            $response = Http::timeout(3)->post($userSyncUrl, ['ping' => true]);
            $latency = round((microtime(true) - $startTime) * 1000, 2);
            $code = $response->status();

            if ($code !== 404) {
                return [
                    'check' => [
                        'id' => 'password_webhook',
                        'name' => 'Webhook Sinkronisasi Data Pengguna & Password Otomatis',
                        'status' => 'PASS',
                        'target' => $userSyncUrl,
                        'http_code' => $code,
                        'latency_ms' => $latency,
                        'message' => "Route webhook sinkronisasi otomatis data pengguna aktif merespons (HTTP {$code}).",
                    ],
                    'issues' => [],
                ];
            }

            // 2. Cek endpoint fallback /api/sipintu/sync-password jika sync-user 404
            $fallbackResponse = Http::timeout(3)->post($passwordSyncUrl, ['ping' => true]);
            $fallbackCode = $fallbackResponse->status();

            if ($fallbackCode !== 404) {
                return [
                    'check' => [
                        'id' => 'password_webhook',
                        'name' => 'Webhook Sinkronisasi Password Otomatis',
                        'status' => 'PASS',
                        'target' => $passwordSyncUrl,
                        'http_code' => $fallbackCode,
                        'latency_ms' => round((microtime(true) - $startTime) * 1000, 2),
                        'message' => "Route webhook sinkronisasi password aktif merespons (HTTP {$fallbackCode}). Disarankan upgrade ke /api/sipintu/sync-user untuk sinkronisasi profil penuh.",
                    ],
                    'issues' => [],
                ];
            }

            // 3. Jika kedua endpoint 404
            $issues[] = [
                'id' => 'PASSWORD_WEBHOOK_NOT_CONFIGURED',
                'title' => 'Webhook Sinkronisasi Data Pengguna Belum Disediakan',
                'severity' => 'WARNING',
                'location' => 'APLIKASI_DOWNSTREAM',
                'location_label' => 'Aplikasi Downstream',
                'cause' => "Aplikasi downstream belum memiliki endpoint POST /api/sipintu/sync-user (atau /api/sipintu/sync-password). Jika data pengguna atau password diubah di SiPintu, downstream tidak akan menerima update instan secara real-time di latar belakang.",
                'solution_title' => 'Tambahkan Endpoint Webhook Sinkronisasi di Downstream',
                'solution_steps' => [
                    "Sediakan route POST /api/sipintu/sync-user di downstream untuk memperbarui data profil & password hash siswa/guru secara real-time.",
                ],
                'solution_code' => "Route::post('/api/sipintu/sync-user', [OAuthController::class, 'syncUser']);",
            ];

            return [
                'check' => [
                    'id' => 'password_webhook',
                    'name' => 'Webhook Sinkronisasi Data Pengguna & Password Otomatis',
                    'status' => 'WARN',
                    'target' => $userSyncUrl,
                    'http_code' => 404,
                    'latency_ms' => $latency,
                    'message' => 'Endpoint webhook belum tersedia (404 Not Found). Data pengguna akan disinkronkan saat login SSO berikutnya.',
                ],
                'issues' => $issues,
            ];
        } catch (Throwable $e) {
            $latency = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'check' => [
                    'id' => 'password_webhook',
                    'name' => 'Webhook Sinkronisasi Data Pengguna & Password Otomatis',
                    'status' => 'WARN',
                    'target' => $userSyncUrl,
                    'http_code' => null,
                    'latency_ms' => $latency,
                    'message' => 'Pemeriksaan webhook dilewati (Host downstream belum merespons).',
                ],
                'issues' => [],
            ];
        }
    }
}
