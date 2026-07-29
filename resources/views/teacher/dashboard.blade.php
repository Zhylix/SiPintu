@extends('layouts.app', ['headerTitle' => 'Portal Guru / Pendidik - Gateway Identity'])

@section('content')
<div class="space-y-8">
    <!-- Teacher Welcome Card -->
    <div class="p-6 rounded-2xl bg-gradient-to-r from-purple-900/60 via-slate-900 to-slate-900 border border-purple-500/30 relative overflow-hidden shadow-2xl">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative z-10">
            <div class="flex items-center space-x-5">
                <div class="w-16 h-16 rounded-2xl bg-purple-600/20 border border-purple-500/30 text-purple-400 flex items-center justify-center font-black text-2xl shadow-inner shrink-0">
                    {{ auth()->user()->initials() }}
                </div>
                <div>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-500/10 text-purple-300 border border-purple-500/20 mb-1">
                        Guru Pembimbing / Tenaga Pendidik
                    </span>
                    <h2 class="text-2xl font-black text-white tracking-tight">Selamat Datang, {{ auth()->user()->name }}</h2>
                    <p class="text-xs text-slate-400 mt-1">
                        NIP / Username: <span class="font-mono text-purple-300 font-semibold">{{ auth()->user()->username ?? 'guru' }}</span> | Email: <span class="font-mono text-slate-300">{{ auth()->user()->email }}</span>
                    </p>
                </div>
            </div>
            <div class="flex items-center space-x-3 shrink-0">
                <a href="{{ route('teacher.students') }}" class="px-4 py-2.5 bg-purple-600 hover:bg-purple-500 text-white text-xs font-bold rounded-xl transition-all shadow-lg shadow-purple-600/30">
                    Kelola Siswa Bimbingan &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Teacher Stats Overview Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl space-y-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Siswa Bimbingan</span>
            <div class="text-3xl font-black text-purple-400">{{ $stats['guided_students'] }}</div>
            <p class="text-xs text-slate-400">Siswa aktif bimbingan</p>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl space-y-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Siswa Terdaftar</span>
            <div class="text-3xl font-black text-white">{{ number_format($stats['total_students']) }}</div>
            <p class="text-xs text-slate-400">Database SIJUNA Central</p>
        </div>
    </div>

    <!-- Guided Students Table -->
    <div class="bg-slate-900 rounded-2xl border border-slate-800 p-6 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-white">Daftar Siswa Bimbingan Terkini</h3>
                <p class="text-xs text-slate-400 mt-0.5">Monitoring progress siswa bimbingan</p>
            </div>
            <a href="{{ route('teacher.students') }}" class="text-xs font-bold text-purple-400 hover:underline">
                Lihat Semua Siswa &rarr;
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950 text-slate-400 uppercase font-bold text-[10px] border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Nama Siswa</th>
                        <th class="px-4 py-3">NISN / Identifier</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-300 font-sans">
                    @foreach($guidedStudents as $s)
                        <tr class="hover:bg-slate-800/40">
                            <td class="px-4 py-3 font-semibold text-white">{{ $s->name }}</td>
                            <td class="px-4 py-3 font-mono text-purple-300">{{ $s->external_id ?? $s->username ?? '-' }}</td>
                            <td class="px-4 py-3 font-mono text-slate-400">{{ $s->email }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('teacher.evaluations') }}" class="text-purple-400 hover:underline font-bold text-[11px]">Beri Penilaian</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
