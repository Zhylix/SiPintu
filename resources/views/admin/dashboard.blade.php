@extends('layouts.app', ['headerTitle' => 'Dashboard'])

@section('content')
<div class="space-y-6 sm:space-y-8 min-w-0 max-w-full">
    <!-- Top Stats Overview Grid -->
    <!-- Top Stats Overview Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4 sm:gap-5">
        <!-- Total Users Stat -->
        <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200 shadow-md relative overflow-hidden group hover:border-emerald-500 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <span class="text-xs font-extrabold text-slate-500 uppercase tracking-wider block truncate">Total Pengguna</span>
                    <div class="text-2xl sm:text-3xl font-black text-emerald-950 mt-1">{{ number_format($stats['total_users']) }}</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-100 border border-emerald-300 text-emerald-800 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600 font-semibold flex-wrap gap-x-2 gap-y-1">
                <span>Siswa: <strong class="text-emerald-700 font-black">{{ number_format($stats['students_count']) }}</strong></span>
                <span>Guru: <strong class="text-emerald-700 font-black">{{ number_format($stats['teachers_count']) }}</strong></span>
                <span>DUDI: <strong class="text-emerald-700 font-black">{{ number_format($stats['dudi_count']) }}</strong></span>
            </div>
        </div>

        <!-- Total Alumni Stat -->
        <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200 shadow-md relative overflow-hidden group hover:border-teal-500 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-extrabold text-teal-800 uppercase tracking-wider block truncate">Total Alumni</span>
                        <span class="px-1.5 py-0.5 text-[9px] font-black rounded bg-teal-50 text-teal-700 border border-teal-200 uppercase">Lulus</span>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-teal-950 mt-1">{{ number_format($stats['alumni_count'] ?? 0) }}</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-teal-100 border border-teal-300 text-teal-800 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14v7"></path></svg>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600 font-semibold">
                <span>Status: <strong class="text-teal-700 font-black">Alumni Terdata</strong></span>
                <span class="text-[10px] text-teal-700 font-bold bg-teal-50 px-2 py-0.5 rounded border border-teal-200">Graduated</span>
            </div>
        </div>

        <!-- Registered External Apps -->
        <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200 shadow-md relative overflow-hidden group hover:border-emerald-500 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <span class="text-xs font-extrabold text-slate-500 uppercase tracking-wider block truncate">Aplikasi Terdaftar</span>
                    <div class="text-2xl sm:text-3xl font-black text-emerald-950 mt-1">{{ number_format($stats['applications_count']) }}</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-100 border border-emerald-300 text-emerald-800 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600 font-semibold flex-wrap gap-1">
                <span>Aktif: <strong class="text-emerald-700 font-black">{{ $stats['active_apps_count'] }}</strong></span>
                <span>OAuth 2.0 Clients</span>
            </div>
        </div>

        <!-- SIJUNA Identity Sync -->
        <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200 shadow-md relative overflow-hidden group hover:border-emerald-500 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <span class="text-xs font-extrabold text-slate-500 uppercase tracking-wider block truncate">Status Sinkron SIJUNA</span>
                    <div class="text-lg font-black text-emerald-700 mt-1 capitalize truncate">{{ $latestSync?->status ?? 'Tersinkron' }}</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-100 border border-emerald-300 text-emerald-800 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-600 font-semibold flex items-center justify-between flex-wrap gap-1">
                <span>Terakhir: {{ $latestSync?->completed_at?->diffForHumans() ?? 'Baru Saja' }}</span>
                <span class="font-bold text-emerald-950">{{ number_format($stats['students_count']) }} Siswa &bull; {{ number_format($stats['alumni_count'] ?? 0) }} Alumni</span>
            </div>
        </div>

        <!-- SSO Active Tokens -->
        <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200 shadow-md relative overflow-hidden group hover:border-amber-500 transition-all flex flex-col justify-between">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <span class="text-xs font-extrabold text-slate-500 uppercase tracking-wider block truncate">Token SiPintu Aktif</span>
                    <div class="text-2xl sm:text-3xl font-black text-emerald-950 mt-1">{{ number_format($stats['sso_tokens_count']) }}</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-100 border border-amber-300 text-amber-800 flex items-center justify-center font-bold shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600 font-semibold">
                <span>Keamanan: <strong class="text-amber-700 font-black">OAuth 2.0</strong></span>
                <span class="text-[10px] text-slate-400">Bearer Sessions</span>
            </div>
        </div>
    </div>

    <!-- User Application Catalog Grid (Tampilan Perspektif User) -->
    <div class="space-y-4 pt-2">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-lg font-black text-emerald-950 whitespace-nowrap">Katalog Aplikasi</h3>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800 border border-emerald-300 whitespace-nowrap">
                        Pratinjau User
                    </span>
                </div>
                <p class="text-xs text-slate-600 font-medium mt-0.5">Akses cepat dan filter portal aplikasi Anda.</p>
            </div>
            <a href="{{ route('admin.apps') }}" class="text-xs font-extrabold text-emerald-700 hover:underline whitespace-nowrap shrink-0 inline-flex items-center gap-1">
                <span>Buka Halaman</span> &rarr;
            </a>
        </div>

        @include('partials.app-catalog-grid')
    </div>

    <!-- Registered Applications SSO Connection Status & Benchmark List Widget -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6 space-y-6 shadow-sm min-w-0 max-w-full" x-data="{ 
        filter: 'connected',
        init() {
            const stored = localStorage.getItem('sipintu_admin_dashboard_filter');
            if (stored && ['connected', 'disconnected', 'all'].includes(stored)) {
                this.filter = stored;
            }
            this.$watch('filter', val => localStorage.setItem('sipintu_admin_dashboard_filter', val));
        }
    }">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4 min-w-0">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-black text-emerald-950">Daftar Status Koneksi SSO Klien</h3>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">
                        Realtime List
                    </span>
                </div>
                <p class="text-xs text-slate-600 font-medium mt-0.5">Memantau koneksi aplikasi turunan yang tersambung ke SiPintu Gateway secara rinci.</p>
            </div>

            <!-- Interactive Connection Status Filter Tabs -->
            <div class="flex items-center space-x-1.5 bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs font-bold w-full sm:w-auto overflow-x-auto max-w-full no-scrollbar min-w-0">
                <button type="button" @click="filter = 'connected'" :class="filter === 'connected' ? 'bg-emerald-700 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'" class="px-3 py-1.5 rounded-lg transition-all flex items-center space-x-1.5 shrink-0 whitespace-nowrap">
                    <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                    <span>Connected</span>
                </button>
                <button type="button" @click="filter = 'disconnected'" :class="filter === 'disconnected' ? 'bg-rose-700 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'" class="px-3 py-1.5 rounded-lg transition-all flex items-center space-x-1.5 shrink-0 whitespace-nowrap">
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                    <span>Disconnected</span>
                </button>
                <button type="button" @click="filter = 'all'" :class="filter === 'all' ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-600 hover:text-slate-900'" class="px-3 py-1.5 rounded-lg transition-all shrink-0 whitespace-nowrap">
                    <span>Semua Aplikasi</span>
                </button>
            </div>
        </div>

        <!-- Patokan Standar Integrasi & Reference Info Box (Light Aesthetic) -->
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3 min-w-0 max-w-full">
            <div class="flex items-center justify-between flex-wrap gap-2">
                <div class="flex items-center space-x-2 font-black text-xs uppercase tracking-wider text-emerald-900">
                    <svg class="w-4 h-4 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Patokan Standar Parameter Koneksi SSO</span>
                </div>
                <span class="text-[10px] font-mono bg-white text-slate-700 px-2.5 py-0.5 rounded-md border border-slate-300 font-bold shrink-0">Standard Spec v2.0</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 text-xs font-sans">
                <div class="bg-white p-3 rounded-lg border border-slate-200 min-w-0">
                    <span class="text-slate-500 font-bold block text-[10px] uppercase">1. Syarat Terkoneksi</span>
                    <span class="font-bold text-emerald-800 block mt-0.5 text-[11px] break-words">Status Active + Client ID Match</span>
                </div>
                <div class="bg-white p-3 rounded-lg border border-slate-200 min-w-0">
                    <span class="text-slate-500 font-bold block text-[10px] uppercase">2. Auth & Token Endpoints</span>
                    <span class="font-mono text-slate-800 font-bold block mt-0.5 text-[11px] break-all">/oauth/authorize & /oauth/token</span>
                </div>
                <div class="bg-white p-3 rounded-lg border border-slate-200 min-w-0">
                    <span class="text-slate-500 font-bold block text-[10px] uppercase">3. Autentikasi Header API</span>
                    <span class="font-mono text-emerald-800 font-bold block mt-0.5 text-[11px] break-all">X-Client-ID & X-Client-Secret</span>
                </div>
                <div class="bg-white p-3 rounded-lg border border-slate-200 min-w-0">
                    <span class="text-slate-500 font-bold block text-[10px] uppercase">4. Patokan Latensi</span>
                    <span class="font-bold text-emerald-800 block mt-0.5 text-[11px]">Response Time &lt; 200 ms</span>
                </div>
            </div>
        </div>

        <!-- Clean Vertical List Layout -->
        <div class="space-y-3 min-w-0 max-w-full">
            @forelse($registeredApps as $app)
                @php
                    $isConnected = $app->status === 'active' && ($app->last_health_status !== 'offline');
                @endphp
                <div x-show="(filter === 'connected' && {{ $isConnected ? 'true' : 'false' }}) || (filter === 'disconnected' && {{ ! $isConnected ? 'true' : 'false' }}) || filter === 'all'"
                     class="p-4 rounded-xl border transition-all flex flex-col lg:flex-row lg:items-center justify-between gap-4 min-w-0 max-w-full {{ $isConnected ? 'bg-emerald-50/20 border-emerald-200 hover:border-emerald-400' : 'bg-rose-50/30 border-rose-200 hover:border-rose-300' }}">
                    
                    <!-- Left: App Identity & Client ID -->
                    <div class="flex items-start space-x-3.5 min-w-0 w-full lg:w-auto flex-1">
                        @if($app->logo_url)
                            <img src="{{ $app->logo_url }}" alt="{{ $app->name }}" class="w-10 h-10 rounded-xl object-cover border border-slate-200 shadow-2xs shrink-0">
                        @else
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 font-extrabold text-sm {{ $isConnected ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-rose-100 text-rose-800 border border-rose-300' }}">
                                {{ strtoupper(substr($app->name, 0, 2)) }}
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                                <h4 class="text-sm font-black text-slate-900 truncate max-w-full">{{ $app->name }}</h4>
                                <span class="px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-white text-emerald-800 border border-emerald-300 shrink-0 select-all">
                                    {{ $app->client_id }}
                                </span>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 text-xs mt-1 text-slate-500 font-mono min-w-0">
                                <a href="{{ $app->base_url }}" target="_blank" class="text-emerald-700 font-bold hover:underline truncate max-w-full sm:max-w-xs">{{ $app->base_url }}</a>
                                <span class="hidden sm:inline text-slate-300">&bull;</span>
                                <span class="truncate max-w-full sm:max-w-xs" title="{{ $app->redirect_uri }}">{{ $app->redirect_uri }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Middle: Allowed Roles -->
                    <div class="flex items-center space-x-1.5 flex-wrap gap-1 shrink-0">
                        <span class="text-xs text-slate-500 font-semibold mr-1">Role:</span>
                        @foreach($app->roles as $role)
                            <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-white text-slate-700 border border-slate-200 uppercase">
                                {{ $role->getDisplayName() }}
                            </span>
                        @endforeach
                    </div>

                    <!-- Right: Connection Badge & Action Button -->
                    <div class="flex items-center justify-between lg:justify-end space-x-3 shrink-0 pt-2 lg:pt-0 border-t lg:border-t-0 border-slate-100 w-full lg:w-auto">
                        @if($app->status === 'maintenance')
                            <span class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase bg-amber-100 text-amber-900 border-2 border-amber-300 inline-flex items-center gap-2 shadow-sm whitespace-nowrap">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"></span>
                                <span>MAINTENANCE</span>
                            </span>
                        @elseif($isConnected)
                            <span class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase bg-emerald-100 text-emerald-900 border-2 border-emerald-300 inline-flex items-center gap-2 shadow-sm whitespace-nowrap">
                                <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 animate-pulse shrink-0"></span>
                                <span>TERKONEKSI</span>
                            </span>
                        @else
                            <span class="px-3.5 py-1.5 rounded-xl text-xs font-black uppercase bg-rose-100 text-rose-900 border-2 border-rose-300 inline-flex items-center gap-2 shadow-sm whitespace-nowrap">
                                <span class="w-2.5 h-2.5 rounded-full bg-rose-600 shrink-0"></span>
                                <span>TERPUTUS</span>
                            </span>
                        @endif

                        <form action="{{ route('admin.applications.test-health', $app) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg text-xs font-extrabold transition-colors shadow-2xs">
                                Test Health
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center bg-slate-50 rounded-xl border border-slate-200 text-slate-500 text-xs font-semibold">
                    Belum ada aplikasi SSO Klien yang terdaftar.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Recent Audit Activity Log Stream -->
    <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-6 space-y-4 shadow-sm min-w-0 max-w-full">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4">
            <div>
                <h3 class="text-base font-black text-emerald-950">User Activity Log</h3>
                <p class="text-xs text-slate-600 font-medium mt-0.5">Catatan aktivitas autentikasi & SSO gateway.</p>
            </div>
            <a href="{{ route('admin.audit-logs.index') }}" class="text-xs font-extrabold text-emerald-700 hover:underline whitespace-nowrap shrink-0 inline-flex items-center gap-1">
                <span>Lihat Seluruh Log</span> &rarr;
            </a>
        </div>

        <div class="overflow-x-auto border border-slate-200 rounded-xl w-full max-w-full">
            <table class="w-full text-left text-xs min-w-[600px]">
                <thead class="bg-emerald-50 text-emerald-900 uppercase font-black text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Pengguna</th>
                        <th class="px-4 py-3">Aktivitas</th>
                        <th class="px-4 py-3">IP Address</th>
                        <th class="px-4 py-3">Metadata</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 font-mono text-slate-700 bg-white">
                    @foreach($latestAuditLogs as $log)
                        @php
                            $isSsoFail = $log->isSsoFailure();
                        @endphp
                        <tr class="transition-colors {{ $isSsoFail ? 'bg-rose-50/60 hover:bg-rose-100/50' : 'hover:bg-emerald-50/50' }}">
                            <td class="px-4 py-3 whitespace-nowrap text-slate-500 font-semibold">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="px-4 py-3 whitespace-nowrap font-sans font-bold text-slate-900">
                                {{ $log->user?->name ?? 'Guest / System' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($isSsoFail)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase bg-rose-100 text-rose-900 border border-rose-300 inline-flex items-center gap-1">
                                        <svg class="w-3 h-3 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        <span>GAGAL SSO: {{ $log->activity }}</span>
                                    </span>
                                @elseif($log->isSsoEvent())
                                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-sky-100 text-sky-900 border border-sky-300">
                                        SSO &bull; {{ $log->activity }}
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">
                                        {{ $log->activity }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-slate-500 font-semibold">{{ $log->ip_address ?? '-' }}</td>
                            <td class="px-4 py-3 text-slate-600 max-w-xs truncate font-mono text-[11px]">
                                {{ json_encode($log->metadata) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
