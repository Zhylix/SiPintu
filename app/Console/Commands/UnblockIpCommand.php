<?php

namespace App\Console\Commands;

use App\Models\BlockedIp;
use App\Services\SecurityService;
use Illuminate\Console\Command;

class UnblockIpCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:unblock 
                            {ip? : IP address yang ingin dibuka blokirnya} 
                            {--all : Buka blokir seluruh IP address yang sedang aktif}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Buka blokir IP address tertentu atau seluruh IP yang terblokir oleh proteksi keamanan';

    /**
     * Execute the console command.
     */
    public function handle(SecurityService $securityService): int
    {
        $ip = $this->argument('ip');
        $all = $this->option('all');

        if ($all) {
            $activeBlocks = BlockedIp::where('is_active', true)->get();

            if ($activeBlocks->isEmpty()) {
                $this->info('Tidak ada IP address yang sedang terblokir saat ini.');

                return Command::SUCCESS;
            }

            foreach ($activeBlocks as $block) {
                $block->update([
                    'is_active' => false,
                    'expires_at' => now(),
                ]);
                $securityService->clearAllIpSecurityCache($block->ip_address);
            }

            $count = $activeBlocks->count();
            $this->info("Berhasil membuka blokir {$count} IP address.");

            return Command::SUCCESS;
        }

        if (empty($ip)) {
            $activeBlocks = BlockedIp::where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->get();

            if ($activeBlocks->isEmpty()) {
                $this->info('Tidak ada IP address yang sedang terblokir saat ini.');

                return Command::SUCCESS;
            }

            $this->table(
                ['ID', 'IP Address', 'Alasan', 'Berakhir Pada'],
                $activeBlocks->map(fn ($b) => [
                    $b->id,
                    $b->ip_address,
                    $b->reason,
                    $b->expires_at ? $b->expires_at->toDateTimeString() : 'Permanen',
                ])
            );

            $this->comment('Gunakan: php artisan security:unblock <IP_ADDRESS> atau php artisan security:unblock --all');

            return Command::SUCCESS;
        }

        $blocked = BlockedIp::where('ip_address', $ip)->first();
        if ($blocked) {
            $blocked->update([
                'is_active' => false,
                'expires_at' => now(),
            ]);
        }

        $securityService->clearAllIpSecurityCache($ip);
        $this->info("Blokir pada IP address [{$ip}] berhasil dibuka dan cache dibersihkan.");

        return Command::SUCCESS;
    }
}
