@extends('layouts.app', ['headerTitle' => 'Portal DUDI'])

@section('content')
<div class="space-y-8">
    <!-- DUDI Welcome Header Card -->
    <div class="p-6 rounded-3xl bg-gradient-to-r from-emerald-50 via-white to-white border border-emerald-200 relative overflow-hidden shadow-sm">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative z-10">
            <div class="flex items-center space-x-5">
                <div class="w-16 h-16 rounded-2xl bg-emerald-600 text-white flex items-center justify-center font-black text-2xl shadow-md shrink-0">
                    {{ auth()->user()->initials() }}
                </div>
                <div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300 mb-1.5">
                        ● Mitra Dunia Usaha & Industri (DUDI) SMKN 1 Bangsri
                    </span>
                    <h2 class="text-2xl font-black text-emerald-950 tracking-tight">Selamat Datang, {{ auth()->user()->name }}</h2>
                    <p class="text-xs text-slate-600 font-medium mt-1">
                        Perusahaan / Industri: <span class="font-bold text-emerald-800">{{ auth()->user()->name }}</span> | Email Kontak: <span class="font-mono text-slate-700 font-semibold">{{ auth()->user()->email }}</span>
                    </p>
                </div>
            </div>
            <div class="flex items-center space-x-3 shrink-0">
                <a href="{{ route('dudi.apps') }}" class="px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-600/20">
                    Aplikasi Terintegrasi &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- DUDI SSO Applications Section -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-black text-emerald-950">Aplikasi Terintegrasi DUDI</h3>
                <p class="text-xs text-slate-600 font-medium mt-0.5">Filter aplikasi berdasarkan kategori atau tandai ⭐ favorit Anda</p>
            </div>
        </div>

        @include('partials.app-catalog-grid')
    </div>
</div>
@endsection
