@extends('layouts.app', ['headerTitle' => 'Portal Siswa - Gateway Identity & SSO'])

@section('content')
<div class="space-y-8">
    <!-- Welcome Header Card -->
    <div class="p-6 rounded-2xl bg-gradient-to-r from-indigo-900/60 via-slate-900 to-slate-900 border border-indigo-500/30 relative overflow-hidden shadow-2xl">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative z-10">
            <div class="flex items-center space-x-5">
                <div class="w-16 h-16 rounded-2xl bg-indigo-600/20 border border-indigo-500/30 text-indigo-400 flex items-center justify-center font-black text-2xl shadow-inner shrink-0">
                    {{ auth()->user()->initials() }}
                </div>
                <div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 mb-1">
                        Siswa Aktif (SIJUNA)
                    </span>
                    <h2 class="text-2xl font-black text-white tracking-tight">Selamat Datang, {{ auth()->user()->name }}</h2>
                    <p class="text-xs text-slate-400 mt-1">
                        Identifier / NISN: <span class="font-mono text-indigo-300 font-semibold">{{ auth()->user()->external_id ?? auth()->user()->username ?? '-' }}</span> | Email: <span class="font-mono text-slate-300">{{ auth()->user()->email }}</span>
                    </p>
                </div>
            </div>
            <div class="flex items-center space-x-3 shrink-0">
                <a href="{{ route('student.pkl') }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-all shadow-lg shadow-indigo-600/30">
                    Lihat Jurnal PKL &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Student Quick Overview Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl space-y-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Status PKL</span>
            <div class="text-lg font-bold text-emerald-400 flex items-center">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 mr-2 animate-ping"></span>
                {{ $pklInfo['status'] }}
            </div>
            <p class="text-xs text-slate-400 truncate">{{ $pklInfo['company_name'] }}</p>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl space-y-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Guru Pembimbing</span>
            <div class="text-base font-bold text-white truncate">{{ $pklInfo['mentor_name'] }}</div>
            <p class="text-xs text-slate-400">Pembimbing Lapangan: {{ $pklInfo['dudi_supervisor'] }}</p>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl space-y-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Presensi Magang</span>
            <div class="text-2xl font-black text-indigo-400">{{ $pklInfo['attendance_count'] }} Hari</div>
            <p class="text-xs text-slate-400">Logbook terisi: {{ $pklInfo['logbook_count'] }} Catatan</p>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl space-y-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Nilai Evaluasi Sementara</span>
            <div class="text-2xl font-black text-amber-400">{{ $pklInfo['evaluation_score'] }} / 100</div>
            <p class="text-xs text-slate-400">Predikat: Sangat Baik (A)</p>
        </div>
    </div>

    <!-- Student SSO Applications Section -->
    <div class="bg-slate-900 rounded-2xl border border-slate-800 p-6 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-white">Aplikasi Terintegrasi Single Sign-On (SSO) Siswa</h3>
                <p class="text-xs text-slate-400 mt-0.5">Klik aplikasi untuk langsung login otomatis menggunakan sesi Gateway Anda</p>
            </div>
            <a href="{{ route('student.apps') }}" class="text-xs font-bold text-indigo-400 hover:underline">
                Semua Aplikasi &rarr;
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
            @forelse($applications as $app)
                <div class="p-5 rounded-xl bg-slate-950/80 border border-slate-800 hover:border-indigo-500/40 transition-all space-y-3 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-bold text-white">{{ $app->name }}</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                SSO Ready
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 line-clamp-2">{{ $app->description }}</p>
                    </div>

                    <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between">
                        <span class="text-[11px] text-slate-400">Diizinkan untuk Siswa</span>
                        <a href="{{ route('oauth.authorize', ['client_id' => $app->client_id, 'redirect_uri' => $app->redirect_uri, 'response_type' => 'code', 'scope' => 'openid profile email']) }}" class="px-3 py-1.5 bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white border border-indigo-500/30 text-xs font-bold rounded-lg transition-all">
                            Buka SSO &rarr;
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-3 p-6 text-center text-xs text-slate-400 bg-slate-950/40 rounded-xl border border-slate-800">
                    Belum ada aplikasi yang dihubungkan untuk role Siswa.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
