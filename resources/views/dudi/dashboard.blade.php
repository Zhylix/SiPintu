@extends('layouts.app', ['headerTitle' => 'Portal DUDI'])

@section('content')
<div class="space-y-8">
    <!-- DUDI Welcome Header Card -->
    <div class="p-5 sm:p-8 rounded-3xl bg-gradient-to-r from-emerald-50 via-white to-teal-50/40 border border-emerald-200/80 relative overflow-hidden shadow-xs">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative z-10 w-full">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-5 w-full min-w-0">
                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-700 border border-emerald-500 text-white flex items-center justify-center font-black text-xl sm:text-2xl shadow-md shrink-0">
                    {{ auth()->user()->initials() }}
                </div>
                <div class="space-y-1.5 w-full min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center px-3 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                            ● Mitra DUDI
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium text-slate-600 bg-slate-100 border border-slate-200">
                            SMKN 1 Bangsri
                        </span>
                    </div>

                    <h2 class="text-lg sm:text-2xl font-black text-emerald-950 tracking-tight leading-snug break-words max-w-full">
                        Selamat Datang, {{ auth()->user()->name }}
                    </h2>

                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-600 font-medium w-full">
                        <div class="flex items-center space-x-1 shrink-0">
                            <span class="text-slate-400">Email Kontak:</span>
                            <span class="font-mono text-slate-700 font-medium truncate max-w-[180px] sm:max-w-xs">{{ auth()->user()->email }}</span>
                        </div>
                        @if(auth()->user()->username)
                            <span class="text-slate-300 shrink-0">•</span>
                            <div class="flex items-center space-x-1 shrink-0">
                                <span class="text-slate-400">ID:</span>
                                <span class="font-mono text-emerald-800 font-bold">{{ auth()->user()->username }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex items-center space-x-3 shrink-0 self-stretch sm:self-auto w-full sm:w-auto">
                <a href="{{ route('dudi.apps') }}" class="w-full sm:w-auto px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-600/20 text-center">
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
