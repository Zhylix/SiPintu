<?php

namespace App\Observers;

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
        'phone',
        'avatar',
        'status',
        'password',
    ];

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

        try {
            app(UserDataSyncService::class)->broadcastUserUpdate($user, $intersect, $previous);
        } catch (\Throwable $e) {
            Log::error("[UserObserver] Downstream sync error for user ID {$user->id}: ".$e->getMessage());
        }
    }
}
