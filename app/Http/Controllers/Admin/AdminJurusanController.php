<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jurusan;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminJurusanController extends Controller
{
    /**
     * Tampilkan halaman ringkasan dan tabel pengelompokan alumni berdasarkan 5 Jurusan Resmi
     */
    public function index(Request $request)
    {
        // 1. Data 5 Jurusan dengan hitungan alumni & siswa
        $jurusans = Jurusan::withCount(['alumni', 'students'])
            ->orderByRaw("FIELD(kode_jurusan, 'PPLG', 'TO', 'AKL', 'PM', 'MPLB')")
            ->get();

        $totalAlumni = User::where('role', 'alumni')->count();
        $totalAlumniWithJurusan = User::where('role', 'alumni')->whereNotNull('jurusan_id')->count();

        // 2. Sebaran kelas/rombel per jurusan untuk alumni
        $classroomDistribution = DB::table('users')
            ->where('role', 'alumni')
            ->whereNotNull('classroom')
            ->whereNotNull('jurusan_id')
            ->select('jurusan_id', 'classroom', DB::raw('count(*) as count'))
            ->groupBy('jurusan_id', 'classroom')
            ->orderBy('classroom')
            ->get()
            ->groupBy('jurusan_id');

        // 3. Query daftar rinci alumni sesuai filter jurusan & pencarian
        $selectedJurusanKode = $request->query('jurusan', 'all');
        $search = trim((string) $request->query('search', ''));

        $alumniQuery = User::where('role', 'alumni')
            ->with('jurusan');

        if ($selectedJurusanKode !== 'all' && ! empty($selectedJurusanKode)) {
            $jurusan = Jurusan::where('kode_jurusan', $selectedJurusanKode)->first();
            if ($jurusan) {
                $alumniQuery->where('jurusan_id', $jurusan->id);
            }
        }

        if (! empty($search)) {
            $alumniQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('external_id', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('classroom', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $alumniList = $alumniQuery
            ->orderByRaw("COALESCE(classroom, '') ASC, name ASC")
            ->paginate(15)
            ->withQueryString();

        return view('admin.jurusan.index', compact(
            'jurusans',
            'totalAlumni',
            'totalAlumniWithJurusan',
            'classroomDistribution',
            'alumniList',
            'selectedJurusanKode',
            'search'
        ));
    }

    /**
     * Jalankan ulang regex pengelompokan jurusan untuk seluruh akun alumni di sistem
     */
    public function resync(Request $request): RedirectResponse
    {
        $jurusanMap = Jurusan::pluck('id', 'kode_jurusan')->toArray();
        $alumniUsers = User::where('role', 'alumni')->get();

        $updatedCount = 0;
        $statsByJurusan = [
            'PPLG' => 0,
            'TO' => 0,
            'AKL' => 0,
            'PM' => 0,
            'MPLB' => 0,
        ];

        foreach ($alumniUsers as $user) {
            $kode = Jurusan::extractKodeJurusan($user->classroom);

            // Fallback nama jika kelas kosong
            if (! $kode && $user->name) {
                if (preg_match('/\((?:Alumni\s+)?([A-Za-z\s]+?)\)/i', $user->name, $m)) {
                    $kode = Jurusan::extractKodeJurusan($m[1]);
                }
            }

            if ($kode && isset($jurusanMap[$kode])) {
                $user->jurusan_id = $jurusanMap[$kode];
                $user->save();
                $updatedCount++;
                if (isset($statsByJurusan[$kode])) {
                    $statsByJurusan[$kode]++;
                }
            }
        }

        AuditLogger::log('admin_resync_alumni_jurusan', [
            'total_alumni' => $alumniUsers->count(),
            'updated_count' => $updatedCount,
            'stats_by_jurusan' => $statsByJurusan,
        ]);

        $detailMsg = "PPLG: {$statsByJurusan['PPLG']}, TO: {$statsByJurusan['TO']}, AKL: {$statsByJurusan['AKL']}, PM: {$statsByJurusan['PM']}, MPLB: {$statsByJurusan['MPLB']}";

        return redirect()->route('admin.jurusan.index')->with(
            'success',
            "Berhasil mengelompokkan ulang {$updatedCount} alumni ke dalam 5 jurusan resmi ({$detailMsg})."
        );
    }
}
