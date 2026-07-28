<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PklStatus extends Model
{
    use HasFactory;

    protected $table = 'pkl_statuses';

    protected $fillable = [
        'student_id',
        'company_name',
        'division',
        'mentor_name',
        'dudi_supervisor',
        'status',
        'notes',
        'start_date',
        'end_date',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function allowedStatuses(): array
    {
        return [
            'Pengajuan' => 'Pengajuan Tempat PKL',
            'Menunggu Konfirmasi' => 'Menunggu Konfirmasi DUDI',
            'Diterima' => 'Diterima di DUDI',
            'Aktif Berjalan' => 'Aktif Berjalan PKL',
            'Dievaluasi' => 'Proses Evaluasi / Penilaian',
            'Selesai PKL' => 'Lulus / Selesai PKL',
            'Ditolak' => 'Pengajuan Ditolak',
        ];
    }

    public function getBadgeColorAttribute(): string
    {
        return match ($this->status) {
            'Aktif Berjalan' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
            'Diterima' => 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20',
            'Selesai PKL' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
            'Menunggu Konfirmasi' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
            'Pengajuan' => 'bg-purple-500/10 text-purple-400 border-purple-500/20',
            'Dievaluasi' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
            'Ditolak' => 'bg-rose-500/10 text-rose-400 border-rose-500/20',
            default => 'bg-slate-500/10 text-slate-400 border-slate-500/20',
        };
    }
}
