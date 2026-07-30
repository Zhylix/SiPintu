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
                <a href="{{ route('dudi.apps') }}" class="px-4 py-2.5 bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold rounded-xl transition-all shadow-lg shadow-amber-600/30">
                    Aplikasi Terintegrasi &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- DUDI SSO Applications Section -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-white">Aplikasi Terintegrasi DUDI</h3>
                <p class="text-xs text-slate-400 mt-0.5">Filter aplikasi berdasarkan kategori atau tandai ⭐ favorit Anda</p>
            </div>
        </div>

        @include('partials.app-catalog-grid')
    </div>
</div>
@endsection
