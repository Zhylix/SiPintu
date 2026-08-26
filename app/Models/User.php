<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'external_id',
        'name',
        'email',
        'username',
        'password',
        'role',
        'classroom',
        'phone',
        'avatar',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getUserTypeAttribute(): ?string
    {
        return $this->role;
    }

    public function hasPermission(string $permissionSlug): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        try {
            return $this->hasPermissionTo($permissionSlug);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function isStudent(): bool
    {
        return in_array($this->role, ['student', 'siswa']) || $this->hasRole(['student', 'siswa']);
    }

    public function isTeacher(): bool
    {
        return in_array($this->role, ['teacher', 'guru']) || $this->hasRole(['teacher', 'guru']);
    }

    public function isDudi(): bool
    {
        return in_array($this->role, ['dudi', 'mitra']) || $this->hasRole(['dudi', 'mitra']);
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'administrator']) || $this->hasRole(['admin', 'administrator']);
    }

    public function isAlumni(): bool
    {
        return in_array($this->role, ['alumni']) || $this->hasRole(['alumni']);
    }

    public function getUserTypeName(): string
    {
        if ($this->isAdmin()) {
            return 'Administrator';
        }
        if ($this->isTeacher()) {
            return 'Guru';
        }
        if ($this->isDudi()) {
            return 'Mitra DUDI';
        }
        if ($this->isAlumni()) {
            return 'Alumni';
        }
        if ($this->isStudent()) {
            return 'Siswa';
        }

        return ucfirst($this->role ?? 'User');
    }

    public function canAccessApplication(Application $app): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $allowedRoleSlugs = array_filter(array_merge(
            $app->roles()->pluck('roles.slug')->toArray(),
            $app->roles()->pluck('roles.name')->toArray()
        ));

        if (empty($allowedRoleSlugs)) {
            return false;
        }

        $userRoleSlugs = array_filter(array_merge(
            $this->roles()->pluck('roles.slug')->toArray(),
            $this->roles()->pluck('roles.name')->toArray(),
            [$this->role]
        ));

        return ! empty(array_intersect($allowedRoleSlugs, $userRoleSlugs));
    }

    public function favoriteApplications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'user_favorite_applications')
            ->withTimestamps()
            ->withPivot('sort_order');
    }

    public function externalIds(): HasMany
    {
        return $this->hasMany(UserExternalId::class);
    }

    public function hasFavorited(Application|int $application): bool
    {
        $appId = $application instanceof Application ? $application->id : $application;

        return $this->favoriteApplications()->where('application_id', $appId)->exists();
    }

    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
