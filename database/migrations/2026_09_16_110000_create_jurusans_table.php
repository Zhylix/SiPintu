<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Buat tabel jurusan
        Schema::create('jurusans', function (Blueprint $table) {
            $table->id();
            $table->string('kode_jurusan', 20)->unique()->index();
            $table->string('nama_jurusan', 255);
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        // Jurusan
        $now = now();
        $initialJurusans = [
            [
                'kode_jurusan' => 'PPL',
                'nama_jurusan' => 'Pengembangan Perangkat Lunak',
                'deskripsi' => 'Konsentrasi keahlian Pengembangan Perangkat Lunak dan Gim (PPLG / RPL) berfokus pada rekayasa perangkat lunak, pemrograman web, mobile, dan gim.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode_jurusan' => 'TO',
                'nama_jurusan' => 'Teknik Otomotif',
                'deskripsi' => 'Konsentrasi keahlian Teknik Otomotif berfokus pada pemeliharaan, perbaikan, dan teknologi mesin kendaraan bermotor serta sistem otomotif modern.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode_jurusan' => 'AKL',
                'nama_jurusan' => 'Akuntansi dan Keuangan Lembaga',
                'deskripsi' => 'Konsentrasi keahlian Akuntansi dan Keuangan Lembaga mempelajari pembukuan, pelaporan keuangan, perpajakan, dan operasional perbankan/lembaga keuangan.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode_jurusan' => 'PM',
                'nama_jurusan' => 'Pemasaran',
                'deskripsi' => 'Konsentrasi keahlian Pemasaran berfokus pada strategi penjualan, bisnis ritel modern, pemasaran digital (digital marketing), dan e-commerce.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'kode_jurusan' => 'MPLB',
                'nama_jurusan' => 'Manajemen Perkantoran dan Layanan Bisnis',
                'deskripsi' => 'Konsentrasi keahlian Manajemen Perkantoran dan Layanan Bisnis berfokus pada administrasi perkantoran modern, kearsipan digital, dan komunikasi bisnis.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        DB::table('jurusans')->insert($initialJurusans);

        // 3. kolom jurusan_id ke user
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('jurusan_id')
                ->nullable()
                ->after('classroom')
                ->constrained('jurusans')
                ->nullOnDelete();
        });

        // Backfill data alumni menggunakan regex normalisasi ke 5 jurusan
        $jurusanMap = DB::table('jurusans')->pluck('id', 'kode_jurusan')->toArray();

        $users = DB::table('users')
            ->whereNotNull('classroom')
            ->where('classroom', '!=', '')
            ->select('id', 'classroom', 'role')
            ->get();

        foreach ($users as $u) {
            $classroom = $u->classroom;
            $cleaned = preg_replace('/\s*\(.*?\)\s*/', ' ', $classroom);
            $cleaned = preg_replace('/^(alumni|siswa)\s+/i', '', trim($cleaned));

            $matchedCode = null;
            if (preg_match('/^(?:(?:X|XI|XII|\d+)\s+)?([A-Za-z\s]+?)(?:\s+\d+)?$/i', trim($cleaned), $matches)) {
                $code = strtoupper(trim($matches[1]));
                if (in_array($code, ['PPL', 'PPLG', 'RPL', 'SIJA'])) {
                    $matchedCode = 'PPL';
                } elseif (in_array($code, ['TO', 'TBSM', 'TKRO'])) {
                    $matchedCode = 'TO';
                } elseif (in_array($code, ['AKL', 'AK'])) {
                    $matchedCode = 'AKL';
                } elseif (in_array($code, ['PM', 'BDP'])) {
                    $matchedCode = 'PM';
                } elseif (in_array($code, ['MPLB', 'OTKP', 'AP'])) {
                    $matchedCode = 'MPLB';
                }
            }

            if ($matchedCode && isset($jurusanMap[$matchedCode])) {
                DB::table('users')->where('id', $u->id)->update([
                    'jurusan_id' => $jurusanMap[$matchedCode],
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'jurusan_id')) {
                $table->dropForeign(['jurusan_id']);
                $table->dropColumn('jurusan_id');
            }
        });

        Schema::dropIfExists('jurusans');
    }
};
