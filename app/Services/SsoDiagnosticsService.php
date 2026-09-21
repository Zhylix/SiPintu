<?php

namespace App\Services;

use App\Models\Application;
use App\Models\OAuthAccessToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Throwable;

class SsoDiagnosticsService
{
    /**
     * Jalankan diagnosa lengkap koneksi SSO untuk aplikasi tertentu dengan 8 titik uji presisi
     */
    public function diagnose(Application $application, ?string $inputSecret = null, bool $persist = true): array
    {
        $application->loadMissing('roles');

        $checks = [];
        $issues = [];
        $telemetry = [];

        // 1. Periksa Integritas Konfigurasi & Keselarasan Domain/Origin di SiPintu Gateway
        $configCheck = $this->checkGatewayConfiguration($application, $inputSecret);
        $checks[] = $configCheck['check'];
        if (! empty($configCheck['issues'])) {
            $issues = array_merge($issues, $configCheck['issues']);
        }
        $telemetry['origin_matched'] = $configCheck['origin_matched'] ?? true;

        // 2. Periksa Kesiapan OAuth 2.0 Engine & Token Protocol SiPintu
        $oauthEngineCheck = $this->checkOAuthEngine($application);
        $checks[] = $oauthEngineCheck['check'];
        if (! empty($oauthEngineCheck['issues'])) {
            $issues = array_merge($issues, $oauthEngineCheck['issues']);
        }

        // 3. Periksa Jaringan, Resolusi DNS & Latensi Benchmark Host Downstream
        $hostCheck = $this->checkHostReachability($application);
        $checks[] = $hostCheck['check'];
        if (! empty($hostCheck['issues'])) {
            $issues = array_merge($issues, $hostCheck['issues']);
        }
        $telemetry['resolved_ip'] = $hostCheck['resolved_ip'] ?? null;
        $telemetry['ip_classification'] = $hostCheck['ip_classification'] ?? 'Unknown';
        $telemetry['latency_ms'] = $hostCheck['check']['latency_ms'] ?? 0;
        $telemetry['latency_grade'] = $hostCheck['latency_grade'] ?? 'unknown';

        // 4. Periksa Endpoint Callback SSO di Downstream (Redirect URI) dengan Deep HTTP Status Code Inspection
        $callbackCheck = $this->checkCallbackEndpoint($application);
        $checks[] = $callbackCheck['check'];
        if (! empty($callbackCheck['issues'])) {
            $issues = array_merge($issues, $callbackCheck['issues']);
        }

        // 5. Periksa Endpoint Health Check Downstream (/health) & Dekonstruksi Payload JSON
        $healthCheck = $this->checkHealthEndpoint($application);
        $checks[] = $healthCheck['check'];
        if (! empty($healthCheck['issues'])) {
            $issues = array_merge($issues, $healthCheck['issues']);
        }
        $telemetry['json_health_payload'] = $healthCheck['health_payload'] ?? null;

        // 6. Periksa Endpoint Webhook Sinkronisasi Data Pengguna & Password (Signed Request & CSRF Detection)
        $webhookCheck = $this->checkPasswordSyncWebhook($application);
        $checks[] = $webhookCheck['check'];
        if (! empty($webhookCheck['issues'])) {
            $issues = array_merge($issues, $webhookCheck['issues']);
        }
        $telemetry['webhook_signature_supported'] = $webhookCheck['signature_supported'] ?? false;

        // 7. Periksa Audit Keamanan Protokol & Isolasi SSO (Security & Protocol Isolation Audit)
        $securityCheck = $this->checkSecurityAndIsolation($application);
        $checks[] = $securityCheck['check'];
        if (! empty($securityCheck['issues'])) {
            $issues = array_merge($issues, $securityCheck['issues']);
        }

        // 8. Periksa Telemetri Sesi Aktif & Siklus Hidup Token (Active Token Lifecycle Telemetry)
        $tokenCheck = $this->checkTokenAndSessionLifecycle($application);
        $checks[] = $tokenCheck['check'];
        if (! empty($tokenCheck['issues'])) {
            $issues = array_merge($issues, $tokenCheck['issues']);
        }
        $telemetry['active_tokens_count'] = $tokenCheck['active_tokens'] ?? 0;
        $telemetry['last_connected_at'] = $application->last_connected_at?->toIso8601String();
        $telemetry['last_connected_human'] = $application->last_connected_at ? $application->last_connected_at->diffForHumans() : 'Belum pernah terkoneksi';
        $telemetry['total_api_requests'] = (int) $application->total_api_requests;

        // Sintesis Hasil Diagnosa dengan Weighted Health Index (WHI)
        $passedCount = count(array_filter($checks, fn ($c) => $c['status'] === 'PASS'));
        $warnCount = count(array_filter($checks, fn ($c) => $c['status'] === 'WARN'));
        $failCount = count(array_filter($checks, fn ($c) => $c['status'] === 'FAIL'));
        $totalChecks = count($checks);

        $hasCriticalFailures = count(array_filter($issues, fn ($i) => $i['severity'] === 'CRITICAL')) > 0;

        // Pembobotan Presisi Berdasarkan Tingkat Kritis Fungsionalitas SSO
        $weights = [
            'gateway_config' => 20,
            'oauth_engine' => 15,
            'host_reachability' => 25,
            'callback_endpoint' => 20,
            'health_endpoint' => 10,
            'password_webhook' => 10,
            'security_audit' => 0, // Bonus/Security advisory
            'token_lifecycle' => 0, // Informational telemetry
        ];

        $earnedPoints = 0;
        foreach ($checks as $check) {
            $cid = $check['id'] ?? '';
            $weight = $weights[$cid] ?? 0;
            if ($weight > 0) {
                if ($check['status'] === 'PASS') {
                    $earnedPoints += $weight;
                } elseif ($check['status'] === 'WARN') {
                    $earnedPoints += ($weight * 0.5);
                }
            }
        }

        $healthScore = max(0, min(100, (int) round($earnedPoints)));

        // Gating Rules: Jika Host Mati Total atau Callback Crash/Hilang, SSO Tidak Mungkin Berfungsi
        if ($hostCheck['check']['status'] === 'FAIL') {
            $healthScore = min($healthScore, 15);
        } elseif ($callbackCheck['check']['status'] === 'FAIL') {
            $healthScore = min($healthScore, 40);
        } elseif ($configCheck['check']['status'] === 'FAIL') {
            $healthScore = min($healthScore, 30);
        }

        // Tentukan Overall Status
        if ($hasCriticalFailures || $failCount > 0 || $healthScore < 50) {
            $overallStatus = 'CRITICAL';
            $statusText = 'Terjadi Masalah Kritis (Integrasi SSO Terganggu)';
            $badgeColor = 'rose';
        } elseif ($warnCount > 0 || $healthScore < 100) {
            $overallStatus = 'WARNING';
            $statusText = 'Berfungsi dengan Catatan (Konfigurasi/Jaringan Belum Optimal)';
            $badgeColor = 'amber';
        } else {
            $overallStatus = 'HEALTHY';
            $statusText = 'Koneksi SSO Normal & Siap Digunakan (100% Sehat)';
            $badgeColor = 'emerald';
        }

        $result = [
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
            'diagnosed_at_human' => now()->translatedFormat('d F Y, H:i:s'),
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
            'telemetry' => $telemetry,
            'checks' => $checks,
            'issues' => $issues,
        ];

        // Persistensi hasil ke database model & cache untuk konsistensi monitoring
        if ($persist) {
            $this->persistDiagnosisResults($application, $overallStatus, $statusText, $telemetry['latency_ms'] ?? null, $result);
        }

        return $result;
    }

    /**
     * Diagnosa massal untuk seluruh aplikasi downstream terdaftar
     */
    public function diagnoseAll(): array
    {
        $applications = Application::with('roles')->get();
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
     * 1. Periksa Konfigurasi & Keselarasan Domain/Origin di SiPintu Gateway
     */
    protected function checkGatewayConfiguration(Application $app, ?string $inputSecret = null): array
    {
        $issues = [];
        $status = 'PASS';
        $messages = [];
        $originMatched = true;

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
                    "Ubah status menjadi 'Active', lalu simpan konfigurasi.",
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

        // Cek Validitas Format Base URL
        if (empty($app->base_url) || ! filter_var($app->base_url, FILTER_VALIDATE_URL)) {
            $status = 'FAIL';
            $messages[] = "Base URL '{$app->base_url}' bukan URL valid.";
            $issues[] = [
                'id' => 'INVALID_BASE_URL_FORMAT',
                'title' => 'Format Base URL Tidak Valid',
                'severity' => 'CRITICAL',
                'location' => 'GATEWAY_SIPINTU',
                'location_label' => 'SiPintu Gateway',
                'cause' => "Base URL yang didaftarkan ('{$app->base_url}') tidak memiliki format URL http:// atau https:// yang valid.",
                'solution_title' => 'Perbaiki Base URL Aplikasi',
                'solution_steps' => [
                    "Buka panel Admin SiPintu > Menu 'Aplikasi Eksternal'.",
                    "Klik 'Edit' pada aplikasi {$app->name}.",
                    'Pastikan Base URL menyertakan http:// atau https:// (contoh: http://localhost:8001).',
                ],
                'solution_code' => 'http://localhost:8001',
            ];
        }

        // Cek Validitas Format Redirect URI
        if (empty($app->redirect_uri) || ! filter_var($app->redirect_uri, FILTER_VALIDATE_URL)) {
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
                    'Pastikan menyertakan http:// atau https:// (contoh: http://localhost:8001/oauth/callback).',
                ],
                'solution_code' => 'http://localhost:8001/oauth/callback',
            ];
        }

        // Cek Keselarasan Domain/Origin antara Base URL & Redirect URI
        if ($status !== 'FAIL' && filter_var($app->base_url, FILTER_VALIDATE_URL) && filter_var($app->redirect_uri, FILTER_VALIDATE_URL)) {
            $baseParsed = parse_url((string) $app->base_url);
            $redirectParsed = parse_url((string) $app->redirect_uri);

            $baseHost = strtolower($baseParsed['host'] ?? '');
            $redirectHost = strtolower($redirectParsed['host'] ?? '');
            $baseScheme = strtolower($baseParsed['scheme'] ?? 'http');
            $redirectScheme = strtolower($redirectParsed['scheme'] ?? 'http');
            $basePort = $baseParsed['port'] ?? ($baseScheme === 'https' ? 443 : 80);
            $redirectPort = $redirectParsed['port'] ?? ($redirectScheme === 'https' ? 443 : 80);

            if ($baseHost !== $redirectHost || $baseScheme !== $redirectScheme || $basePort !== $redirectPort) {
                $originMatched = false;
                $messages[] = "Perhatian: Origin Base URL ({$baseScheme}://{$baseHost}:{$basePort}) berbeda dengan Redirect URI ({$redirectScheme}://{$redirectHost}:{$redirectPort}).";
                $issues[] = [
                    'id' => 'DOMAIN_ORIGIN_MISMATCH',
                    'title' => 'Ketidakcocokan Origin Domain / Port / Protokol',
                    'severity' => 'WARNING',
                    'location' => 'GATEWAY_SIPINTU',
                    'location_label' => 'SiPintu Gateway',
                    'cause' => "Base URL ('{$app->base_url}') dan Redirect URI ('{$app->redirect_uri}') menggunakan host, port, atau protokol berbeda. Hal ini dapat memicu error CORS, cookie sesi terhapus, atau kegagalan pertukaran token.",
                    'solution_title' => 'Samakan Origin Host dan Protokol',
                    'solution_steps' => [
                        "Buka konfigurasi aplikasi {$app->name} di Admin SiPintu.",
                        'Pastikan Redirect URI berada di bawah domain, port, dan skema (http/https) yang sama dengan Base URL.',
                    ],
                    'solution_code' => rtrim($app->base_url, '/').'/oauth/callback',
                ];
            }
        }

        // Cek Kesesuaian Secret jika diuji
        if ($inputSecret !== null) {
            $secretValid = ($inputSecret === $app->client_secret);
            if (! $secretValid && ! empty($app->client_secret)) {
                try {
                    $secretValid = Hash::check($inputSecret, (string) $app->client_secret);
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
                    'cause' => 'Client Secret yang terpasang di file .env downstream tidak sama dengan kunci rahasia di SiPintu.',
                    'solution_title' => 'Perbarui SIPINTU_CLIENT_SECRET di .env Downstream',
                    'solution_steps' => [
                        'Buka file .env di aplikasi downstream.',
                        "Periksa nilai SIPINTU_CLIENT_SECRET, atau buat kunci baru melalui tombol 'Reset Secret' di SiPintu.",
                    ],
                    'solution_code' => 'SIPINTU_CLIENT_SECRET=sec_xxxxxxxxxxxx',
                ];
            }
        }

        if (empty($messages)) {
            $messages[] = 'Status ACTIVE, kredensial terverifikasi, role akses terdaftar, dan domain origin selaras.';
        }

        return [
            'check' => [
                'id' => 'gateway_config',
                'name' => 'Integritas Registry, Role Akses & Domain Match',
                'status' => $status,
                'target' => $app->client_id,
                'message' => implode(' ', $messages),
                'latency_ms' => 0,
            ],
            'issues' => $issues,
            'origin_matched' => $originMatched,
        ];
    }

    /**
     * 2. Periksa Kesiapan OAuth 2.0 Engine SiPintu & Token Route
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
            if (! $hasAuthorize) {
                $missing[] = 'oauth.authorize';
            }
            if (! $hasToken) {
                $missing[] = 'oauth.token';
            }
            if (! $hasUserApi) {
                $missing[] = 'api.v1.user';
            }

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
            $message = 'Endpoint OAuth SiPintu (/oauth/authorize, /oauth/token, /api/v1/user) siap beroperasi 100%.';
        }

        return [
            'check' => [
                'id' => 'oauth_engine',
                'name' => 'Kesiapan OAuth 2.0 Engine & Token Protocol SiPintu',
                'status' => $status,
                'target' => Route::has('oauth.token') ? route('oauth.token') : (rtrim((string) config('app.url', 'http://localhost:8000'), '/').'/oauth/token'),
                'message' => $message,
                'latency_ms' => 0,
            ],
            'issues' => $issues,
        ];
    }

    /**
     * 3. Periksa Jaringan, Resolusi DNS & Latensi Benchmark Host Downstream
     */
    protected function checkHostReachability(Application $app): array
    {
        $targetUrl = rtrim((string) $app->base_url, '/');
        if (empty($targetUrl) || ! filter_var($targetUrl, FILTER_VALIDATE_URL)) {
            return [
                'check' => [
                    'id' => 'host_reachability',
                    'name' => 'Konektivitas Host Server & Resolusi DNS',
                    'status' => 'FAIL',
                    'target' => $targetUrl ?: '(kosong)',
                    'http_code' => null,
                    'latency_ms' => 0,
                    'message' => 'Base URL kosong atau format tidak valid.',
                ],
                'issues' => [],
                'resolved_ip' => null,
                'ip_classification' => 'Invalid',
                'latency_grade' => 'critical',
            ];
        }

        // Resolusi DNS Host
        $host = parse_url($targetUrl, PHP_URL_HOST);
        $resolvedIp = null;
        $ipClassification = 'Unknown';

        if ($host) {
            if (filter_var($host, FILTER_VALIDATE_IP)) {
                $resolvedIp = $host;
            } else {
                $dnsLookup = @gethostbyname($host);
                if ($dnsLookup && $dnsLookup !== $host) {
                    $resolvedIp = $dnsLookup;
                }
            }

            if ($resolvedIp) {
                if (in_array($resolvedIp, ['127.0.0.1', '::1']) || str_starts_with($resolvedIp, '127.')) {
                    $ipClassification = 'Loopback / Localhost';
                } elseif (filter_var($resolvedIp, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                    $ipClassification = 'Private Network (LAN/Docker)';
                } else {
                    $ipClassification = 'Public Internet IP';
                }
            }
        }

        $startTime = microtime(true);
        $issues = [];

        try {
            $response = Http::timeout(3)->get($targetUrl);
            $latency = round((microtime(true) - $startTime) * 1000, 2);
            $code = $response->status();

            // Klasifikasi Latensi
            if ($latency < 50) {
                $latencyGrade = 'ultra_fast';
                $latencyLabel = 'Sangat Cepat';
            } elseif ($latency <= 150) {
                $latencyGrade = 'optimal';
                $latencyLabel = 'Optimal';
            } elseif ($latency <= 500) {
                $latencyGrade = 'warning';
                $latencyLabel = 'Tolerable / Agak Lambat';
            } else {
                $latencyGrade = 'critical';
                $latencyLabel = 'Tinggi / Rawan Lag';
            }

            $ipInfo = $resolvedIp ? " (IP: {$resolvedIp} [{$ipClassification}])" : '';

            return [
                'check' => [
                    'id' => 'host_reachability',
                    'name' => 'Konektivitas Host Server & Resolusi DNS',
                    'status' => 'PASS',
                    'target' => $targetUrl,
                    'http_code' => $code,
                    'latency_ms' => $latency,
                    'message' => "Host downstream aktif{$ipInfo}. Merespons dalam {$latency} ms ({$latencyLabel}, HTTP {$code}).",
                ],
                'issues' => [],
                'resolved_ip' => $resolvedIp,
                'ip_classification' => $ipClassification,
                'latency_grade' => $latencyGrade,
            ];
        } catch (Throwable $e) {
            $latency = round((microtime(true) - $startTime) * 1000, 2);
            $err = $e->getMessage();

            // Analisis akar masalah kegagalan jaringan
            if (str_contains($err, 'Connection refused') || str_contains($err, 'Failed to connect')) {
                $port = parse_url($targetUrl, PHP_URL_PORT) ?: 8001;
                $cause = "Port {$port} pada host downstream tidak terbuka atau service web server downstream belum dinyalakan.";
                $fixSteps = [
                    'Buka terminal pada folder proyek aplikasi downstream.',
                    "Jalankan web server: php artisan serve --port={$port}",
                    'Pastikan port yang aktif cocok dengan URL Base di SiPintu.',
                ];
                $fixCode = "php artisan serve --port={$port}";
            } elseif (str_contains($err, 'Could not resolve host') || str_contains($err, 'Name or service not known')) {
                $cause = "Domain host downstream '{$host}' tidak dapat di-resolve oleh DNS server.";
                $fixSteps = [
                    'Periksa ejaan hostname atau domain aplikasi downstream.',
                    'Jika menggunakan domain lokal (.test / .local), pastikan terdaftar di /etc/hosts server SiPintu.',
                ];
                $fixCode = "127.0.0.1   {$host}";
            } elseif (str_contains($err, 'timed out') || str_contains($err, 'Operation timed out')) {
                $cause = 'Koneksi ke host downstream mengalami timeout (melebihi batas toleransi 3 detik). Kemungkinan terblokir firewall atau server downstream mengalami resource spike.';
                $fixSteps = [
                    'Periksa apakah firewall server memblokir request masuk dari SiPintu.',
                    'Pastikan downstream dapat diakses langsung dari server hosting SiPintu.',
                ];
                $fixCode = null;
            } elseif (str_contains($err, 'SSL') || str_contains($err, 'certificate')) {
                $cause = 'Sertifikat SSL/HTTPS di server downstream tidak valid, kadaluarsa, atau self-signed.';
                $fixSteps = [
                    'Perbarui sertifikat SSL di web server downstream.',
                    'Untuk lingkungan testing lokal, pertimbangkan menggunakan HTTP sementara waktu.',
                ];
                $fixCode = null;
            } else {
                $cause = "Terjadi kegagalan jaringan saat menghubungi host downstream: {$err}";
                $fixSteps = [
                    'Periksa konektivitas jaringan antara server SiPintu dan aplikasi downstream.',
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
                    'name' => 'Konektivitas Host Server & Resolusi DNS',
                    'status' => 'FAIL',
                    'target' => $targetUrl,
                    'http_code' => null,
                    'latency_ms' => $latency,
                    'message' => "Host downstream gagal dihubungi: {$err}",
                ],
                'issues' => $issues,
                'resolved_ip' => $resolvedIp,
                'ip_classification' => $ipClassification,
                'latency_grade' => 'critical',
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

            // 1. Jika kode 302 Found: Sangat Normal untuk callback OAuth saat diakses langsung tanpa query code
            if ($code === 302) {
                return [
                    'check' => [
                        'id' => 'callback_endpoint',
                        'name' => 'Ketersediaan Route Callback SSO',
                        'status' => 'PASS',
                        'target' => $callbackUrl,
                        'http_code' => 302,
                        'latency_ms' => $latency,
                        'message' => "Route callback SSO aktif (HTTP 302 Redirect). Downstream mengalihkan permintaan kosong kembali ke login secara aman ({$latency} ms).",
                    ],
                    'issues' => [],
                ];
            }

            // 2. Jika kode 400 Bad Request atau 422 Unprocessable: Normal karena tidak mengirim code/state
            if ($code === 400 || $code === 422) {
                return [
                    'check' => [
                        'id' => 'callback_endpoint',
                        'name' => 'Ketersediaan Route Callback SSO',
                        'status' => 'PASS',
                        'target' => $callbackUrl,
                        'http_code' => $code,
                        'latency_ms' => $latency,
                        'message' => "Route callback SSO aktif dan memvalidasi parameter otorisasi (HTTP {$code}, {$latency} ms).",
                    ],
                    'issues' => [],
                ];
            }

            // 3. Jika kode 404 Not Found
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
                        'Buka file routes/web.php di aplikasi downstream.',
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

            // 4. Jika kode 405 Method Not Allowed (Misal salah didaftarkan sebagai POST)
            if ($code === 405) {
                $issues[] = [
                    'id' => 'CALLBACK_METHOD_NOT_ALLOWED',
                    'title' => 'Method Not Allowed pada Route Callback (HTTP 405)',
                    'severity' => 'CRITICAL',
                    'location' => 'APLIKASI_DOWNSTREAM',
                    'location_label' => 'Aplikasi Downstream',
                    'cause' => 'Route callback di downstream salah didaftarkan sebagai Route::post(). Protokol OAuth redirect dari browser SiPintu selalu menggunakan HTTP GET.',
                    'solution_title' => 'Ubah Route Menjadi HTTP GET',
                    'solution_steps' => [
                        'Buka routes/web.php di aplikasi downstream.',
                        'Ganti Route::post(\'/oauth/callback\', ...) menjadi Route::get(\'/oauth/callback\', ...).',
                    ],
                    'solution_code' => "Route::get('/oauth/callback', [OAuthController::class, 'callback'])->name('oauth.callback');",
                ];

                return [
                    'check' => [
                        'id' => 'callback_endpoint',
                        'name' => 'Ketersediaan Route Callback SSO',
                        'status' => 'FAIL',
                        'target' => $callbackUrl,
                        'http_code' => 405,
                        'latency_ms' => $latency,
                        'message' => 'Route callback mengembalikan HTTP 405 (Didaftarkan dengan HTTP Method salah, harus GET).',
                    ],
                    'issues' => $issues,
                ];
            }

            // 5. Jika kode 419 Page Expired (CSRF Protection Block)
            if ($code === 419) {
                $issues[] = [
                    'id' => 'CALLBACK_CSRF_BLOCKED',
                    'title' => 'Route Callback Terhalang Proteksi CSRF (HTTP 419)',
                    'severity' => 'CRITICAL',
                    'location' => 'APLIKASI_DOWNSTREAM',
                    'location_label' => 'Aplikasi Downstream',
                    'cause' => 'Route callback terhalang oleh CSRF middleware di downstream. OAuth callback menerima redirect eksternal dari SiPintu sehingga tidak membawa CSRF token Laravel lokal.',
                    'solution_title' => 'Pastikan Callback Diizinkan Tanpa Token CSRF Manual',
                    'solution_steps' => [
                        'Pastikan route callback adalah GET (GET request di Laravel secara default bebas CSRF).',
                        'Jika dibungkus middleware kustom, kecualikan rute /oauth/callback.',
                    ],
                    'solution_code' => null,
                ];

                return [
                    'check' => [
                        'id' => 'callback_endpoint',
                        'name' => 'Ketersediaan Route Callback SSO',
                        'status' => 'FAIL',
                        'target' => $callbackUrl,
                        'http_code' => 419,
                        'latency_ms' => $latency,
                        'message' => 'Route callback terhalang proteksi CSRF (HTTP 419).',
                    ],
                    'issues' => $issues,
                ];
            }

            // 6. Jika kode 500+ Internal Server Error
            if ($code >= 500) {
                $issues[] = [
                    'id' => 'CALLBACK_INTERNAL_ERROR',
                    'title' => 'Route Callback Downstream Crash (HTTP '.$code.')',
                    'severity' => 'CRITICAL',
                    'location' => 'APLIKASI_DOWNSTREAM',
                    'location_label' => 'Aplikasi Downstream',
                    'cause' => "Route callback di downstream mengalami exception internal (HTTP {$code}). Kemungkinan controller mencoba mengakses \$request->get('code') tanpa null check.",
                    'solution_title' => 'Periksa Log Error di Aplikasi Downstream',
                    'solution_steps' => [
                        'Buka file storage/logs/laravel.log di folder downstream.',
                        'Tambahkan pemeriksaan if (! $request->has(\'code\')) { return redirect()->route(\'login\'); }.',
                    ],
                    'solution_code' => 'if (! $request->has(\'code\')) { return redirect()->route(\'login\'); }',
                ];

                return [
                    'check' => [
                        'id' => 'callback_endpoint',
                        'name' => 'Ketersediaan Route Callback SSO',
                        'status' => 'FAIL',
                        'target' => $callbackUrl,
                        'http_code' => $code,
                        'latency_ms' => $latency,
                        'message' => "Route callback melempar galat server internal (HTTP {$code}).",
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
                            'Pastikan web server downstream sedang menyala.',
                            'Pastikan Redirect URI di SiPintu sama persis dengan URL callback downstream.',
                        ],
                        'solution_code' => null,
                    ],
                ],
            ];
        }
    }

    /**
     * 5. Periksa Endpoint Health Check Downstream (/health) & Dekonstruksi Payload JSON
     */
    protected function checkHealthEndpoint(Application $app): array
    {
        $targetUrl = $app->health_check_url ?: rtrim((string) $app->base_url, '/').'/health';
        $startTime = microtime(true);
        $issues = [];

        try {
            $response = Http::timeout(3)->get($targetUrl);
            $latency = round((microtime(true) - $startTime) * 1000, 2);
            $code = $response->status();

            $payload = null;
            try {
                $payload = $response->json();
            } catch (Throwable $e) {
                $payload = null;
            }

            if ($response->successful()) {
                // Dekonstruksi payload JSON: jika downstream mengembalikan status degraded/error di dalam JSON
                if (is_array($payload) && isset($payload['status']) && in_array(strtolower((string) $payload['status']), ['error', 'degraded', 'down', 'fail', 'unhealthy'])) {
                    $issues[] = [
                        'id' => 'HEALTH_PAYLOAD_DEGRADED',
                        'title' => 'Layanan Internal Downstream Melaporkan Degradasi Status',
                        'severity' => 'WARNING',
                        'location' => 'APLIKASI_DOWNSTREAM',
                        'location_label' => 'Aplikasi Downstream',
                        'cause' => "Endpoint /health merespons HTTP 200 namun payload JSON mengindikasikan gangguan internal: status='{$payload['status']}'.",
                        'solution_title' => 'Periksa Komponen Downstream yang Terganggu',
                        'solution_steps' => [
                            'Buka endpoint /health downstream di browser untuk melihat detail komponen yang gagal (misal koneksi database atau redis).',
                        ],
                        'solution_code' => null,
                    ];

                    return [
                        'check' => [
                            'id' => 'health_endpoint',
                            'name' => 'Endpoint Pemantauan Health Check & Status JSON (/health)',
                            'status' => 'WARN',
                            'target' => $targetUrl,
                            'http_code' => $code,
                            'latency_ms' => $latency,
                            'message' => "Endpoint merespons HTTP 200 tetapi payload internal berstatus '{$payload['status']}'.",
                        ],
                        'issues' => $issues,
                        'health_payload' => $payload,
                    ];
                }

                $metaInfo = [];
                if (is_array($payload)) {
                    if (isset($payload['status'])) {
                        $metaInfo[] = "Status: {$payload['status']}";
                    }
                    if (isset($payload['database'])) {
                        $metaInfo[] = "DB: {$payload['database']}";
                    }
                    if (isset($payload['php'])) {
                        $metaInfo[] = "PHP: {$payload['php']}";
                    }
                }
                $metaStr = ! empty($metaInfo) ? ' ['.implode(', ', $metaInfo).']' : '';

                return [
                    'check' => [
                        'id' => 'health_endpoint',
                        'name' => 'Endpoint Pemantauan Health Check & Status JSON (/health)',
                        'status' => 'PASS',
                        'target' => $targetUrl,
                        'http_code' => $code,
                        'latency_ms' => $latency,
                        'message' => "Endpoint /health merespons sempurna (HTTP {$code}, {$latency} ms){$metaStr}.",
                    ],
                    'issues' => [],
                    'health_payload' => $payload,
                ];
            }

            if ($code === 404) {
                $issues[] = [
                    'id' => 'HEALTH_CHECK_MISSING',
                    'title' => 'Endpoint /health Belum Disediakan di Downstream',
                    'severity' => 'WARNING',
                    'location' => 'APLIKASI_DOWNSTREAM',
                    'location_label' => 'Aplikasi Downstream',
                    'cause' => 'Aplikasi downstream belum menyediakan route GET /health. SSO tetap dapat beroperasi, namun metrik kesehatan otomatis tidak dapat terpantau SiPintu.',
                    'solution_title' => 'Tambahkan Route /health di routes/web.php Downstream',
                    'solution_steps' => [
                        'Tambahkan rute GET sederhana di routes/web.php aplikasi downstream.',
                    ],
                    'solution_code' => "Route::get('/health', fn () => response()->json(['status' => 'ok']));",
                ];

                return [
                    'check' => [
                        'id' => 'health_endpoint',
                        'name' => 'Endpoint Pemantauan Health Check & Status JSON (/health)',
                        'status' => 'WARN',
                        'target' => $targetUrl,
                        'http_code' => 404,
                        'latency_ms' => $latency,
                        'message' => 'Endpoint /health belum tersedia (404 Not Found). Disarankan menambahkannya untuk monitoring berkala.',
                    ],
                    'issues' => $issues,
                    'health_payload' => null,
                ];
            }

            $issues[] = [
                'id' => 'HEALTH_CHECK_ERROR',
                'title' => 'Endpoint /health Mengembalikan Galat (HTTP '.$code.')',
                'severity' => 'WARNING',
                'location' => 'APLIKASI_DOWNSTREAM',
                'location_label' => 'Aplikasi Downstream',
                'cause' => "Endpoint /health di downstream mengembalikan status HTTP {$code}.",
                'solution_title' => 'Periksa Implementasi Route /health Downstream',
                'solution_steps' => [
                    'Periksa controller atau closure route /health di aplikasi downstream.',
                    'Pastikan route mengembalikan response HTTP 200 OK.',
                ],
                'solution_code' => "Route::get('/health', fn () => response()->json(['status' => 'ok']));",
            ];

            return [
                'check' => [
                    'id' => 'health_endpoint',
                    'name' => 'Endpoint Pemantauan Health Check & Status JSON (/health)',
                    'status' => 'WARN',
                    'target' => $targetUrl,
                    'http_code' => $code,
                    'latency_ms' => $latency,
                    'message' => "Endpoint /health mengembalikan status HTTP {$code}.",
                ],
                'issues' => $issues,
                'health_payload' => $payload,
            ];
        } catch (Throwable $e) {
            $latency = round((microtime(true) - $startTime) * 1000, 2);

            return [
                'check' => [
                    'id' => 'health_endpoint',
                    'name' => 'Endpoint Pemantauan Health Check & Status JSON (/health)',
                    'status' => 'WARN',
                    'target' => $targetUrl,
                    'http_code' => null,
                    'latency_ms' => $latency,
                    'message' => 'Tidak dapat mengakses /health: '.$e->getMessage(),
                ],
                'issues' => [],
                'health_payload' => null,
            ];
        }
    }

    /**
     * 6. Periksa Endpoint Webhook Sinkronisasi Data Pengguna & Password (Signed Request & CSRF Detection)
     */
    protected function checkPasswordSyncWebhook(Application $app): array
    {
        $userSyncUrl = rtrim((string) $app->base_url, '/').'/api/sipintu/sync-user';
        $passwordSyncUrl = rtrim((string) $app->base_url, '/').'/api/sipintu/sync-password';
        $startTime = microtime(true);
        $issues = [];

        // Buat signature HMAC simulasi resmi dari SiPintu Gateway
        $timestamp = time();
        $testPayload = ['ping' => true, 'timestamp' => $timestamp];
        $signature = hash_hmac('sha256', json_encode($testPayload), (string) ($app->client_secret ?? ''));

        $headers = [
            'Accept' => 'application/json',
            'X-SiPintu-Signature' => $signature,
            'X-SiPintu-Timestamp' => (string) $timestamp,
            'X-SiPintu-Client-Id' => (string) $app->client_id,
        ];

        // 1. Cek endpoint modern /api/sipintu/sync-user terlebih dahulu
        try {
            $response = Http::timeout(3)->withHeaders($headers)->post($userSyncUrl, $testPayload);
            $latency = round((microtime(true) - $startTime) * 1000, 2);
            $code = $response->status();

            if ($code === 200 || $code === 204 || $code === 422) {
                return [
                    'check' => [
                        'id' => 'password_webhook',
                        'name' => 'Webhook Sinkronisasi Profil & Password Real-Time',
                        'status' => 'PASS',
                        'target' => $userSyncUrl,
                        'http_code' => $code,
                        'latency_ms' => $latency,
                        'message' => "Route webhook sinkronisasi otomatis data pengguna aktif dan mengenali payload (HTTP {$code}, {$latency} ms).",
                    ],
                    'issues' => [],
                    'signature_supported' => true,
                ];
            }

            if ($code === 419) {
                $issues[] = [
                    'id' => 'WEBHOOK_CSRF_BLOCKED',
                    'title' => 'Webhook POST Terblokir Middleware CSRF Downstream (HTTP 419)',
                    'severity' => 'WARNING',
                    'location' => 'APLIKASI_DOWNSTREAM',
                    'location_label' => 'Aplikasi Downstream',
                    'cause' => "Route {$userSyncUrl} ditolak oleh VerifyCsrfToken downstream. Webhook API dari server SiPintu tidak membawa CSRF cookie sesi browser.",
                    'solution_title' => 'Pindahkan ke routes/api.php atau Kecualikan CSRF',
                    'solution_steps' => [
                        'Daftarkan route webhook di routes/api.php downstream (bebas CSRF secara default), ATAU',
                        'Kecualikan route api/sipintu/* dari middleware VerifyCsrfToken di bootstrap/app.php (Laravel 11) atau VerifyCsrfToken.php (Laravel 10).',
                    ],
                    'solution_code' => "->validateCsrfTokens(except: ['api/sipintu/*'])",
                ];

                return [
                    'check' => [
                        'id' => 'password_webhook',
                        'name' => 'Webhook Sinkronisasi Profil & Password Real-Time',
                        'status' => 'WARN',
                        'target' => $userSyncUrl,
                        'http_code' => 419,
                        'latency_ms' => $latency,
                        'message' => 'Webhook terhalang proteksi CSRF downstream (HTTP 419). Pindahkan ke routes/api.php.',
                    ],
                    'issues' => $issues,
                    'signature_supported' => false,
                ];
            }
        } catch (Throwable $e) {
            // Lanjutkan ke pengecekan fallback
        }

        // 2. Cek endpoint fallback /api/sipintu/sync-password
        try {
            $fallbackResponse = Http::timeout(3)->withHeaders($headers)->post($passwordSyncUrl, $testPayload);
            $fallbackCode = $fallbackResponse->status();

            if ($fallbackCode !== 404) {
                $latency = round((microtime(true) - $startTime) * 1000, 2);

                return [
                    'check' => [
                        'id' => 'password_webhook',
                        'name' => 'Webhook Sinkronisasi Profil & Password Real-Time',
                        'status' => 'PASS',
                        'target' => $passwordSyncUrl,
                        'http_code' => $fallbackCode,
                        'latency_ms' => $latency,
                        'message' => "Route webhook sinkronisasi password aktif merespons (HTTP {$fallbackCode}). Disarankan upgrade ke /api/sipintu/sync-user untuk sinkronisasi profil penuh.",
                    ],
                    'issues' => [],
                    'signature_supported' => true,
                ];
            }
        } catch (Throwable $e) {
            // Fallback juga gagal
        }

        // 3. Jika kedua endpoint webhook 404 / belum disediakan
        $latency = round((microtime(true) - $startTime) * 1000, 2);
        $issues[] = [
            'id' => 'PASSWORD_WEBHOOK_NOT_CONFIGURED',
            'title' => 'Webhook Sinkronisasi Data Pengguna Belum Disediakan',
            'severity' => 'WARNING',
            'location' => 'APLIKASI_DOWNSTREAM',
            'location_label' => 'Aplikasi Downstream',
            'cause' => 'Aplikasi downstream belum memiliki endpoint POST /api/sipintu/sync-user (atau /api/sipintu/sync-password). Jika data pengguna atau password diubah di SiPintu, downstream tidak akan menerima pembaruan instan di latar belakang.',
            'solution_title' => 'Tambahkan Endpoint Webhook Sinkronisasi di Downstream',
            'solution_steps' => [
                'Sediakan route POST /api/sipintu/sync-user di routes/api.php downstream.',
                'Perbarui profil dan password hash siswa/guru saat menerima panggilan dari SiPintu.',
            ],
            'solution_code' => "Route::post('/api/sipintu/sync-user', [OAuthController::class, 'syncUser']);",
        ];

        return [
            'check' => [
                'id' => 'password_webhook',
                'name' => 'Webhook Sinkronisasi Profil & Password Real-Time',
                'status' => 'WARN',
                'target' => $userSyncUrl,
                'http_code' => 404,
                'latency_ms' => $latency,
                'message' => 'Endpoint webhook belum tersedia (404 Not Found). Data pengguna akan disinkronkan saat login SSO berikutnya.',
            ],
            'issues' => $issues,
            'signature_supported' => false,
        ];
    }

    /**
     * 7. Periksa Audit Keamanan Protokol & Isolasi SSO (Security & Protocol Isolation Audit)
     */
    protected function checkSecurityAndIsolation(Application $app): array
    {
        $status = 'PASS';
        $issues = [];
        $messages = [];

        // Evaluasi penggunaan HTTPS di lingkungan non-lokal
        $baseParsed = parse_url((string) $app->base_url);
        $scheme = strtolower($baseParsed['scheme'] ?? 'http');
        $host = strtolower($baseParsed['host'] ?? '');

        $isLocalHost = in_array($host, ['localhost', '127.0.0.1', '::1'])
            || str_ends_with($host, '.test')
            || str_ends_with($host, '.local')
            || str_starts_with($host, '192.168.')
            || str_starts_with($host, '10.');

        if ($scheme === 'http' && ! $isLocalHost) {
            $status = 'WARN';
            $messages[] = 'Aplikasi menggunakan protokol HTTP tanpa enkripsi SSL di domain publik.';
            $issues[] = [
                'id' => 'INSECURE_HTTP_SCHEME',
                'title' => 'Protokol Tanpa Enkripsi SSL (HTTP Biasa)',
                'severity' => 'WARNING',
                'location' => 'APLIKASI_DOWNSTREAM',
                'location_label' => 'Aplikasi Downstream',
                'cause' => "Base URL '{$app->base_url}' menggunakan skema HTTP di domain publik non-lokal. Authorization code dan token berisiko disadap pihak ketiga di jaringan terbuka.",
                'solution_title' => 'Terapkan Sertifikat SSL/HTTPS',
                'solution_steps' => [
                    'Pasang sertifikat SSL gratis (seperti Let\'s Encrypt atau Cloudflare Flexible).',
                    'Ubah konfigurasi Base URL dan Redirect URI di SiPintu menjadi https://.',
                ],
                'solution_code' => preg_replace('/^http:/', 'https:', (string) $app->base_url),
            ];
        }

        // Cek keamanan Client Secret
        if (empty($app->client_secret)) {
            $status = 'FAIL';
            $messages[] = 'Client Secret kosong.';
            $issues[] = [
                'id' => 'EMPTY_CLIENT_SECRET',
                'title' => 'Client Secret Belum Dibuat',
                'severity' => 'CRITICAL',
                'location' => 'GATEWAY_SIPINTU',
                'location_label' => 'SiPintu Gateway',
                'cause' => 'Aplikasi downstream tidak memiliki client secret sehingga pertukaran authorization code menjadi token ditolak.',
                'solution_title' => 'Buat Ulang Client Secret',
                'solution_steps' => [
                    "Buka panel Admin SiPintu > Menu Aplikasi Eksternal > Klik 'Reset Secret' pada {$app->name}.",
                ],
                'solution_code' => null,
            ];
        } elseif (strlen((string) $app->client_secret) < 16) {
            $status = ($status === 'FAIL') ? 'FAIL' : 'WARN';
            $messages[] = 'Panjang Client Secret kurang dari 16 karakter (terlalu pendek).';
            $issues[] = [
                'id' => 'WEAK_CLIENT_SECRET',
                'title' => 'Client Secret Terlalu Pendek / Lemah',
                'severity' => 'WARNING',
                'location' => 'GATEWAY_SIPINTU',
                'location_label' => 'SiPintu Gateway',
                'cause' => 'Kunci rahasia downstream memiliki entropi rendah (< 16 karakter). Rawan serangan brute-force.',
                'solution_title' => 'Generate Secret Berkekuatan Tinggi',
                'solution_steps' => [
                    "Gunakan tombol 'Reset Secret' untuk menghasilkan token random 32 karakter standar industri.",
                ],
                'solution_code' => null,
            ];
        }

        // Cek anomali Redirect URI (misal mengandung karakter wildcard berbahaya)
        if (str_contains((string) $app->redirect_uri, '*') || str_contains((string) $app->redirect_uri, '#')) {
            $status = 'FAIL';
            $messages[] = 'Redirect URI mengandung wildcard (*) atau fragment (#) yang dilarang OAuth 2.0 RFC 6749.';
            $issues[] = [
                'id' => 'INVALID_REDIRECT_URI_CHARS',
                'title' => 'Redirect URI Mengandung Karakter Ilegal (* atau #)',
                'severity' => 'CRITICAL',
                'location' => 'GATEWAY_SIPINTU',
                'location_label' => 'SiPintu Gateway',
                'cause' => "RFC 6749 melarang wildcard '*' atau hash '#' pada redirect URI OAuth demi mencegah celah eksploitasi open-redirect.",
                'solution_title' => 'Gunakan URL Callback Absolut Spesifik',
                'solution_steps' => [
                    'Hapus karakter * dan # dari Redirect URI.',
                ],
                'solution_code' => rtrim((string) $app->base_url, '/').'/oauth/callback',
            ];
        }

        if (empty($messages)) {
            $messages[] = 'Audit protokol SSL, entropi secret, dan isolasi redirect URI memenuhi standar OAuth 2.0.';
        }

        return [
            'check' => [
                'id' => 'security_audit',
                'name' => 'Audit Keamanan Protokol & Isolasi SSO',
                'status' => $status,
                'target' => $app->redirect_uri,
                'message' => implode(' ', $messages),
                'latency_ms' => 0,
            ],
            'issues' => $issues,
        ];
    }

    /**
     * 8. Periksa Telemetri Sesi Aktif & Siklus Hidup Token (Active Token Lifecycle Telemetry)
     */
    protected function checkTokenAndSessionLifecycle(Application $app): array
    {
        $activeTokens = 0;
        try {
            $activeTokens = OAuthAccessToken::where('application_id', $app->id)
                ->where('revoked', false)
                ->where('expires_at', '>', now())
                ->count();
        } catch (Throwable $e) {
            $activeTokens = 0;
        }

        $lastConnected = $app->last_connected_at ? $app->last_connected_at->diffForHumans() : 'Belum pernah terkoneksi';
        $totalReq = (int) $app->total_api_requests;

        return [
            'check' => [
                'id' => 'token_lifecycle',
                'name' => 'Telemetri Sesi Aktif & Siklus Hidup Token',
                'status' => 'PASS',
                'target' => "App ID #{$app->id}",
                'message' => "Token aktif: {$activeTokens} token | Total requests: {$totalReq} panggilan | Terakhir aktif: {$lastConnected}.",
                'latency_ms' => 0,
            ],
            'issues' => [],
            'active_tokens' => $activeTokens,
        ];
    }

    /**
     * Simpan hasil diagnosa ke database model Application dan cache
     */
    protected function persistDiagnosisResults(Application $app, string $overallStatus, string $statusText, ?float $latencyMs, array $result): void
    {
        try {
            $app->updateQuietly([
                'last_health_status' => match ($overallStatus) {
                    'HEALTHY' => 'online',
                    'WARNING' => 'warning',
                    default => 'offline',
                },
                'last_health_latency_ms' => $latencyMs !== null ? (int) round($latencyMs) : null,
                'last_health_message' => $statusText,
                'last_health_check_at' => now(),
            ]);
        } catch (Throwable $e) {
            // Ignore database write failures in test or restricted environments
        }

        try {
            Cache::put("sipintu_sso_diagnosis_{$app->client_id}", $result, now()->addMinutes(10));
        } catch (Throwable $e) {
            // Ignore cache failure
        }
    }
}
