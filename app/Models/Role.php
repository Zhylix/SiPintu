<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role as SpatieRole;

use Illuminate\Support\Str;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'slug',
        'guard_name',
    ];

    protected static function booted(): void
    {
        static::creating(function (Role $role) {
            if (empty($role->slug) && ! empty($role->name)) {
                $role->slug = Str::slug($role->name);
            }
        });

        static::updating(function (Role $role) {
            if (empty($role->slug) && ! empty($role->name)) {
                $role->slug = Str::slug($role->name);
            }
        });
    }

    public static function create(array $attributes = [])
    {
        if (empty($attributes['slug']) && ! empty($attributes['name'])) {
            $attributes['slug'] = Str::slug($attributes['name']);
        }

        return parent::create($attributes);
    }

    public function getSlugAttribute(?string $value): string
    {
        return $value ?: Str::slug($this->name ?? '');
    }

    public function getDisplayName(): string
    {
        return match (strtolower($this->name)) {
            'student', 'siswa' => 'Siswa',
            'alumni' => 'Alumni',
            'teacher', 'guru' => 'Guru',
            'dudi', 'mitra' => 'Mitra DUDI',
            'admin', 'administrator' => 'Administrator',
            default => ucfirst($this->name),
        };
    }

    public function getDescription(): string
    {
        return match (strtolower($this->name)) {
            'admin', 'administrator' => 'Administrator dengan akses penuh untuk mengelola pengguna, pendaftaran aplikasi, peran, dan audit sistem.',
            'teacher', 'guru' => 'Guru dan Tenaga Pendidik SMKN 1 Bangsri yang dapat mengakses portal guru dan aplikasi terintegrasi.',
            'dudi', 'mitra' => 'Mitra Dunia Usaha & Dunia Industri yang bekerjasama dengan sekolah untuk program PKL dan sistem terintegrasi.',
            'student', 'siswa' => 'Siswa SMKN 1 Bangsri yang disinkronkan secara otomatis dari sistem SIJUNA.',
            'alumni' => 'Alumni SMKN 1 Bangsri yang disinkronkan secara otomatis dari sistem SIJUNA.',
            default => 'Peran pengguna dalam gateway sekolah.',
        };
    }

    public function applications(): BelongsToMany
    {
        return $this->belongsToMany(Application::class, 'application_role');
    }
}
