@extends('layouts.app', ['headerTitle' => 'Portal Mitra DUDI & Industri - Gateway Identity'])

@section('content')
<div class="space-y-8">
    <!-- DUDI Welcome Header Card -->
    <div class="p-6 rounded-2xl bg-gradient-to-r from-amber-900/60 via-slate-900 to-slate-900 border border-amber-500/30 relative overflow-hidden shadow-2xl">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative z-10">
            <div class="flex items-center space-x-5">
                <div class="w-16 h-16 rounded-2xl bg-amber-600/20 border border-amber-500/30 text-amber-400 flex items-center justify-center font-black text-2xl shadow-inner shrink-0">
                    {{ auth()->user()->initials() }}
                </div>
                <div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/10 text-amber-300 border border-amber-500/20 mb-1">
                        Mitra Dunia Usaha & Industri (DUDI)
                    </span>
                    <h2 class="text-2xl font-black text-white tracking-tight">Selamat Datang, {{ auth()->user()->name }}</h2>
                    <p class="text-xs text-slate-400 mt-1">
                        Perusahaan: <span class="font-semibold text-amber-300">{{ auth()->user()->name }}</span> | Email Kontak: <span class="font-mono text-slate-300">{{ auth()->user()->email }}</span>
                    </p>
                </div>
            </div>
            <div class="flex items-center space-x-3 shrink-0">
                <a href="{{ route('dudi.evaluations') }}" class="px-4 py-2.5 bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold rounded-xl transition-all shadow-lg shadow-amber-600/30">
                    Beri Penilaian Magang &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- DUDI Quick Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl space-y-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Peserta Magang Aktif</span>
            <div class="text-3xl font-black text-amber-400">{{ $stats['active_interns'] }} Siswa</div>
            <p class="text-xs text-slate-400">Periode Juli - Desember 2026</p>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl space-y-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tingkat Kehadiran Magang</span>
            <div class="text-3xl font-black text-emerald-400">{{ $stats['attendance_rate'] }}</div>
            <p class="text-xs text-slate-400">Presensi Industri Realtime</p>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl space-y-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Perlu Penilaian Evaluasi</span>
            <div class="text-3xl font-black text-rose-400">{{ $stats['pending_evaluations'] }} Siswa</div>
            <p class="text-xs text-slate-400">Diperbarui minggu ini</p>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl space-y-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Alumni PKL Industri</span>
            <div class="text-3xl font-black text-indigo-400">{{ $stats['completed_interns'] }} Siswa</div>
            <p class="text-xs text-slate-400">Tahun Ajaran Lalu</p>
        </div>
    </div>

    <!-- Interns List Section -->
    <div class="bg-slate-900 rounded-2xl border border-slate-800 p-6 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-white">Peserta Magang / PKL di Perusahaan Anda</h3>
                <p class="text-xs text-slate-400 mt-0.5">Daftar siswa SMK yang sedang melaksanakan kegiatan magang industri</p>
            </div>
            <a href="{{ route('dudi.interns') }}" class="text-xs font-bold text-amber-400 hover:underline">
                Lihat Semua Peserta &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950 text-slate-400 uppercase font-bold text-[10px] border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Nama Siswa</th>
                        <th class="px-4 py-3">NISN / External ID</th>
                        <th class="px-4 py-3">Divisi Magang</th>
                        <th class="px-4 py-3">Status Kehadiran</th>
                        <th class="px-4 py-3">Penilaian</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-300">
                    @foreach($interns as $intern)
                        <tr class="hover:bg-slate-800/40">
                            <td class="px-4 py-3 font-semibold text-white">{{ $intern->name }}</td>
                            <td class="px-4 py-3 font-mono text-amber-300">{{ $intern->external_id ?? '-' }}</td>
                            <td class="px-4 py-3 font-sans text-slate-300">Software & Engineering</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    Hadir Hari Ini
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('dudi.evaluations') }}" class="text-amber-400 hover:underline font-bold text-[11px]">Input Evaluasi</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
