<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'type',
        'target_role',
        'channel',
        'is_active',
        'created_by',
        'published_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Enforce maximum active announcements = 1.
     * When any announcement is activated (is_active = true), all other announcements are automatically deactivated.
     */
    protected static function booted()
    {
        static::saving(function ($announcement) {
            if ($announcement->is_active) {
                static::where('id', '!=', $announcement->id ?? 0)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }
        });
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function whatsAppLogs(): HasMany
    {
        return $this->hasMany(WhatsAppLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForWeb($query)
    {
        return $query->whereIn('channel', ['web', 'both']);
    }

    public function scopeForWhatsApp($query)
    {
        return $query->whereIn('channel', ['whatsapp', 'both']);
    }

    public function getChannelLabelAttribute(): string
    {
        return match ($this->channel) {
            'web' => 'Web Saja',
            'whatsapp' => 'WhatsApp Saja',
            'both' => 'Web & WhatsApp',
            default => 'Web & WhatsApp',
        };
    }

    public function scopeForRole($query, ?string $role)
    {
        if (empty($role)) {
            return $query->where('target_role', 'all');
        }

        if ($role === 'admin') {
            return $query->whereIn('target_role', ['all', 'admin']);
        }

        // Non-admin users (student, teacher, dudi) see 'all', 'user', and their specific role
        return $query->whereIn('target_role', ['all', 'user', $role]);
    }
}
