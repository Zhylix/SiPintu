<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Application extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category_id',
        'description',
        'base_url',
        'icon',
        'client_id',
        'client_secret',
        'redirect_uri',
        'logout_uri',
        'scopes',
        'status',
        'health_check_url',
        'last_health_status',
        'last_health_check_at',
        'last_connected_at',
        'last_connected_ip',
        'connection_status',
        'total_api_requests',
    ];

    protected $hidden = [
        'client_secret',
    ];

    protected $casts = [
        'last_health_check_at' => 'datetime',
        'last_connected_at' => 'datetime',
        'total_api_requests' => 'integer',
    ];

    /**
     * Record API connection event from downstream application
     */
    public function recordApiConnection(?string $ipAddress = null): void
    {
        $this->update([
            'last_connected_at' => now(),
            'last_connected_ip' => $ipAddress ?: request()?->ip(),
            'connection_status' => 'connected',
            'total_api_requests' => $this->total_api_requests + 1,
        ]);
    }

    /**
     * Get real-time connection status (Connected if accessed in last 15 minutes)
     */
    public function getRealtimeConnectionStatusAttribute(): string
    {
        if (! $this->last_connected_at) {
            return 'never_connected';
        }

        return $this->last_connected_at->diffInMinutes(now()) <= 15 ? 'connected' : 'disconnected';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ApplicationCategory::class, 'category_id');
    }

    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_favorite_applications')->withTimestamps();
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'application_role');
    }

    public function isRoleAllowed(Role|string $role): bool
    {
        $roleSlug = is_string($role) ? $role : $role->slug;

        return $this->roles()->where('slug', $roleSlug)->exists();
    }
}
