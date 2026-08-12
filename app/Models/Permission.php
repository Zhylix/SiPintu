<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
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
        return match ($this->name) {
            'manage-users' => 'Kelola Akun Pengguna',
            'manage-applications' => 'Kelola Aplikasi Eksternal',
            'manage-roles' => 'Kelola Role & Hak Akses',
            'sync-sijuna' => 'Sinkronisasi Data Siswa SIJUNA',
            'view-audit-logs' => 'Lihat Audit Log & Aktivitas',
            'access-external-apps' => 'Akses Aplikasi',
            default => ucwords(str_replace(['-', '_'], ' ', $this->name)),
        };
    }
}
