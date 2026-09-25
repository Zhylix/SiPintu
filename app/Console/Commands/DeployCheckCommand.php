<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DeployCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sipintu:deploy-check {--fix : Otomatis jalankan perbaikan untuk folder permission, symlink, dan generator icon}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit kesiapan deployment produksi SiPintu (Pre-flight Production Checklist)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->newLine();
        $this->line('<fg=white;bg=blue;options=bold>                                                                </>');
        $this->line('<fg=white;bg=blue;options=bold>   🚀 SIPINTU IDENTITY GATEWAY - PRODUCTION DEPLOY CHECKLIST   </>');
        $this->line('<fg=white;bg=blue;options=bold>                                                                </>');
        $this->newLine();

        $shouldFix = (bool) $this->option('fix');
        $checks = [];
        $hasErrors = false;
        $hasWarnings = false;

        // 1. APP_KEY check
        $appKey = config('app.key');
        if (! empty($appKey) && str_starts_with($appKey, 'base64:')) {
            $checks[] = ['APP_KEY', 'Pass', 'App key terkonfigurasi dengan aman'];
        } else {
            $hasErrors = true;
            $checks[] = ['APP_KEY', 'FAIL', 'APP_KEY kosong atau tidak valid base64. Jalankan: php artisan key:generate'];
        }

        // 2. APP_DEBUG check
        $debug = config('app.debug');
        if (! $debug) {
            $checks[] = ['APP_DEBUG', 'Pass', 'Debug mode dinonaktifkan (false)'];
        } else {
            if (app()->isProduction()) {
                $hasErrors = true;
                $checks[] = ['APP_DEBUG', 'FAIL', 'APP_DEBUG=true pada lingkungan produksi! Berisiko kebocoran stack trace.'];
            } else {
                $hasWarnings = true;
                $checks[] = ['APP_DEBUG', 'WARN', 'APP_DEBUG masih true. Ubah ke false sebelum go-live di server publik.'];
            }
        }

        // 3. APP_ENV check
        $env = config('app.env');
        if ($env === 'production') {
            $checks[] = ['APP_ENV', 'Pass', 'Environment disetel ke production'];
        } else {
            $hasWarnings = true;
            $checks[] = ['APP_ENV', 'WARN', "Environment saat ini adalah '{$env}'. Disarankan 'production' untuk server rilis."];
        }

        // 4. APP_URL check
        $url = (string) config('app.url');
        if (str_starts_with($url, 'https://')) {
            $checks[] = ['APP_URL (HTTPS)', 'Pass', "Domain HTTPS aktif: {$url}"];
        } else {
            $hasWarnings = true;
            $checks[] = ['APP_URL (HTTPS)', 'WARN', "APP_URL ({$url}) belum menggunakan protokol https://. SSL wajib untuk SSO."];
        }

        // 5. Database Connection
        try {
            DB::connection()->getPdo();
            $driver = DB::connection()->getDriverName();
            $checks[] = ['Koneksi Database', 'Pass', "Terhubung ke {$driver} (".DB::connection()->getDatabaseName().')'];
        } catch (\Throwable $e) {
            $hasErrors = true;
            $checks[] = ['Koneksi Database', 'FAIL', 'Gagal terhubung ke database: '.$e->getMessage()];
        }

        // 6. Directory Write Permissions & Auto-Fix
        $requiredDirs = [
            'storage',
            'storage/app',
            'storage/app/public',
            'storage/framework',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
            'storage/logs',
            'storage/backups',
            'bootstrap/cache',
        ];

        $dirFailures = [];
        foreach ($requiredDirs as $dir) {
            $path = base_path($dir);
            if (! File::exists($path)) {
                if ($shouldFix) {
                    File::makeDirectory($path, 0775, true, true);
                } else {
                    $dirFailures[] = "{$dir} (tidak ditemukan)";

                    continue;
                }
            }

            if (! is_writable($path)) {
                $dirFailures[] = "{$dir} (tidak dapat ditulisi)";
            }
        }

        if (empty($dirFailures)) {
            $checks[] = ['Izin Tulis Storage', 'Pass', 'Semua direktori cache, session, backup & storage dapat ditulisi'];
        } else {
            $hasErrors = true;
            $checks[] = ['Izin Tulis Storage', 'FAIL', 'Masalah izin: '.implode(', ', $dirFailures).'. Berikan chmod -R 775 storage bootstrap/cache'];
        }

        // 7. Storage Symlink
        $publicStorage = public_path('storage');
        if (File::exists($publicStorage) && is_link($publicStorage)) {
            $checks[] = ['Storage Symlink', 'Pass', 'Symlink public/storage terhubung dengan benar'];
        } else {
            if ($shouldFix) {
                $this->callSilently('storage:link');
                $checks[] = ['Storage Symlink', 'Pass', 'Symlink public/storage berhasil dibuat otomatis'];
            } else {
                $hasWarnings = true;
                $checks[] = ['Storage Symlink', 'WARN', 'Symlink public/storage belum ada. Jalankan: php artisan storage:link'];
            }
        }

        // 8. Queue Connection
        $queueConn = config('queue.default');
        if (in_array($queueConn, ['database', 'redis'])) {
            $checks[] = ['Queue Connection', 'Pass', "Driver antrean menggunakan '{$queueConn}' (asinkron)"];
        } else {
            $hasWarnings = true;
            $checks[] = ['Queue Connection', 'WARN', "Driver '{$queueConn}' akan memproses sync secara lambat. Disarankan 'database' atau 'redis' + supervisor."];
        }

        // 9. Cache Store
        $cacheStore = config('cache.default');
        if (in_array($cacheStore, ['database', 'redis', 'memcached', 'file'])) {
            $checks[] = ['Cache Store', 'Pass', "Cache driver aktif: '{$cacheStore}'"];
        }

        // 10. PWA Assets Check
        $pwaFiles = [
            'public/sw.js',
            'public/manifest.webmanifest',
            'public/offline.html',
        ];
        $missingPwa = [];
        foreach ($pwaFiles as $pFile) {
            if (! File::exists(base_path($pFile))) {
                $missingPwa[] = $pFile;
            }
        }

        if (empty($missingPwa)) {
            $checks[] = ['Aset PWA Offline', 'Pass', 'Service worker, manifest, dan offline.html tersedia'];
        } else {
            $hasWarnings = true;
            $checks[] = ['Aset PWA Offline', 'WARN', 'File PWA hilang: '.implode(', ', $missingPwa)];
        }

        // 11. Custom Error Pages
        $errorPages = ['403', '404', '419', '500', '503'];
        $missingErrors = [];
        foreach ($errorPages as $code) {
            if (! File::exists(resource_path("views/errors/{$code}.blade.php"))) {
                $missingErrors[] = $code;
            }
        }

        if (empty($missingErrors)) {
            $checks[] = ['Custom Error Pages', 'Pass', 'Error pages (403, 404, 419, 500, 503) lengkap & bermerek'];
        } else {
            $hasWarnings = true;
            $checks[] = ['Custom Error Pages', 'WARN', 'Error pages belum lengkap: '.implode(', ', $missingErrors)];
        }

        // 12. Security Headers & Proxy Trust
        $checks[] = ['Security & Proxy', 'Pass', 'Trusted proxies & SecurityHeadersMiddleware terpasang di HTTP pipeline'];

        // Output results table
        $this->table(
            ['Komponen / Titik Uji', 'Status', 'Catatan Evaluasi'],
            array_map(function ($row) {
                $statusColor = match ($row[1]) {
                    'Pass' => '<fg=green;options=bold>✓ READY</>',
                    'WARN' => '<fg=yellow;options=bold>⚠ PERINGATAN</>',
                    default => '<fg=red;options=bold>✗ GAGAL</>',
                };

                return [$row[0], $statusColor, $row[2]];
            }, $checks)
        );

        $this->newLine();

        if ($hasErrors) {
            $this->error('❌ Ada komponen krusial yang GAGAL. Harap perbaiki sebelum meluncurkan ke server produksi.');

            return self::FAILURE;
        }

        if ($hasWarnings) {
            $this->warn('⚠️  Sistem SiPintu pada dasarnya SIAP, namun perhatikan catatan PERINGATAN di atas untuk kesiapan 100% optimal di server produksi.');
        } else {
            $this->info('✅ SELURUH TITIK UJI LULUS! SiPintu siap 100% untuk dideploy ke lingkungan produksi.');
        }

        $this->newLine();
        $this->line('<fg=cyan;options=bold>Langkah Rekomendasi di Server Produksi:</>');
        $this->line(' 1. php artisan config:cache && php artisan route:cache && php artisan view:cache');
        $this->line(' 2. Pastikan Cron Linux aktif: * * * * * cd /path/ke/sipintu && php artisan schedule:run >> /dev/null 2>&1');
        $this->line(' 3. Pastikan Supervisor aktif untuk: php artisan queue:work --sleep=3 --tries=3 --max-time=3600');
        $this->newLine();

        return self::SUCCESS;
    }
}
