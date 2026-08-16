@extends('layouts.app', ['headerTitle' => auth()->user()->isAlumni() ? 'Portal Alumni' : 'Portal Siswa'])

@section('content')
<div class="space-y-8">
    <!-- Welcome Header Card -->
    <div class="p-5 sm:p-8 rounded-3xl bg-gradient-to-r from-emerald-50 via-white to-teal-50/40 border border-emerald-200/80 relative overflow-hidden shadow-xs">
        <!-- Background Accent -->
        <div class="absolute -right-10 -bottom-10 w-60 h-60 bg-emerald-100/50 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6 relative z-10 w-full">
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-5 w-full min-w-0">
                <div class="w-14 h-14 sm:w-16 sm:h-16 rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-700 border border-emerald-500 text-white flex items-center justify-center font-black text-xl sm:text-2xl shadow-md shrink-0">
                    {{ auth()->user()->initials() }}
                </div>
                <div class="space-y-1.5 w-full min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center px-3 py-0.5 rounded-full text-[11px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                            ● {{ auth()->user()->isAlumni() ? 'Alumni' : 'Siswa Aktif' }}
                        </span>
                        @if(auth()->user()->classroom)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-white text-emerald-900 border border-emerald-200">
                                Kelas {{ auth()->user()->classroom }}
                            </span>
                        @endif
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium text-slate-600 bg-slate-100 border border-slate-200">
                            SMKN 1 Bangsri
                        </span>
                    </div>

                    <h2 class="text-lg sm:text-2xl font-black text-emerald-950 tracking-tight leading-snug break-words max-w-full">
                        Selamat Datang, {{ auth()->user()->name }}
                    </h2>

                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-600 font-medium w-full">
                        <div class="flex items-center space-x-1 shrink-0">
                            <span class="text-slate-400">ID:</span>
                            <span class="font-mono text-emerald-800 font-bold">{{ auth()->user()->external_id ?? auth()->user()->username ?? '-' }}</span>
                        </div>
                        <span class="text-slate-300 shrink-0">•</span>
                        <div class="flex items-center space-x-1 min-w-0">
                            <span class="text-slate-400 shrink-0">Email:</span>
                            <span class="font-mono text-slate-700 font-medium truncate max-w-[180px] sm:max-w-xs">{{ auth()->user()->email }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center space-x-3 shrink-0 self-stretch sm:self-auto w-full sm:w-auto">
                <a href="{{ route('student.apps') }}" class="w-full sm:w-auto px-5 py-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-600/20 text-center">
                    Aplikasi Terintegrasi &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- Student SSO Applications Section -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-black text-emerald-950">Katalog Aplikasi</h3>
                <p class="text-xs text-slate-600 font-medium mt-0.5">Filter berdasarkan kategori atau tandai ⭐ favorit untuk akses cepat ke portal aplikasi Anda</p>
            </div>
        </div>

        @include('partials.app-catalog-grid')
    </div>
</div>
@endsection
