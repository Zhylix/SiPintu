<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'sync_type',
        'status',
        'records_processed',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function getSyncTypeLabelAttribute(): string
    {
        return match ($this->sync_type) {
            'sijuna_teachers' => 'Guru SIJUNA',
            'sijuna_students' => 'Siswa SIJUNA',
            'sijuna_all' => 'Semua Data SIJUNA',
            default => ucfirst(str_replace('_', ' ', $this->sync_type ?? 'SIJUNA')),
        };
    }
}
