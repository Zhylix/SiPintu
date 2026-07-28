<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'external_id',
        'name',
        'email',
        'username',
        'password',
        'user_type',
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

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function permissions()
    {
        return $this->roles->flatMap(function ($role) {
            return $role->permissions;
        })->unique('id');
    }

    public function hasRole(string|array $roles): bool
    {
        if (is_string($roles)) {
            $roles = [$roles];
        }

        return $this->roles()->whereIn('slug', $roles)->exists() || in_array($this->user_type, $roles);
    }

    public function hasPermission(string $permissionSlug): bool
    {
        if ($this->hasRole('admin')) {
            return true;
        }

        foreach ($this->roles as $role) {
            if ($role->permissions()->where('slug', $permissionSlug)->exists()) {
                return true;
            }
        }

        return false;
    }

    public function isStudent(): bool
    {
        return $this->user_type === 'student' || $this->hasRole('student');
    }

    public function isTeacher(): bool
    {
        return $this->user_type === 'teacher' || $this->hasRole('teacher');
    }

    public function isDudi(): bool
    {
        return $this->user_type === 'dudi' || $this->hasRole('dudi');
    }

    public function isAdmin(): bool
    {
        return $this->user_type === 'admin' || $this->hasRole('admin');
    }

    public function canAccessApplication(Application $app): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $allowedRoleIds = $app->roles()->pluck('roles.id')->toArray();
        if (empty($allowedRoleIds)) {
            return false;
        }

        $userRoleIds = $this->roles()->pluck('roles.id')->toArray();

        return !empty(array_intersect($allowedRoleIds, $userRoleIds));
    }

    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }
}
