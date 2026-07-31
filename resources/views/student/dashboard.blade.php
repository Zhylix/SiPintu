@extends('layouts.app', ['headerTitle' => 'Portal Siswa - Gateway SMKN 1 BANGSRI'])

@section('content')
<div class="space-y-8">
    <!-- Welcome Header Card -->
    <div class="p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-emerald-50 via-white to-white border border-emerald-200 relative overflow-hidden shadow-sm">
        <!-- Background Accent -->
        <div class="absolute -right-10 -bottom-10 w-60 h-60 bg-emerald-100/60 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative z-10">
            <div class="flex items-center space-x-5">
                <div class="w-16 h-16 rounded-2xl bg-emerald-600 border border-emerald-500 text-white flex items-center justify-center font-black text-2xl shadow-md shrink-0">
                    {{ auth()->user()->initials() }}
                </div>
                <div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300 mb-1.5">
                        ● Siswa Aktif SMKN 1 Bangsri
                    </span>
                    <h2 class="text-2xl font-black text-emerald-950 tracking-tight">Selamat Datang, {{ auth()->user()->name }}</h2>
                    <p class="text-xs text-slate-600 font-medium mt-1">
                        Identifier / NISN: <span class="font-mono text-emerald-800 font-extrabold">{{ auth()->user()->external_id ?? auth()->user()->username ?? '-' }}</span> | Email: <span class="font-mono text-slate-700 font-semibold">{{ auth()->user()->email }}</span>
                    </p>
                </div>
            </div>
            <div class="flex items-center space-x-3 shrink-0">
                <a href="{{ route('student.apps') }}" class="px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-600/20">
                    Aplikasi Terintegrasi &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Student SSO Applications Section -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-black text-emerald-950">Katalog Aplikasi Sekolah</h3>
                <p class="text-xs text-slate-600 font-medium mt-0.5">Filter berdasarkan kategori atau tandai ⭐ favorit untuk akses cepat ke portal aplikasi Anda</p>
            </div>
        </div>

        @include('partials.app-catalog-grid')
    </div>
</div>
@endsection
