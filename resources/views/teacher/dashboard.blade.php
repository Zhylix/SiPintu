@extends('layouts.app', ['headerTitle' => 'Portal Guru / Pendidik - Gateway SMKN 1 BANGSRI'])

@section('content')
<div class="space-y-8">
    <!-- Teacher Welcome Card -->
    <div class="p-6 rounded-3xl bg-gradient-to-r from-emerald-50 via-white to-white border border-emerald-200 relative overflow-hidden shadow-sm">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative z-10">
            <div class="flex items-center space-x-5">
                <div class="w-16 h-16 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-black text-2xl shadow-md shrink-0">
                    {{ auth()->user()->initials() }}
                </div>
                <div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300 mb-1.5">
                        ● Guru Pembimbing / Tenaga Pendidik SMKN 1 Bangsri
                    </span>
                    <h2 class="text-2xl font-black text-emerald-950 tracking-tight">Selamat Datang, {{ auth()->user()->name }}</h2>
                    <p class="text-xs text-slate-600 font-medium mt-1">
                        NIP / Username: <span class="font-mono text-emerald-800 font-extrabold">{{ auth()->user()->username ?? 'guru' }}</span> | Email: <span class="font-mono text-slate-700 font-semibold">{{ auth()->user()->email }}</span>
                    </p>
                </div>
            </div>
            <div class="flex items-center space-x-3 shrink-0">
                <a href="{{ route('teacher.students') }}" class="px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-600/20">
                    Kelola Siswa Bimbingan &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Teacher Stats Overview Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-2">
            <span class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Siswa Bimbingan</span>
            <div class="text-3xl font-black text-emerald-700">{{ $stats['guided_students'] }}</div>
            <p class="text-xs text-slate-600 font-medium">Siswa aktif bimbingan PKL</p>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-2">
            <span class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Total Siswa Terdaftar</span>
            <div class="text-3xl font-black text-emerald-950">{{ number_format($stats['total_students']) }}</div>
            <p class="text-xs text-slate-600 font-medium">Database SIJUNA Central</p>
        </div>
    </div>

    <!-- Guided Students Table -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-black text-emerald-950">Daftar Siswa Bimbingan Terkini</h3>
                <p class="text-xs text-slate-600 font-medium mt-0.5">Monitoring progress siswa bimbingan</p>
            </div>
            <a href="{{ route('teacher.students') }}" class="text-xs font-extrabold text-emerald-700 hover:underline">
                Lihat Semua Siswa &rarr;
            </a>
        </div>

        <div class="overflow-x-auto border border-slate-200 rounded-xl">
            <table class="w-full text-left text-xs">
                <thead class="bg-emerald-50 text-emerald-900 uppercase font-black text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Nama Siswa</th>
                        <th class="px-4 py-3">NISN / Identifier</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700 font-sans bg-white">
                    @foreach($guidedStudents as $s)
                        <tr class="hover:bg-emerald-50/50">
                            <td class="px-4 py-3 font-bold text-slate-900">{{ $s->name }}</td>
                            <td class="px-4 py-3 font-mono font-bold text-emerald-800">{{ $s->external_id ?? $s->username ?? '-' }}</td>
                            <td class="px-4 py-3 font-mono text-slate-600 font-semibold">{{ $s->email }}</td>
                            <td class="px-4 py-3">
                                <a href="{{ route('teacher.evaluations') }}" class="text-emerald-700 hover:underline font-extrabold text-[11px]">Beri Penilaian</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Teacher SSO Applications Section -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-black text-emerald-950">Aplikasi Terintegrasi (Portal Guru)</h3>
                <p class="text-xs text-slate-600 font-medium mt-0.5">Filter aplikasi berdasarkan kategori atau tandai ⭐ favorit Anda</p>
            </div>
        </div>

        @include('partials.app-catalog-grid')
    </div>
</div>
@endsection
