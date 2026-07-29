<?php

namespace App\Jobs;

use App\Models\Application;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckApplicationHealthJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        $applications = Application::whereNotNull('health_check_url')->get();

        foreach ($applications as $app) {
            try {
                $response = Http::timeout(5)->get($app->health_check_url);
                $status = $response->successful() ? 'online' : 'warning';
            } catch (Exception $e) {
                $status = 'offline';
                Log::warning("Health check failed for application {$app->name}: ".$e->getMessage());
            }

            $app->update([
                'last_health_status' => $status,
                'last_health_check_at' => now(),
            ]);
        }
    }
}
