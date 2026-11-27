<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\UserDataSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendDownstreamUserSyncWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public array $backoff = [10, 30, 60];

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public User $user,
        public array $changes = [],
        public array $previous = [],
        public bool $force = false
    ) {}

    /**
     * Execute the job.
     */
    public function handle(UserDataSyncService $syncService): void
    {
        try {
            $syncService->broadcastUserUpdate(
                user: $this->user,
                changes: $this->changes,
                previous: $this->previous,
                force: $this->force
            );
        } catch (\Throwable $e) {
            Log::error("[SendDownstreamUserSyncWebhookJob] Gagal mengirim webhook user ID {$this->user->id}: ".$e->getMessage());
            throw $e;
        }
    }
}
