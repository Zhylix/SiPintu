<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErrorLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'incident_code',
        'status_code',
        'error_type',
        'message',
        'file',
        'line',
        'trace',
        'url',
        'route_name',
        'method',
        'ip_address',
        'user_agent',
        'user_id',
        'user_role',
        'request_data',
        'headers',
        'status',
        'occurrence_count',
        'fingerprint',
        'last_seen_at',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
    ];

    protected $casts = [
        'status_code' => 'integer',
        'line' => 'integer',
        'occurrence_count' => 'integer',
        'request_data' => 'array',
        'headers' => 'array',
        'last_seen_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->where('status', 'unresolved');
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', 'resolved');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('incident_code', 'like', "%{$term}%")
                ->orWhere('message', 'like', "%{$term}%")
                ->orWhere('error_type', 'like', "%{$term}%")
                ->orWhere('url', 'like', "%{$term}%")
                ->orWhere('ip_address', 'like', "%{$term}%")
                ->orWhereHas('user', function (Builder $uq) use ($term) {
                    $uq->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
        });
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    public function markAsResolved(?int $resolverId = null, ?string $notes = null): bool
    {
        return $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $resolverId,
            'resolution_notes' => $notes ?? $this->resolution_notes,
        ]);
    }

    public function markAsUnresolved(): bool
    {
        return $this->update([
            'status' => 'unresolved',
            'resolved_at' => null,
            'resolved_by' => null,
        ]);
    }

    public function markAsIgnored(?int $resolverId = null): bool
    {
        return $this->update([
            'status' => 'ignored',
            'resolved_at' => now(),
            'resolved_by' => $resolverId,
        ]);
    }

    public function getSeverityClass(): string
    {
        if ($this->status_code >= 500) {
            return 'bg-rose-100 text-rose-800 border-rose-200';
        }

        if ($this->status_code >= 400) {
            return 'bg-amber-100 text-amber-800 border-amber-200';
        }

        return 'bg-blue-100 text-blue-800 border-blue-200';
    }

    public function getStatusBadge(): array
    {
        return match ($this->status) {
            'resolved' => [
                'label' => 'Selesai',
                'class' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            ],
            'ignored' => [
                'label' => 'Diabaikan',
                'class' => 'bg-slate-100 text-slate-700 border-slate-200',
            ],
            default => [
                'label' => 'Belum Selesai',
                'class' => 'bg-rose-100 text-rose-800 border-rose-200 animate-pulse',
            ],
        };
    }
}
