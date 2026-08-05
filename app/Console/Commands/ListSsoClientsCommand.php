<?php

namespace App\Console\Commands;

use App\Models\Application;
use Illuminate\Console\Command;

class ListSsoClientsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sipintu:sso-list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tampilkan daftar seluruh aplikasi SSO Klien yang terdaftar di SiPintu Gateway';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $applications = Application::select(['id', 'name', 'client_id', 'redirect_uri', 'base_url', 'status', 'created_at'])->get();

        if ($applications->isEmpty()) {
            $this->warn('Belum ada aplikasi klien SSO yang terdaftar.');
            $this->info('Jalankan: php artisan sipintu:sso-client untuk mendaftarkan aplikasi pertama Anda.');

            return Command::SUCCESS;
        }

        $this->info('Daftar Aplikasi SSO Klien Terdaftar:');

        $rows = $applications->map(fn ($app) => [
            'ID' => $app->id,
            'Nama Aplikasi' => $app->name,
            'Client ID' => $app->client_id,
            'Redirect URI' => $app->redirect_uri,
            'Base URL' => $app->base_url ?? '-',
            'Status' => strtoupper($app->status),
            'Terdaftar' => $app->created_at?->format('Y-m-d H:i') ?? '-',
        ])->toArray();

        $this->table(
            ['ID', 'Nama Aplikasi', 'Client ID', 'Redirect URI', 'Base URL', 'Status', 'Terdaftar'],
            $rows
        );

        return Command::SUCCESS;
    }
}
