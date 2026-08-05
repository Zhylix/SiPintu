<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

class TestSsoHealthCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sipintu:sso-health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uji kesehatan sistem OAuth 2.0 / SSO Gateway dan verifikasi bypassing CSRF';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memeriksa Kesehatan Sistem SSO SiPintu Gateway...');
        $this->newLine();

        $routes = [
            'oauth.authorize' => 'GET /oauth/authorize',
            'oauth.token' => 'POST /oauth/token',
            'oauth.logout' => 'POST /oauth/logout',
            'oauth.well-known' => 'GET /.well-known/openid-configuration',
            'oauth.jwks' => 'GET /oauth/jwks.json',
            'api.v1.user' => 'GET /api/v1/user',
        ];

        $results = [];

        foreach ($routes as $name => $endpoint) {
            $exists = Route::has($name);
            $results[] = [
                'Endpoint' => $endpoint,
                'Route Name' => $name,
                'Status Routing' => $exists ? 'READY' : 'MISSING',
            ];
        }

        $this->table(['Endpoint', 'Route Name', 'Status Routing'], $results);

        // Test POST /oauth/token CSRF bypass via local HTTP call
        $baseUrl = config('app.url', 'http://localhost:8000');
        $this->info("Melakukan pengujian koneksi HTTP ke {$baseUrl}/oauth/token...");

        try {
            $response = Http::asForm()
                ->acceptJson()
                ->post("{$baseUrl}/oauth/token", [
                    'grant_type' => 'authorization_code',
                    'client_id' => 'health_check_dummy',
                    'client_secret' => 'dummy',
                    'code' => 'dummy',
                    'redirect_uri' => 'http://localhost/callback',
                ]);

            if ($response->status() === 401 && ($response->json('error') === 'invalid_client' || $response->json('error') === 'invalid_grant')) {
                $this->components->info('✓ Tes CSRF Bypass BERHASIL! Respons JSON OAuth 2.0 diterima dengan sempurna (Status 401).');
            } elseif ($response->status() === 400 && $response->json('error') === 'invalid_grant') {
                $this->components->info('✓ Tes CSRF Bypass BERHASIL! Respons JSON OAuth 2.0 diterima dengan sempurna (Status 400).');
            } elseif (str_contains((string) $response->body(), 'CSRF token mismatch')) {
                $this->components->error('✗ Tes CSRF Bypass GAGAL! Respons masih menghasilkan CSRF Token Mismatch.');
            } else {
                $this->components->warn("Respons diterima dengan kode HTTP {$response->status()}: ".substr((string) $response->body(), 0, 100));
            }
        } catch (\Exception $e) {
            $this->components->warn("Pastikan server berjalan di {$baseUrl} (php artisan serve --port=8000). Pesan: ".$e->getMessage());
        }

        return Command::SUCCESS;
    }
}
