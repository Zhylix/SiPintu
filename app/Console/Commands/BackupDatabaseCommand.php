<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;

class BackupDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sipintu:backup {--retention=7 : Jumlah hari retensi file backup}';

    /**
     * The console command description.
     */
    protected $description = 'Cadangkan database SiPintu (MySQL/MariaDB) secara otomatis ke storage lokal dan bersihkan backup kedaluwarsa';

    /**
     * Execute the console command.
     */
    public function handle(DatabaseBackupService $backupService): int
    {
        $this->info('Memulai pencadangan database SiPintu...');
        $retentionDays = max(1, (int) $this->option('retention'));

        $startTime = microtime(true);
        $result = $backupService->createBackup($retentionDays);
        $duration = round(microtime(true) - $startTime, 2);

        if (! $result['success']) {
            $this->error('Gagal mencadangkan database SiPintu!');
            $this->line("- Pesan: {$result['message']}");

            return Command::FAILURE;
        }

        $this->info('Pencadangan database SiPintu BERHASIL!');
        $this->line("- File: {$result['filename']}");
        $this->line("- Ukuran: {$result['filesize_human']}");
        $this->line("- Metode: {$result['method']}");
        $this->line("- Durasi: {$duration} detik");
        $this->line("- Retensi: {$retentionDays} hari (Dihapus: {$result['deleted_old_count']} file lama)");

        return Command::SUCCESS;
    }
}
