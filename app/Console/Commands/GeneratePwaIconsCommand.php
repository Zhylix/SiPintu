<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Services\PwaIconService;
use Illuminate\Console\Command;

class GeneratePwaIconsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pwa:generate-icons';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and synchronize all PWA icon variants and manifest from active website logo / icon';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Generating PWA icons from current website logo...');

        $siteLogo = Setting::get('site_logo');
        $siteIcon = Setting::get('site_icon');
        $isConnected = Setting::isIconConnectedToLogo();

        if ($siteIcon) {
            $this->line(" - Source: Custom PWA Icon ({$siteIcon})");
        } elseif ($siteLogo) {
            $this->line(" - Source: Website Logo ({$siteLogo}) [Tersambung ke Logo]");
        } else {
            $this->line(' - Source: Default Logo (public/images/logo-smkn1bangsri.png)');
        }

        $success = PwaIconService::generateFromCurrentLogo();

        if ($success) {
            $version = Setting::getIconVersion();
            $this->components->info("PWA Icons berhasil digenerate! Versi Cache: v{$version}");
            $this->line(' Varian icon yang dihasilkan:');
            foreach (PwaIconService::SIZES as $size) {
                $this->line("   - /icons/icon-{$size}x{$size}.png");
            }
            $this->line('   - /icons/icon-maskable-192x192.png');
            $this->line('   - /icons/icon-maskable-512x512.png');
            $this->line('   - /apple-touch-icon.png');
            $this->line('   - /manifest.webmanifest & /manifest.json updated.');

            return self::SUCCESS;
        }

        $this->error('Gagal men-generate PWA icons. Pastikan ekstensi PHP GD terpasang di server.');

        return self::FAILURE;
    }
}
