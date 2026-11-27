<?php

namespace App\Observers;

use App\Jobs\SendDownstreamUserSyncWebhookJob;
use App\Models\User;
use App\Services\UserDataSyncService;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    /**
     * Enable or disable synchronization globally (e.g., during database migrations/seeders).
     */
    public static bool $syncEnabled = true;

    /**
     * Attributes that trigger automatic downstream synchronization when changed.
     */
    protected array $syncableAttributes = [
        'name',
        'email',
        'username',
        'external_id',
        'role',
        'classroom',
        'jurusan_id',
        'phone',
        'avatar',
        'status',
        'password',
    ];

    /**
     * Handle the User "created" event to auto-provision new users in downstream apps.
     */
    public function created(User $user): void
    {
        if (! static::$syncEnabled) {
            return;
        }

        $attributes = array_keys(array_filter($user->getAttributes(), fn ($val) => ! is_null($val)));
        $intersect = array_values(array_intersect($this->syncableAttributes, $attributes));

        if (empty($intersect)) {
            $intersect = ['name', 'email', 'role'];
        }

        if (app()->runningInConsole()) {
            SendDownstreamUserSyncWebhookJob::dispatchSync($user, $intersect, [], true);
        } else {
            SendDownstreamUserSyncWebhookJob::dispatch($user, $intersect, [], true)->afterResponse();
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        if (! static::$syncEnabled) {
            return;
        }

        $changes = array_keys($user->getChanges());
        $intersect = array_values(array_intersect($this->syncableAttributes, $changes));

        if (empty($intersect)) {
            return;
        }

        $previous = [];
        foreach ($intersect as $field) {
            $previous[$field] = $user->getOriginal($field);
        }

        if (app()->runningInConsole()) {
            SendDownstreamUserSyncWebhookJob::dispatchSync($user, $intersect, $previous);
        } else {
            SendDownstreamUserSyncWebhookJob::dispatch($user, $intersect, $previous)->afterResponse();
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        if (! static::$syncEnabled) {
            return;
        }

        $syncAction = function () use ($user) {
            try {
                app(UserDataSyncService::class)->broadcastUserDeletion($user);
            } catch (\Throwable $e) {
                Log::error("[UserObserver] Downstream sync error for deleted user ID {$user->id}: ".$e->getMessage());
            }
        };

        if (app()->runningInConsole()) {
            $syncAction();
        } else {
            dispatch($syncAction)->afterResponse();
        }
    }
}
