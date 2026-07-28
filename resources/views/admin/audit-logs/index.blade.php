@extends('layouts.app', ['headerTitle' => 'Audit Log Aktivitas'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white">Catatan Audit Log & Aktivitas Keamanan</h2>
            <p class="text-xs text-slate-400 mt-1">Audit log mencatat login, SSO exchange, pembuatan user, dan perubahan hak akses secara permanen</p>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[240px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari berdasarkan Aktivitas, Pengguna, atau IP Address..."
                    class="w-full px-4 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
            </div>

            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-semibold rounded-xl transition-all">
                Cari Audit Log
            </button>
        </form>
    </div>

    <!-- Audit Logs Table -->
    <div class="bg-slate-900 rounded-2xl border border-slate-800 overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950 text-slate-400 uppercase font-bold text-[10px] border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Waktu (Timestamp)</th>
                        <th class="px-6 py-4">Pengguna</th>
                        <th class="px-6 py-4">Aktivitas (Event)</th>
                        <th class="px-6 py-4">IP Address</th>
                        <th class="px-6 py-4">User Agent</th>
                        <th class="px-6 py-4">Metadata Payload</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-mono">
                    @forelse($auditLogs as $log)
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-slate-400 text-[11px]">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-sans font-bold text-white">
                                {{ $log->user?->name ?? 'System / Anonymous' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded text-[10px] font-bold uppercase bg-indigo-500/10 text-indigo-300 border border-indigo-500/20">
                                    {{ $log->activity }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-slate-300">
                                {{ $log->ip_address ?? '127.0.0.1' }}
                            </td>
                            <td class="px-6 py-4 text-slate-400 max-w-xs truncate text-[11px]" title="{{ $log->user_agent }}">
                                {{ $log->user_agent ?: '-' }}
                            </td>
                            <td class="px-6 py-4 text-slate-400 max-w-sm truncate text-[11px]">
                                {{ json_encode($log->metadata) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400 font-sans">
                                Belum ada data audit log.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-800">
            {{ $auditLogs->links() }}
        </div>
    </div>
</div>
@endsection
