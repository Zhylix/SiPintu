<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedIp extends Model
{
    use HasFactory;

    protected $table = 'blocked_ips';

    protected $fillable = [
        'ip_address',
        'reason',
        'blocked_by',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Safely mutate expires_at attribute to prevent TypeError on __PHP_Incomplete_Class or invalid formats.
     */
    public function setExpiresAtAttribute($value): void
    {
        if ($value instanceof \__PHP_Incomplete_Class || (is_object($value) && ! ($value instanceof \DateTimeInterface))) {
            $data = (array) $value;
            if (! empty($data['date']) && is_string($data['date'])) {
                $tz = ! empty($data['timezone']) && is_string($data['timezone']) ? $data['timezone'] : null;
                try {
                    $value = \Illuminate\Support\Carbon::parse($data['date'], $tz);
                } catch (\Throwable) {
                    $value = null;
                }
            } else {
                $value = null;
            }
        }

        $this->attributes['expires_at'] = $value ? $this->fromDateTime($value) : null;
    }

    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }
}
