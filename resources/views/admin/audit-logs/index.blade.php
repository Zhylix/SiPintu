@extends('layouts.app', ['headerTitle' => 'Audit Log Aktivitas'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
        <div>
            <h2 class="text-xl font-black text-emerald-950">Catatan Audit Log & Aktivitas Keamanan</h2>
            <p class="text-xs text-slate-600 font-medium mt-1">Audit log mencatat login, SSO exchange, pembuatan user, dan perubahan hak akses secara permanen</p>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="flex flex-wrap items-center gap-3">
            <div class="flex-1 min-w-[240px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari berdasarkan Aktivitas, Pengguna, atau IP Address..."
                    class="w-full px-4 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-900 font-semibold placeholder-slate-400 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all">
            </div>

            <button type="submit" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl shadow-md shadow-emerald-700/20 transition-all">
                Cari Audit Log
            </button>
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
                        <tr class="hover:bg-emerald-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-slate-500 font-semibold text-[11px]">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-sans font-bold text-slate-900">
                                {{ $log->user?->name ?? 'System / Anonymous' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    {{ $log->activity }}
                                </span>
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
