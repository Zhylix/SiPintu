@extends('layouts.app', ['headerTitle' => 'Dashboard Gateway SMKN 1 BANGSRI'])

@section('content')
<div class="space-y-8">
    <!-- Top Stats Overview Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Total Users Stat -->
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-md relative overflow-hidden group hover:border-emerald-500 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Total Pengguna Gateway</span>
                    <div class="text-3xl font-black text-emerald-950 mt-1">{{ number_format($stats['total_users']) }}</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-100 border border-emerald-300 text-emerald-800 flex items-center justify-center font-bold">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600 font-semibold">
                <span>Siswa: <strong class="text-emerald-700 font-black">{{ $stats['students_count'] }}</strong></span>
                <span>Guru: <strong class="text-emerald-700 font-black">{{ $stats['teachers_count'] }}</strong></span>
                <span>DUDI: <strong class="text-emerald-700 font-black">{{ $stats['dudi_count'] }}</strong></span>
            </div>
        </div>

        <!-- Registered External Apps -->
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-md relative overflow-hidden group hover:border-emerald-500 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Aplikasi Terdaftar</span>
                    <div class="text-3xl font-black text-emerald-950 mt-1">{{ number_format($stats['applications_count']) }}</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-100 border border-emerald-300 text-emerald-800 flex items-center justify-center font-bold">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600 font-semibold">
                <span>Aktif: <strong class="text-emerald-700 font-black">{{ $stats['active_apps_count'] }}</strong></span>
                <span>OAuth 2.0 Clients</span>
            </div>
        </div>

        <!-- SIJUNA Identity Sync -->
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-md relative overflow-hidden group hover:border-emerald-500 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Status Sinkron SIJUNA</span>
                    <div class="text-lg font-black text-emerald-700 mt-1 capitalize">{{ $latestSync?->status ?? 'Tersinkron' }}</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-100 border border-emerald-300 text-emerald-800 flex items-center justify-center font-bold">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 text-xs text-slate-600 font-semibold flex justify-between">
                <span>Terakhir: {{ $latestSync?->completed_at?->diffForHumans() ?? 'Baru Saja' }}</span>
                <span class="font-bold text-emerald-950">{{ $stats['students_count'] }} Siswa</span>
            </div>
        </div>

        <!-- SSO Active Tokens -->
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-md relative overflow-hidden group hover:border-amber-500 transition-all">
            <div class="flex items-center justify-between">
                <div>
                    <span class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Token SSO Aktif</span>
                    <div class="text-3xl font-black text-emerald-950 mt-1">{{ number_format($stats['sso_tokens_count']) }}</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-100 border border-amber-300 text-amber-800 flex items-center justify-center font-bold">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Registered Applications Status Grid -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-black text-emerald-950">Aplikasi Eksternal Terdaftar (Registry & Access Control)</h3>
                <p class="text-xs text-slate-600 font-medium mt-0.5">Daftar aplikasi eksternal tersambung OAuth 2.0 / Gateway SMKN 1 Bangsri</p>
            </div>
            <a href="{{ route('admin.applications.create') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-600/20">
                + Daftarkan Aplikasi
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
            @foreach($applications as $app)
                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-3 hover:border-emerald-400 transition-all">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-sm font-black text-slate-900 block">{{ $app->name }}</span>
                            <a href="{{ $app->base_url }}" target="_blank" class="text-xs text-emerald-700 font-extrabold hover:underline font-mono">{{ $app->base_url }}</a>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase {{ $app->status === 'active' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-slate-200 text-slate-600' }}">
                            {{ $app->status }}
                        </span>
                    </div>

                    <p class="text-xs text-slate-600 font-medium line-clamp-2">{{ $app->description }}</p>

                    <div class="pt-2 border-t border-slate-200 flex items-center justify-between text-xs">
                        <span class="text-slate-500 font-semibold">Role Diizinkan:</span>
                        <div class="flex flex-wrap gap-1">
                            @foreach($app->roles as $role)
                                <span class="px-1.5 py-0.5 rounded text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300 uppercase">
                                    {{ $role->slug }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Recent Audit Activity Log Stream -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-black text-emerald-950">Stream Activity & Audit Log Terbaru</h3>
                <p class="text-xs text-slate-600 font-medium mt-0.5">Catatan realtime autentikasi, SSO login, dan perubahan konfigurasi gateway</p>
            </div>
            <a href="{{ route('admin.audit-logs.index') }}" class="text-xs font-extrabold text-emerald-700 hover:underline">
                Lihat Seluruh Audit Log &rarr;
            </a>
        </div>

        <div class="overflow-x-auto border border-slate-200 rounded-xl">
            <table class="w-full text-left text-xs">
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
                        <tr class="hover:bg-emerald-50/50">
                            <td class="px-4 py-3 whitespace-nowrap text-slate-500 font-semibold">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="px-4 py-3 whitespace-nowrap font-sans font-bold text-slate-900">
                                {{ $log->user?->name ?? 'Guest / System' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    {{ $log->activity }}
                                </span>
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
