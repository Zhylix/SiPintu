@extends('layouts.app')

@section('title', 'Laporan Analitik & Statistik Gateway')

@section('content')
<div class="space-y-8">
    <!-- Top Header Banner - Bright Emerald & Teal Gradient -->
    <div class="relative bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-700 text-white rounded-3xl p-6 sm:p-8 overflow-hidden shadow-xl shadow-emerald-900/10 border border-emerald-500/30">
        <!-- Ambient Decorative Circles -->
        <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="absolute right-1/3 -top-10 w-60 h-60 bg-emerald-400/20 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="inline-flex items-center space-x-2 bg-white/15 backdrop-blur-md border border-white/25 px-3 py-1 rounded-full text-white text-xs font-extrabold mb-3 shadow-xs">
                    <span class="w-2 h-2 rounded-full bg-amber-300 animate-pulse"></span>
                    <span>Laporan Real-Time Gateway</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white drop-shadow-xs">Analitik Penggunaan SSO & Sistem</h1>
                <p class="text-emerald-50 text-xs sm:text-sm mt-1 max-w-2xl font-medium leading-relaxed opacity-95">
                    Pantau statistik akses Single Sign-On, distribusi peran pengguna, kesehatan aplikasi eksternal, dan siklus sinkronisasi SIJUNA.
                </p>
            </div>

            <!-- Time Range Filter Pills -->
            <div class="flex items-center bg-white/20 backdrop-blur-md p-1.5 rounded-2xl border border-white/30 shrink-0 self-start md:self-auto shadow-sm">
                <a href="{{ route('admin.analytics.index', ['range' => '7']) }}"
                   class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ $range === '7' ? 'bg-white text-emerald-900 shadow-md shadow-emerald-900/20 font-black' : 'text-emerald-100 hover:text-white hover:bg-white/10' }}">
                    7 Hari
                </a>
                <a href="{{ route('admin.analytics.index', ['range' => '30']) }}"
                   class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ $range === '30' ? 'bg-white text-emerald-900 shadow-md shadow-emerald-900/20 font-black' : 'text-emerald-100 hover:text-white hover:bg-white/10' }}">
                    30 Hari
                </a>
                <a href="{{ route('admin.analytics.index', ['range' => 'all']) }}"
                   class="px-4 py-2 rounded-xl text-xs font-bold transition-all {{ $range === 'all' ? 'bg-white text-emerald-900 shadow-md shadow-emerald-900/20 font-black' : 'text-emerald-100 hover:text-white hover:bg-white/10' }}">
                    Semua
                </a>
            </div>
        </div>
    </div>

    <!-- Summary Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Metric 1: Total Login & Activity -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Aktivitas Login ({{ $range === 'all' ? 'Total' : $range.' Hari' }})</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($logMetrics['successful_logins']) }}</h3>
                    <p class="text-[11px] text-emerald-600 font-bold mt-1 flex items-center">
                        <svg class="w-3.5 h-3.5 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Sesi login pengguna berhasil
                    </p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition-transform shrink-0 shadow-xs">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                </div>
            </div>
        </div>

        <!-- Metric 2: Active SSO Access Tokens -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Token SSO Aktif</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ number_format($ssoMetrics['active_tokens']) }}</h3>
                    <p class="text-[11px] text-blue-600 font-bold mt-1">
                        Dari {{ number_format($ssoMetrics['total_issued_tokens']) }} total token OAuth
                    </p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-200 flex items-center justify-center text-blue-600 group-hover:scale-110 transition-transform shrink-0 shadow-xs">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                </div>
            </div>
        </div>

        <!-- Metric 3: SIJUNA Sync Success Rate -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Tingkat Sukses Sync SIJUNA</span>
                    <h3 class="text-2xl font-black text-slate-900 mt-1">{{ $syncMetrics['success_rate'] }}%</h3>
                    <p class="text-[11px] text-indigo-600 font-bold mt-1">
                        {{ number_format($syncMetrics['successful_syncs']) }} / {{ number_format($syncMetrics['total_syncs']) }} siklus berhasil
                    </p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-indigo-50 border border-indigo-200 flex items-center justify-center text-indigo-600 group-hover:scale-110 transition-transform shrink-0 shadow-xs">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                </div>
            </div>
        </div>

        <!-- Metric 4: App Health Overview -->
        <div class="bg-white border border-slate-200/80 rounded-2xl p-5 shadow-xs hover:shadow-md transition-shadow relative overflow-hidden group">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Aplikasi Online</span>
                    <h3 class="text-2xl font-black text-emerald-600 mt-1">{{ $appHealthBreakdown['online'] }} Apps</h3>
                    <p class="text-[11px] text-slate-500 font-bold mt-1">
                        {{ $appHealthBreakdown['offline'] }} Offline • {{ $appHealthBreakdown['warning'] }} Warning
                    </p>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-600 group-hover:scale-110 transition-transform shrink-0 shadow-xs">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Analytics Content (Grid 2 Column) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left 2 Cols: User Role Distribution & Top Apps -->
        <div class="lg:col-span-2 space-y-8">
            <!-- User Role Distribution Progress Card -->
            <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-xs space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-black text-slate-900">Distribusi Akun Pengguna</h3>
                        <p class="text-xs text-slate-500 font-medium">Total {{ number_format($userDistribution['total']) }} akun pengguna terdaftar di Gateway SiPintu</p>
                    </div>
                    <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-mono font-bold">
                        {{ number_format($userDistribution['total']) }} Users
                    </span>
                </div>

                <!-- Combined Segmented Bar -->
                <div class="w-full h-4 bg-slate-100 rounded-full overflow-hidden flex shadow-inner">
                    <div style="width: {{ $userDistribution['students']['percentage'] }}%" class="bg-emerald-500 transition-all duration-500" title="Siswa: {{ $userDistribution['students']['percentage'] }}%"></div>
                    <div style="width: {{ $userDistribution['alumni']['percentage'] ?? 0 }}%" class="bg-teal-500 transition-all duration-500" title="Alumni: {{ $userDistribution['alumni']['percentage'] ?? 0 }}%"></div>
                    <div style="width: {{ $userDistribution['teachers']['percentage'] }}%" class="bg-blue-500 transition-all duration-500" title="Guru: {{ $userDistribution['teachers']['percentage'] }}%"></div>
                    <div style="width: {{ $userDistribution['dudi']['percentage'] }}%" class="bg-amber-500 transition-all duration-500" title="DUDI: {{ $userDistribution['dudi']['percentage'] }}%"></div>
                    <div style="width: {{ $userDistribution['admin']['percentage'] }}%" class="bg-violet-500 transition-all duration-500" title="Admin: {{ $userDistribution['admin']['percentage'] }}%"></div>
                </div>

                <!-- Individual Role Breakdown Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 pt-2">
                    <!-- Siswa -->
                    <div class="p-3.5 rounded-2xl bg-emerald-50/60 border border-emerald-100 text-center">
                        <span class="text-[11px] font-bold text-emerald-700 uppercase block">Siswa</span>
                        <span class="text-lg font-black text-emerald-950 block mt-0.5">{{ number_format($userDistribution['students']['count']) }}</span>
                        <span class="text-[10px] font-extrabold text-emerald-600 block">{{ $userDistribution['students']['percentage'] }}%</span>
                    </div>
                    <!-- Alumni -->
                    <div class="p-3.5 rounded-2xl bg-teal-50/60 border border-teal-100 text-center">
                        <span class="text-[11px] font-bold text-teal-700 uppercase block">Alumni</span>
                        <span class="text-lg font-black text-teal-950 block mt-0.5">{{ number_format($userDistribution['alumni']['count'] ?? 0) }}</span>
                        <span class="text-[10px] font-extrabold text-teal-600 block">{{ $userDistribution['alumni']['percentage'] ?? 0 }}%</span>
                    </div>
                    <!-- Guru -->
                    <div class="p-3.5 rounded-2xl bg-blue-50/60 border border-blue-100 text-center">
                        <span class="text-[11px] font-bold text-blue-700 uppercase block">Guru</span>
                        <span class="text-lg font-black text-blue-950 block mt-0.5">{{ number_format($userDistribution['teachers']['count']) }}</span>
                        <span class="text-[10px] font-extrabold text-blue-600 block">{{ $userDistribution['teachers']['percentage'] }}%</span>
                    </div>
                    <!-- DUDI -->
                    <div class="p-3.5 rounded-2xl bg-amber-50/60 border border-amber-100 text-center">
                        <span class="text-[11px] font-bold text-amber-700 uppercase block">DUDI</span>
                        <span class="text-lg font-black text-amber-950 block mt-0.5">{{ number_format($userDistribution['dudi']['count']) }}</span>
                        <span class="text-[10px] font-extrabold text-amber-600 block">{{ $userDistribution['dudi']['percentage'] }}%</span>
                    </div>
                    <!-- Admin -->
                    <div class="p-3.5 rounded-2xl bg-violet-50/60 border border-violet-100 text-center">
                        <span class="text-[11px] font-bold text-violet-700 uppercase block">Admin</span>
                        <span class="text-lg font-black text-violet-950 block mt-0.5">{{ number_format($userDistribution['admin']['count']) }}</span>
                        <span class="text-[10px] font-extrabold text-violet-600 block">{{ $userDistribution['admin']['percentage'] }}%</span>
                    </div>
                </div>
            </div>

            <!-- Top Most Accessed Applications Grid -->
            <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-xs space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-black text-slate-900">Aplikasi Paling Sering Diakses (SSO)</h3>
                        <p class="text-xs text-slate-500 font-medium">Berdasarkan total otorisasi token OAuth 2.0 oleh pengguna</p>
                    </div>
                    <a href="{{ route('admin.applications.index') }}" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 hover:underline">
                        Kelola Aplikasi &rarr;
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @forelse($topApps as $app)
                        <div class="p-4 rounded-2xl border border-slate-200/80 bg-slate-50/50 hover:bg-white hover:border-emerald-400 hover:shadow-md transition-all flex items-center justify-between">
                            <div class="flex items-center space-x-3 min-w-0">
                                <div class="w-10 h-10 rounded-xl bg-emerald-100 border border-emerald-200 flex items-center justify-center text-emerald-800 font-black text-sm shrink-0">
                                    #{{ $loop->iteration }}
                                </div>
                                <div class="min-w-0">
                                    <h4 class="text-xs font-black text-slate-900 truncate">{{ is_array($app) ? $app['name'] : $app->name }}</h4>
                                    <span class="text-[10px] text-slate-500 font-semibold block truncate">
                                        {{ (is_array($app) ? $app['category_name'] : ($app->category?->name ?? 'Umum')) }} • {{ (is_array($app) ? $app['status'] : $app->status) === 'active' ? '● Aktif' : '○ Inaktif' }}
                                    </span>
                                </div>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-black bg-emerald-100 text-emerald-800 font-mono">
                                    {{ is_array($app) ? $app['access_tokens_count'] : $app->access_tokens_count }} SSO
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full p-6 text-center text-slate-400 text-xs font-semibold">
                            Belum ada statistik aplikasi terdaftar.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right 1 Col: Monitoring Summary & SIJUNA Status -->
        <div class="space-y-8">
            <!-- SIJUNA Sync Status Widget -->
            <div class="bg-white border border-slate-200/80 rounded-3xl p-6 shadow-xs space-y-5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <h3 class="text-base font-black text-slate-900">Status Sync SIJUNA</h3>
                    <a href="{{ route('admin.sijuna.index') }}" class="text-xs font-bold text-emerald-600 hover:underline">
                        Buka SIJUNA
                    </a>
                </div>

                @if(!empty($syncMetrics['has_latest']))
                    <div class="p-4 rounded-2xl bg-emerald-50/40 border border-emerald-100 space-y-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-600 font-semibold">Status Terakhir</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase {{ $syncMetrics['latest_sync_status'] === 'success' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-rose-100 text-rose-800 border border-rose-300' }}">
                                {{ $syncMetrics['latest_sync_status'] }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-600 font-semibold">Data Diproses (Terakhir)</span>
                            <span class="font-bold text-slate-900 font-mono">{{ number_format($syncMetrics['latest_sync_records']) }} Record</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-600 font-semibold">Total Data Diproses</span>
                            <span class="font-bold text-emerald-700 font-mono">{{ number_format($syncMetrics['total_records_processed'] ?? $syncMetrics['latest_sync_records']) }} Record</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-slate-600 font-semibold">Waktu Jalur</span>
                            <span class="font-semibold text-slate-700">{{ $syncMetrics['latest_sync_time'] }}</span>
                        </div>
                    </div>
                @else
                    <div class="p-4 rounded-2xl bg-slate-50 text-center text-xs text-slate-500 font-medium border border-slate-200/60">
                        Belum ada catatan sinkronisasi.
                    </div>
                @endif

                <form action="{{ route('admin.sijuna.sync') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-xs transition-all shadow-md shadow-emerald-600/20 flex items-center justify-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        <span>Jalankan Sync Sekarang</span>
                    </button>
                </form>
            </div>

            <!-- Fast Action Launcher -->
            <div class="bg-gradient-to-br from-emerald-50 via-teal-50 to-emerald-100/60 border border-emerald-200/80 rounded-3xl p-6 shadow-sm space-y-4">
                <div class="flex items-center space-x-2 text-emerald-900">
                    <svg class="w-5 h-5 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    <h3 class="text-sm font-black tracking-wide text-emerald-950">Alat Diagnostik & Monitoring</h3>
                </div>
                <p class="text-slate-600 text-xs leading-relaxed font-medium">
                    Uji kesehatan routing SSO, verifikasi CSRF bypass, dan validasi seluruh endpoint secara instan.
                </p>

                <div class="space-y-2 pt-2">
                    <a href="{{ route('admin.monitoring.index') }}"
                       class="w-full py-2.5 px-4 bg-white hover:bg-emerald-50 text-emerald-900 border border-emerald-200/80 font-extrabold rounded-xl text-xs transition-all shadow-xs hover:shadow-md flex items-center justify-between">
                        <span>Monitoring Dashboard</span>
                        <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                    <a href="{{ route('admin.audit-logs.index') }}"
                       class="w-full py-2.5 px-4 bg-white hover:bg-emerald-50 text-emerald-900 border border-emerald-200/80 font-extrabold rounded-xl text-xs transition-all shadow-xs hover:shadow-md flex items-center justify-between">
                        <span>Audit Log Keamanan</span>
                        <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
