<?php

namespace App\Console\Commands;

use App\Models\Application;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateSsoClientCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sipintu:sso-client
                            {name? : Nama aplikasi klien (contoh: TESApi App)}
                            {--redirect= : Redirect URI Callback SSO (contoh: http://localhost:8001/oauth/callback)}
                            {--base-url= : Base URL Aplikasi Klien (contoh: http://localhost:8001)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Buat dan daftarkan kredensial SSO Client Application (Client ID & Client Secret) secara instan';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('====================================================');
        $this->info('      SiPintu Gateway SSO Client App Generator     ');
        $this->info('====================================================');

        $name = $this->argument('name') ?? $this->ask('Masukkan Nama Aplikasi Klien', 'Aplikasi Klien Dev');
        $redirectUri = $this->option('redirect') ?? $this->ask('Masukkan Redirect URI Callback', 'http://localhost:8001/oauth/callback');
        $baseUrl = $this->option('base-url') ?? $this->ask('Masukkan Base URL Aplikasi Klien', 'http://localhost:8001');

        $clientId = 'app_'.strtolower(Str::random(12));
        $clientSecret = 'sec_'.Str::random(32);
        $slug = Str::slug($name).'-'.Str::random(4);

        $application = Application::create([
            'name' => $name,
            'slug' => $slug,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'base_url' => $baseUrl,
            'scopes' => 'openid profile email',
            'status' => 'active',
            'icon' => 'app-symbol',
        ]);

        $this->newLine();
        $this->components->info('Kredensial SSO Berhasil Dibuat dan Didaftarkan!');

        $this->table(
            ['Parameter', 'Nilai'],
            [
                ['ID Aplikasi', $application->id],
                ['Nama Aplikasi', $application->name],
                ['Client ID', $clientId],
                ['Client Secret', $clientSecret],
                ['Redirect URI', $redirectUri],
                ['Base URL', $baseUrl],
                ['Status', 'ACTIVE'],
            ]
        );

        $this->newLine();
        $this->info('Salin variabel berikut ke file .env aplikasi klien Anda:');
        $this->comment('----------------------------------------------------');
        $this->line('SIPINTU_BASE_URL=http://localhost:8000');
        $this->line("SIPINTU_CLIENT_ID={$clientId}");
        $this->line("SIPINTU_CLIENT_SECRET={$clientSecret}");
        $this->line("SIPINTU_REDIRECT_URI={$redirectUri}");
        $this->comment('----------------------------------------------------');

        return Command::SUCCESS;
    }
}
