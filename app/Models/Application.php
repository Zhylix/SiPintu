<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
    ];

    protected $hidden = [
        'client_secret',
    ];

    protected $casts = [
        'last_health_check_at' => 'datetime',
    ];

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
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
