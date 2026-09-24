<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\BlockedIp;
use App\Models\OAuthAccessToken;
use App\Models\OAuthAuthCode;
use App\Models\OAuthRefreshToken;
use App\Models\SecurityLog;
use App\Models\WhatsAppLog;
use Illuminate\Console\Command;

class PruneExpiredTokensAndLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sipintu:prune {--days=90 : Jumlah hari retensi log}';

    /**
     * The console command description.
     */
    protected $description = 'Bersihkan token OAuth kedaluwarsa dan pangkas log lama untuk menjaga performa database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai pembersihan data kedaluwarsa SiPintu Gateway...');
        $days = (int) $this->option('days');
        $logThreshold = now()->subDays($days);

        // 1. Prune Expired & Revoked OAuth Auth Codes (> 1 jam)
        $deletedCodes = OAuthAuthCode::where('expires_at', '<', now()->subHour())
            ->orWhere(function ($q) {
                $q->where('revoked', true)->where('expires_at', '<', now());
            })
            ->delete();
        $this->line("- Dihapus {$deletedCodes} OAuth Auth Code yang kedaluwarsa.");

        // 2. Prune Expired & Revoked OAuth Tokens (> 30 hari)
        $deletedRefreshTokens = OAuthRefreshToken::where('expires_at', '<', now()->subDays(30))
            ->orWhere(function ($q) {
                $q->where('revoked', true)->where('expires_at', '<', now());
            })
            ->delete();
        $this->line("- Dihapus {$deletedRefreshTokens} Refresh Token yang kedaluwarsa.");

        $deletedAccessTokens = OAuthAccessToken::where('expires_at', '<', now()->subDays(30))
            ->orWhere(function ($q) {
                $q->where('revoked', true)->where('expires_at', '<', now());
            })
            ->delete();
        $this->line("- Dihapus {$deletedAccessTokens} Access Token yang kedaluwarsa.");

        // 3. Prune Expired Temporary Blocked IPs (> 7 hari setelah expired)
        $deletedBlockedIps = BlockedIp::whereNotNull('expires_at')
            ->where('expires_at', '<', now()->subDays(7))
            ->delete();
        $this->line("- Dihapus {$deletedBlockedIps} riwayat IP terblokir yang sudah kedaluwarsa.");

        // 4. Prune Old WhatsApp Logs (> $days)
        $deletedWaLogs = WhatsAppLog::where('created_at', '<', $logThreshold)->delete();
        $this->line("- Dihapus {$deletedWaLogs} log WhatsApp yang lebih lama dari {$days} hari.");

        // 5. Prune Old Audit & Security Logs (> $days * 2)
        $auditThreshold = now()->subDays($days * 2);
        $deletedAuditLogs = AuditLog::where('created_at', '<', $auditThreshold)->delete();
        $deletedSecurityLogs = SecurityLog::where('created_at', '<', $auditThreshold)->delete();
        $this->line("- Dihapus {$deletedAuditLogs} Audit Log dan {$deletedSecurityLogs} Security Log lama.");

        $this->info('Pembersihan database berhasil diselesaikan!');

        return Command::SUCCESS;
    }
}
