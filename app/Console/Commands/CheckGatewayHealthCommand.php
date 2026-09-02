<?php

namespace App\Console\Commands;

use App\Services\GatewayHealthValidationService;
use Illuminate\Console\Command;

class CheckGatewayHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sipintu:client-check {--client-id= : Client ID aplikasi downstream yang ingin divalidasi} {--secret= : Client Secret aplikasi downstream}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cek & validasi status koneksi aplikasi downstream (client apps) yang terhubung ke REST API SiPintu';

    /**
     * Execute the console command.
     */
    public function handle(GatewayHealthValidationService $validator): int
    {
        $this->info('===============================================================');
        $this->info('  STATUS KONEKSI APLIKASI DOWNSTREAM KE SIPINTU REST API GATEWAY ');
        $this->info('===============================================================');
        $this->newLine();

        $summary = $validator->getDownstreamClientsSummary();

        $this->components->info("Total Aplikasi Downstream Terdaftar: {$summary['total_applications']}");
        $this->line("   - [ONLINE] Terkoneksi (Aktif): {$summary['connected_count']} aplikasi");
        $this->line("   - [OFFLINE] Terputus (Inaktif): {$summary['disconnected_count']} aplikasi");
        $this->line("   - [NEVER] Belum Pernah Terkoneksi: {$summary['never_connected_count']} aplikasi");
        $this->newLine();

        $tableData = [];
        foreach ($summary['clients'] as $client) {
            $statusLabel = match ($client['connection_status']) {
                'connected' => '[ONLINE] TERKONEKSI',
                'disconnected' => '[OFFLINE] TERPUTUS',
                default => '[NEVER] BELUM PERNAH',
            };

            $tableData[] = [
                'ID' => $client['id'],
                'Nama Aplikasi' => $client['name'],
                'Client ID' => $client['client_id'],
                'Status Koneksi' => $statusLabel,
                'Terakhir Terkoneksi' => $client['last_connected_human'],
                'IP Terakhir' => $client['last_connected_ip'],
                'Total Request' => number_format($client['total_api_requests']),
            ];
        }

        $this->table(['ID', 'Nama Aplikasi', 'Client ID', 'Status Koneksi', 'Terakhir Terkoneksi', 'IP Terakhir', 'Total Request'], $tableData);

        // Validate specific Client ID if provided
        $clientId = $this->option('client-id');
        $secret = $this->option('secret');

        if ($clientId) {
            $this->newLine();
            $this->info("Memvalidasi Koneksi Aplikasi Downstream Client ID: '{$clientId}'...");
            $result = $validator->validateClientConnection($clientId, $secret, true);

            if ($result['valid']) {
                $this->components->info("[OK] KONEKSI VALID! {$result['message']}");
                if (isset($result['application'])) {
                    $this->line("   - Aplikasi: {$result['application']['name']} ({$result['application']['client_id']})");
                    $this->line("   - Status: {$result['application']['connection_status']}");
                    $this->line("   - Last Connected: {$result['application']['last_connected_human']}");
                }
            } else {
                $this->components->error("[ERROR] KONEKSI GAGAL! {$result['message']}");
            }
        }

        $this->newLine();

        return Command::SUCCESS;
    }
}
