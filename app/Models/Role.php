<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
    ];

    public function getSlugAttribute(): string
    {
        return $this->name;
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
