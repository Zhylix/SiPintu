@extends('layouts.app', ['headerTitle' => 'Audit Log Aktivitas'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
        <div>
            <h2 class="text-xl font-black text-emerald-950">Catatan Audit Log & Aktivitas Keamanan</h2>
            <p class="text-xs text-slate-600 font-medium mt-1">Audit log mencatat login, SSO exchange, kegagalan autentikasi SSO, pembuatan user, dan perubahan hak akses secara permanen</p>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-4">
        <!-- Quick Filter Category Tabs -->
        <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs font-extrabold no-scrollbar">
            <a href="{{ route('admin.audit-logs.index', array_merge(request()->except('type', 'page'), ['type' => 'all'])) }}"
               class="px-3.5 py-1.5 rounded-xl border transition-all flex items-center gap-1.5 shrink-0 {{ !request('type') || request('type') === 'all' ? 'bg-emerald-800 text-white border-emerald-800 shadow-sm' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' }}">
                <span>Semua Log</span>
            </a>
            <a href="{{ route('admin.audit-logs.index', array_merge(request()->except('type', 'page'), ['type' => 'sso_failed'])) }}"
               class="px-3.5 py-1.5 rounded-xl border transition-all flex items-center gap-1.5 shrink-0 {{ request('type') === 'sso_failed' ? 'bg-rose-700 text-white border-rose-700 shadow-sm' : 'bg-rose-50 text-rose-800 border-rose-200 hover:bg-rose-100' }}">
                <svg class="w-3.5 h-3.5 {{ request('type') === 'sso_failed' ? 'text-white' : 'text-rose-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>Gagal SSO (SSO Failure)</span>
            </a>
            <a href="{{ route('admin.audit-logs.index', array_merge(request()->except('type', 'page'), ['type' => 'sso'])) }}"
               class="px-3.5 py-1.5 rounded-xl border transition-all flex items-center gap-1.5 shrink-0 {{ request('type') === 'sso' ? 'bg-sky-700 text-white border-sky-700 shadow-sm' : 'bg-sky-50 text-sky-800 border-sky-200 hover:bg-sky-100' }}">
                <svg class="w-3.5 h-3.5 {{ request('type') === 'sso' ? 'text-white' : 'text-sky-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                <span>Event SSO Gateway</span>
            </a>
            <a href="{{ route('admin.audit-logs.index', array_merge(request()->except('type', 'page'), ['type' => 'login'])) }}"
               class="px-3.5 py-1.5 rounded-xl border transition-all flex items-center gap-1.5 shrink-0 {{ request('type') === 'login' ? 'bg-emerald-700 text-white border-emerald-700 shadow-sm' : 'bg-emerald-50 text-emerald-800 border-emerald-200 hover:bg-emerald-100' }}">
                <svg class="w-3.5 h-3.5 {{ request('type') === 'login' ? 'text-white' : 'text-emerald-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <span>Aktivitas Login</span>
            </a>
        </div>

        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="flex flex-wrap items-center gap-3">
            @if(request('type'))
                <input type="hidden" name="type" value="{{ request('type') }}">
            @endif
            <div class="flex-1 min-w-[240px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari berdasarkan Aktivitas, Pengguna, atau IP Address..."
                    class="w-full px-4 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-900 font-semibold placeholder-slate-400 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all">
            </div>

            <button type="submit" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl shadow-md shadow-emerald-700/20 transition-all">
                Cari Audit Log
            </button>
            @if(request('search') || request('type'))
                <a href="{{ route('admin.audit-logs.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all">
                    Reset Filter
                </a>
            @endif
        </form>
    </div>

    <!-- Audit Logs Table -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-emerald-50 text-emerald-900 uppercase font-black text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">Waktu (Timestamp)</th>
                        <th class="px-6 py-4">Pengguna</th>
                        <th class="px-6 py-4">Aktivitas (Event)</th>
                        <th class="px-6 py-4">IP Address</th>
                        <th class="px-6 py-4">User Agent</th>
                        <th class="px-6 py-4">Metadata Payload</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 font-mono text-slate-700 bg-white">
                    @forelse($auditLogs as $log)
                        @php
                            $isSsoFail = $log->isSsoFailure();
                            $isSsoEvt = $log->isSsoEvent();
                        @endphp
                        <tr class="transition-colors {{ $isSsoFail ? 'bg-rose-50/60 hover:bg-rose-100/50' : 'hover:bg-emerald-50/50' }}">
                            <td class="px-6 py-4 whitespace-nowrap text-slate-500 font-semibold text-[11px]">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-sans font-bold text-slate-900">
                                {{ $log->user?->name ?? 'System / Anonymous' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($isSsoFail)
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-rose-100 text-rose-900 border-2 border-rose-300 inline-flex items-center gap-1.5 shadow-2xs">
                                        <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                        <span>GAGAL SSO: {{ $log->activity }}</span>
                                    </span>
                                @elseif($isSsoEvt)
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-sky-100 text-sky-900 border border-sky-300 inline-flex items-center gap-1">
                                        <span>SSO &bull; {{ $log->activity }}</span>
                                    </span>
                                @elseif(str_contains($log->activity, 'failed'))
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300">
                                        {{ $log->activity }}
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">
                                        {{ $log->activity }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-slate-600 font-bold">
                                {{ $log->ip_address ?? '127.0.0.1' }}
                            </td>
                            <td class="px-6 py-4 text-slate-600 font-medium max-w-xs truncate text-[11px]" title="{{ $log->user_agent }}">
                                {{ $log->user_agent ?: '-' }}
                            </td>
                            <td class="px-6 py-4 text-slate-600 font-medium max-w-sm truncate text-[11px]">
                                {{ json_encode($log->metadata) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500 font-sans font-medium">
                                Belum ada data audit log.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-200">
            {{ $auditLogs->links() }}
        </div>
    </div>
</div>
@endsection

