@extends('layouts.app', ['headerTitle' => 'Audit Log WhatsApp Gateway'])

@section('content')
<div class="space-y-6 min-w-0 max-w-full">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-sm">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-semibold mb-1">
                <a href="{{ route('admin.whatsapp-gateway.index') }}" class="hover:underline text-emerald-700">WhatsApp Gateway</a> &rarr; <span>Audit Log</span>
            </div>
            <h2 class="text-xl font-black text-emerald-950 tracking-tight">Audit Log Pengiriman Gateway</h2>
            <p class="text-xs text-slate-600 font-medium mt-1">Seluruh riwayat pesan keluar dari aplikasi downstream dan pengumuman pusat.</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.whatsapp-gateway.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all">
                &larr; Kembali ke Dashboard Gateway
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <form method="GET" action="{{ route('admin.whatsapp-gateway.logs') }}" class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Cari Nomor / Pesan / Sesi</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik kata kunci..." class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Aplikasi Pengirim</label>
                <select name="application_id" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500">
                    <option value="all">Semua Aplikasi</option>
                    @foreach($applications as $app)
                        <option value="{{ $app->id }}" {{ request('application_id') == $app->id ? 'selected' : '' }}>
                            {{ $app->name }} ({{ $app->getEffectiveWaSessionId() }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 mb-1">Status Pengiriman</label>
                <select name="status" class="w-full text-xs px-3 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500">
                    <option value="all">Semua Status</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Terkirim</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Gagal</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending / Antrean</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl transition-all shadow-sm">
                    Filter Data
                </button>
                <a href="{{ route('admin.whatsapp-gateway.logs') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold rounded-xl transition-all">
                    Reset
                </a>
            </div>
        </div>
    </form>

    <!-- Logs Table -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-700 uppercase font-black text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">ID</th>
                        <th class="px-4 py-3">Waktu</th>
                        <th class="px-4 py-3">Aplikasi / Sumber</th>
                        <th class="px-4 py-3">Session ID</th>
                        <th class="px-4 py-3">Tujuan</th>
                        <th class="px-4 py-3">Isi Pesan</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-4 py-3 font-mono text-[11px] text-slate-400">
                                #{{ $log->id }}
                            </td>
                            <td class="px-4 py-3 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="px-4 py-3 font-bold text-slate-900 whitespace-nowrap">
                                @if($log->application)
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                                        {{ $log->application->name }}
                                    </span>
                                @elseif($log->announcement)
                                    <span class="inline-flex items-center gap-1.5 text-indigo-700">
                                        <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                                        Pengumuman: {{ Str::limit($log->announcement->title, 20) }}
                                    </span>
                                @else
                                    <span class="text-slate-500">SiPintu Pusat</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-[11px] text-slate-600">
                                <span class="px-2 py-0.5 rounded bg-slate-100 font-semibold">{{ $log->session_id ?? 'default' }}</span>
                            </td>
                            <td class="px-4 py-3 font-mono font-bold text-slate-800 whitespace-nowrap">
                                +{{ $log->phone_number }}
                            </td>
                            <td class="px-4 py-3 max-w-sm">
                                <div class="truncate text-slate-700" title="{{ $log->message }}">
                                    {{ $log->message }}
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($log->status === 'sent')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        Terkirim
                                    </span>
                                @elseif($log->status === 'pending')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                        Antrean
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                        Gagal
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-[11px] text-slate-500 max-w-xs truncate" title="{{ $log->error_message }}">
                                {{ $log->error_message ?? ($log->sent_at ? 'Terkirim pada '.$log->sent_at->format('H:i:s') : '-') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-400">
                                Tidak ada log pesan yang cocok dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
