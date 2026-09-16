<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jurusan extends Model
{
    use HasFactory;

    protected $table = 'jurusans';

    protected $fillable = [
        'kode_jurusan',
        'nama_jurusan',
        'deskripsi',
    ];

    /**
     * Relasi ke seluruh pengguna di jurusan ini
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'jurusan_id');
    }

    /**
     * Relasi ke alumni di jurusan ini
     */
    public function alumni(): HasMany
    {
        return $this->hasMany(User::class, 'jurusan_id')->where('role', 'alumni');
    }

    /**
     * Relasi ke siswa aktif di jurusan ini
     */
    public function students(): HasMany
    {
        return $this->hasMany(User::class, 'jurusan_id')->where('role', 'student');
    }

    /**
     * Ekstraksi kode jurusan dengan regex dan normalisasi ke 5 Jurusan Resmi:
     * - PPL (Pengembangan Perangkat Lunak)
     * - TO (Teknik Otomotif)
     * - AKL (Akuntansi dan Keuangan Lembaga)
     * - PM (Pemasaran)
     * - MPLB (Manajemen Perkantoran dan Layanan Bisnis)
     */
    public static function extractKodeJurusan(?string $classroom, ?string $rawJurusan = null): ?string
    {
        $target = $rawJurusan ?: $classroom;
        if (empty($target)) {
            return null;
        }

        // 1. Bersihkan keterangan dalam tanda kurung (Lulus), (Alumni), dsb.
        $cleaned = preg_replace('/\s*\(.*?\)\s*/', ' ', $target);

        // 2. Bersihkan prefix teks jika ada
        $cleaned = preg_replace('/^(alumni|siswa)\s+/i', '', trim($cleaned));

        // 3. Regex mengekstrak nama jurusan tanpa tingkatan kelas (X, XI, XII, 10, 11, 12) dan nomor rombel (1, 2, 3...)
        if (preg_match('/^(?:(?:X|XI|XII|\d+)\s+)?([A-Za-z\s]+?)(?:\s+\d+)?$/i', trim($cleaned), $matches)) {
            $extracted = strtoupper(trim($matches[1]));

            // Pemetaan normalisasi ke 5 Jurusan Resmi
            if (in_array($extracted, ['PPL', 'PPLG', 'RPL', 'SIJA', 'REKAYASA PERANGKAT LUNAK', 'PENGEMBANGAN PERANGKAT LUNAK'])) {
                return 'PPL';
            }
            if (in_array($extracted, ['TO', 'TBSM', 'TKRO', 'OTOMOTIF', 'TEKNIK OTOMOTIF'])) {
                return 'TO';
            }
            if (in_array($extracted, ['AKL', 'AK', 'AKUNTANSI', 'AKUNTANSI DAN KEUANGAN LEMBAGA'])) {
                return 'AKL';
            }
            if (in_array($extracted, ['PM', 'BDP', 'PEMASARAN', 'BISNIS DARING DAN PEMASARAN'])) {
                return 'PM';
            }
            if (in_array($extracted, ['MPLB', 'OTKP', 'AP', 'MANAJEMEN PERKANTORAN', 'MANAJEMEN PERKANTORAN DAN LAYANAN BISNIS'])) {
                return 'MPLB';
            }

            return $extracted;
        }

        return null;
    }

    /**
     * Dapatkan instance Jurusan berdasarkan teks kelas atau jurusan mentah
     */
    public static function findFromClassroom(?string $classroom, ?string $rawJurusan = null): ?self
    {
        $kode = static::extractKodeJurusan($classroom, $rawJurusan);
        if (! $kode) {
            return null;
        }

        return static::where('kode_jurusan', $kode)->first();
    }
}
