<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
        'jurusan_id',
        'phone',
        'avatar',
        'wa_notify',
        'status',
        'must_change_password',
        'sipintu_last_synced_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'tahun_masuk',
        'tahun_lulus',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'wa_notify' => 'boolean',
            'must_change_password' => 'boolean',
            'sipintu_last_synced_at' => 'datetime',
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

    /**
     * Determine whether the user is still using the initial default password ('password').
     */
    public function isUsingDefaultPassword(): bool
    {
        return ! empty($this->password) && Hash::check('password', $this->password);
    }

    /**
     * Check if user needs to update their initial password.
     */
    public function needsPasswordChange(): bool
    {
        return (bool) ($this->must_change_password || $this->isUsingDefaultPassword());
    }

    /**
     * Check if user has not yet configured a valid WhatsApp phone number.
     */
    public function needsWhatsAppPhone(): bool
    {
        $clean = preg_replace('/[^\d]/', '', (string) $this->phone);

        return empty($clean) || strlen($clean) < 8;
    }

    /**
     * Check if user requires the security onboarding prompt (either default password or missing phone).
     */
    public function needsSecurityOnboarding(): bool
    {
        return $this->needsPasswordChange() || $this->needsWhatsAppPhone();
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

    /**
     * Get the avatar URL for the user.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (empty($this->avatar)) {
            return null;
        }

        if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
            return $this->avatar;
        }

        return Storage::disk('public')->url($this->avatar);
    }

    public function getNisAttribute(): ?string
    {
        if ($this->isStudent() || $this->isAlumni()) {
            return $this->external_id ?: $this->username;
        }

        return null;
    }

    public function getNipAttribute(): ?string
    {
        if ($this->isTeacher()) {
            return $this->external_id ?: $this->username;
        }

        return null;
    }

    public function getDudiCodeAttribute(): ?string
    {
        if ($this->isDudi()) {
            return $this->external_id ?: $this->username;
        }

        return null;
    }

    /**
     * Relasi ke Jurusan (PPL, TO, AKL, PM, MPLB)
     */
    public function jurusan(): BelongsTo
    {
        return $this->belongsTo(Jurusan::class, 'jurusan_id');
    }

    /**
     * Helper statis untuk mengekstrak kode jurusan dari kelas atau raw data
     */
    public static function extractJurusanFromClassroom(?string $classroom, ?string $rawJurusan = null): ?string
    {
        return Jurusan::extractKodeJurusan($classroom, $rawJurusan);
    }

    /**
     * Pasangkan jurusan otomatis ke user ini berdasarkan kelas atau input
     */
    public function assignJurusanFromData(?string $classroom = null, ?string $rawJurusan = null): ?Jurusan
    {
        $cls = $classroom ?: $this->classroom;
        $jurusan = Jurusan::findFromClassroom($cls, $rawJurusan);

        if ($jurusan) {
            $this->jurusan_id = $jurusan->id;
            $this->save();
        }

        return $jurusan;
    }

    /**
     * Tahun Masuk (dihitung dari tahun akun dibuat / created_at)
     */
    public function getTahunMasukAttribute(): ?int
    {
        return $this->created_at ? (int) $this->created_at->format('Y') : null;
    }

    /**
     * Tahun Lulus (dihitung dari tahun pembaruan status kelulusan / updated_at hanya jika berstatus alumni)
     */
    public function getTahunLulusAttribute(): ?int
    {
        if (! $this->isAlumni()) {
            return null;
        }

        return $this->updated_at ? (int) $this->updated_at->format('Y') : null;
    }

    /**
     * Format tanggal masuk siswa
     */
    public function getTahunMasukTanggalAttribute(): ?string
    {
        return $this->created_at ? $this->created_at->translatedFormat('d M Y') : null;
    }

    /**
     * Format tanggal lulus alumni
     */
    public function getTahunLulusTanggalAttribute(): ?string
    {
        if (! $this->isAlumni()) {
            return null;
        }

        return $this->updated_at ? $this->updated_at->translatedFormat('d M Y') : null;
    }
}
