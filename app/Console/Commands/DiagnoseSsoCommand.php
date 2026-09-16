<?php

namespace App\Console\Commands;

use App\Models\Application;
use App\Services\SsoDiagnosticsService;
use Illuminate\Console\Command;

class DiagnoseSsoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sipintu:sso-diagnose 
                            {client_id? : Client ID aplikasi downstream yang ingin didiagnosa}
                            {--all : Jalankan diagnosa otomatis untuk semua aplikasi terdaftar}
                            {--secret= : Client Secret opsional untuk memvalidasi kesesuaian kunci}
                            {--json : Tampilkan hasil diagnosa dalam format JSON murni}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnosa otomatis koneksi SSO aplikasi downstream dengan 8 titik uji, benchmark latensi, DNS, dan solusi perbaikan.';

    /**
     * Execute the console command.
     */
    public function handle(SsoDiagnosticsService $diagnosticsService): int
    {
        $clientId = $this->argument('client_id');
        $runAll = $this->option('all');
        $secret = $this->option('secret');
        $asJson = (bool) $this->option('json');

        if (! $asJson) {
            $this->info('================================================================');
            $this->info('  🔍 DIAGNOSA OTOMATIS KONEKSI SSO SIPINTU IDENTITY GATEWAY     ');
            $this->info('  8 Titik Uji Presisi • Benchmark Latensi • Analisis Akar Masalah');
            $this->info('================================================================');
            $this->newLine();
        }

        if ($clientId) {
            $app = Application::where('client_id', trim($clientId))->first();
            if (! $app) {
                if ($asJson) {
                    $this->line(json_encode(['status' => 'error', 'message' => "Aplikasi '{$clientId}' tidak ditemukan."]));
                } else {
                    $this->error("Aplikasi downstream dengan Client ID '{$clientId}' tidak ditemukan di database.");
                }

                return self::FAILURE;
            }

            return $this->diagnoseSingleApp($app, $diagnosticsService, $secret, $asJson);
        }

        if ($runAll) {
            return $this->diagnoseAllApps($diagnosticsService, $asJson);
        }

        // Tampilkan daftar aplikasi jika tidak ada argumen
        $apps = Application::all();
        if ($apps->isEmpty()) {
            if ($asJson) {
                $this->line(json_encode(['status' => 'empty', 'message' => 'Belum ada aplikasi terdaftar.']));
            } else {
                $this->warn('Belum ada aplikasi downstream yang terdaftar di sistem.');
            }

            return self::SUCCESS;
        }

        if ($asJson) {
            return $this->diagnoseAllApps($diagnosticsService, true);
        }

        $choices = $apps->mapWithKeys(fn ($a) => [$a->client_id => "{$a->name} ({$a->client_id}) - {$a->base_url}"])->toArray();
        $choices['ALL'] = ' DIAGNOSA SEMUA APLIKASI SEKALIGUS';

        $selected = $this->choice('Pilih aplikasi downstream yang ingin didiagnosa:', $choices, 'ALL');

        if ($selected === 'ALL') {
            return $this->diagnoseAllApps($diagnosticsService, false);
        }

        $app = Application::where('client_id', $selected)->first();

        return $this->diagnoseSingleApp($app, $diagnosticsService, $secret, false);
    }

    protected function diagnoseSingleApp(Application $app, SsoDiagnosticsService $diagnosticsService, ?string $secret = null, bool $asJson = false): int
    {
        $result = $diagnosticsService->diagnose($app, $secret);

        if ($asJson) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $result['overall_status'] === 'CRITICAL' ? self::FAILURE : self::SUCCESS;
        }

        $telemetry = $result['telemetry'] ?? [];

        $this->info("Menjalankan diagnosa koneksi SSO untuk: {$app->name} ({$app->client_id})...");
        $this->line("Base URL       : {$app->base_url}");
        $this->line("Redirect URI   : {$app->redirect_uri}");
        if (! empty($telemetry['resolved_ip'])) {
            $this->line("Resolusi DNS   : <fg=cyan>{$telemetry['resolved_ip']}</> ({$telemetry['ip_classification']})");
        }
        if (! empty($telemetry['latency_grade'])) {
            $latencyGradeLabel = match ($telemetry['latency_grade']) {
                'ultra_fast' => '<fg=green;options=bold>Sangat Cepat (<50ms)</>',
                'optimal' => '<fg=green>Optimal (50-150ms)</>',
                'warning' => '<fg=yellow>Tolerable / Agak Lambat (150-500ms)</>',
                default => '<fg=red;options=bold>Rawan Timeout (>500ms)</>',
            };
            $this->line("Bench Latensi  : {$latencyGradeLabel} ({$telemetry['latency_ms']} ms)");
        }
        $this->newLine();

        // 1. Tampilkan Tabel 8 Titik Pemeriksaan
        $tableData = [];
        foreach ($result['checks'] as $check) {
            $statusBadge = match ($check['status']) {
                'PASS' => '<fg=green;options=bold>[ PASS ]</>',
                'WARN' => '<fg=yellow;options=bold>[ WARN ]</>',
                default => '<fg=red;options=bold>[ FAIL ]</>',
            };

            $latency = isset($check['latency_ms']) && $check['latency_ms'] > 0 ? "{$check['latency_ms']} ms" : '-';
            $code = isset($check['http_code']) && $check['http_code'] ? "HTTP {$check['http_code']}" : '-';

            $tableData[] = [
                $check['name'],
                $statusBadge,
                $code,
                $latency,
                $check['message'],
            ];
        }

        $this->table(['Titik Pengujian Presisi', 'Status', 'Respons', 'Latency', 'Keterangan Analisis'], $tableData);
        $this->newLine();

        // 2. Status Skor Kesehatan Tertimbang
        $scoreColor = match ($result['overall_status']) {
            'HEALTHY' => 'green',
            'WARNING' => 'yellow',
            default => 'red',
        };

        $this->line("Kondisi Keseluruhan: <fg={$scoreColor};options=bold>{$result['status_text']}</> (Weighted Score: {$result['health_score']}%)");
        $this->newLine();

        // 3. Rincian Masalah & Panduan Solusi Perbaikan
        if (! empty($result['issues'])) {
            $this->warn('----------------------------------------------------------------');
            $this->warn('  ⚠️ AKAR MASALAH TERDETEKSI & PANDUAN CARA MEMPERBAIKI:');
            $this->warn('----------------------------------------------------------------');

            foreach ($result['issues'] as $idx => $issue) {
                $num = $idx + 1;
                $sevColor = $issue['severity'] === 'CRITICAL' ? 'red' : 'yellow';

                $this->newLine();
                $this->line("<fg={$sevColor};options=bold>MASALAH #{$num}: [{$issue['severity']}] {$issue['title']}</>");
                $this->line("  📍 Lokasi Terjadinya : <options=bold>{$issue['location_label']}</>");
                $this->line("  ❓ Mengapa Terjadi   : {$issue['cause']}");
                $this->line("  🛠️  Solusi Perbaikan : <fg=cyan;options=bold>{$issue['solution_title']}</>");

                if (! empty($issue['solution_steps'])) {
                    foreach ($issue['solution_steps'] as $step) {
                        $this->line("     • {$step}");
                    }
                }

                if (! empty($issue['solution_code'])) {
                    $this->line('     Contoh Kode / Perintah:');
                    $this->line("     <fg=green;bg=black>  {$issue['solution_code']}  </>");
                }
            }
            $this->newLine();
        } else {
            $this->info('🎉 Luar biasa! Seluruh 8 titik pengujian SSO berhasil tanpa ada kendala.');
        }

        return $result['overall_status'] === 'CRITICAL' ? self::FAILURE : self::SUCCESS;
    }

    protected function diagnoseAllApps(SsoDiagnosticsService $diagnosticsService, bool $asJson = false): int
    {
        $summary = $diagnosticsService->diagnoseAll();

        if ($asJson) {
            $this->line(json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        $this->info('Menjalankan diagnosa massal untuk seluruh aplikasi downstream...');

        $tableData = [];
        foreach ($summary['results'] as $res) {
            $app = $res['application'];
            $statusBadge = match ($res['overall_status']) {
                'HEALTHY' => '<fg=green;options=bold>[ 🟢 SEHAT ]</>',
                'WARNING' => '<fg=yellow;options=bold>[ 🟡 PERINGATAN ]</>',
                default => '<fg=red;options=bold>[ 🔴 KRITIS ]</>',
            };

            $tableData[] = [
                $app['name'],
                $app['client_id'],
                $app['base_url'],
                $statusBadge,
                "{$res['health_score']}%",
                "{$res['summary']['issues_count']} Isu",
            ];
        }

        $this->table(['Aplikasi', 'Client ID', 'Base URL', 'Status SSO', 'Skor WHI', 'Temuan'], $tableData);
        $this->newLine();

        $this->info("Total: {$summary['total_applications']} Aplikasi | 🟢 Sehat: {$summary['healthy_count']} | 🟡 Peringatan: {$summary['warning_count']} | 🔴 Kritis: {$summary['critical_count']}");
        $this->line('Gunakan: php artisan sipintu:sso-diagnose [client_id] untuk melihat rincian solusi tiap aplikasi.');

        return self::SUCCESS;
    }
}
