<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\UserDataSyncService;
use Illuminate\Console\Command;

class SyncDownstreamUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sipintu:sync-user
                            {identifier : User ID, Email, or Username/NIS/NIP}
                            {--force : Force sync even if already broadcasted recently}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manually broadcast a user data synchronization payload to all active downstream applications';

    /**
     * Execute the console command.
     */
    public function handle(UserDataSyncService $syncService): int
    {
        $identifier = $this->argument('identifier');

        $user = User::where('id', $identifier)
            ->orWhere('email', $identifier)
            ->orWhere('username', $identifier)
            ->orWhere('external_id', $identifier)
            ->first();

        if (! $user) {
            $this->error("❌ Pengguna dengan identifier '{$identifier}' tidak ditemukan di database SiPintu.");

            return Command::FAILURE;
        }

        $this->info('🚀 Memulai sinkronisasi data pengguna ke seluruh aplikasi downstream...');
        $this->line("   ID Pengguna  : <comment>{$user->id}</comment>");
        $this->line("   Nama Lengkap : <comment>{$user->name}</comment>");
        $this->line("   Email        : <comment>{$user->email}</comment>");
        $this->line("   Role         : <comment>{$user->role}</comment>");
        $this->line('   NIS / NIP    : <comment>'.($user->external_id ?: '-').'</comment>');
        $this->newLine();

        $result = $syncService->broadcastUserUpdate($user, force: (bool) $this->option('force'));

        if (($result['status'] ?? null) === 'skipped') {
            $this->warn('⚠️ '.$result['message']);

            return Command::SUCCESS;
        }

        $details = $result['details'] ?? [];

        $tableRows = [];
        $hasError = false;

        foreach ($details as $appId => $detail) {
            $status = $detail['status'] ?? 'unknown';
            $statusBadge = match ($status) {
                'synced' => '<info>SYNCED (200)</info>',
                'failed' => '<comment>FAILED ('.($detail['http_code'] ?? 'ERR').')</comment>',
                default => '<error>ERROR</error>',
            };

            if ($status !== 'synced') {
                $hasError = true;
            }

            $tableRows[] = [
                $detail['app_name'] ?? "App #{$appId}",
                $detail['client_id'] ?? '-',
                $detail['target_url'] ?? '-',
                ($detail['latency_ms'] ?? 0).' ms',
                $statusBadge,
            ];
        }

        $this->table(
            ['Nama Aplikasi', 'Client ID', 'Target Endpoint Webhook', 'Latency', 'Status'],
            $tableRows
        );

        if ($hasError) {
            $this->warn('⚠️ Sebagian aplikasi downstream gagal merespons webhook sinkronisasi.');
        } else {
            $this->info('✅ Seluruh aplikasi downstream berhasil disinkronkan secara realtime!');
        }

        return Command::SUCCESS;
    }
}
