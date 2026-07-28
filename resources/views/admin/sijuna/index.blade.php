@extends('layouts.app', ['headerTitle' => 'Integrasi API SIJUNA'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white">Integrasi & Sinkronisasi API SIJUNA</h2>
            <p class="text-xs text-slate-400 mt-1">Konfigurasi koneksi backend Gateway dengan SIJUNA External API</p>
        </div>

        <form action="{{ route('admin.sijuna.sync') }}" method="POST">
            @csrf
            <button type="submit" class="px-5 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-emerald-600/30 flex items-center space-x-2 transition-all">
                <svg class="w-4 h-4 animate-spin-hover" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                <span>Jalankan Sinkronisasi Sekarang</span>
            </button>
        </form>
    </div>

    <!-- Configuration Summary Box -->
    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
        <h3 class="text-sm font-bold text-white uppercase tracking-wider">Parameter Konfigurasi Backend (config/services.php)</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
            <div class="p-3.5 rounded-xl bg-slate-950 border border-slate-800">
                <span class="text-slate-400 block mb-1">SIJUNA API URL Endpoint</span>
                <span class="font-mono text-indigo-300 font-bold block truncate">{{ $config['url'] }}</span>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-950 border border-slate-800">
                <span class="text-slate-400 block mb-1">SIJUNA API Token (X-API-Token Header)</span>
                <span class="font-mono text-emerald-400 font-bold block">{{ $config['token_masked'] }}</span>
                <span class="text-[10px] text-amber-400 block mt-1">Terlindungi & Tidak Pernah Diberikan ke Frontend</span>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-950 border border-slate-800">
                <span class="text-slate-400 block mb-1">Timeout & Retries</span>
                <span class="font-bold text-white block">{{ $config['timeout'] }}s Timeout / {{ $config['retry_times'] }} Retries</span>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-950 border border-slate-800">
                <span class="text-slate-400 block mb-1">Siswa Tersinkronisasi</span>
                <span class="font-bold text-emerald-400 text-base block">{{ number_format($syncedStudentsCount) }} Akun Siswa</span>
            </div>
        </div>
    </div>

    <!-- Sync Logs History Table -->
    <div class="bg-slate-900 rounded-2xl border border-slate-800 overflow-hidden shadow-xl space-y-4 p-6">
        <h3 class="text-base font-bold text-white">Riwayat Sinkronisasi (Sync Logs)</h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950 text-slate-400 uppercase font-bold text-[10px] border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">ID Log</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Jumlah Data Diproses</th>
                        <th class="px-4 py-3">Waktu Mulai</th>
                        <th class="px-4 py-3">Waktu Selesai</th>
                        <th class="px-4 py-3">Pesan Error / Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 font-mono">
                    @forelse($syncLogs as $log)
                        <tr class="hover:bg-slate-800/30">
                            <td class="px-4 py-3 text-slate-400">#{{ $log->id }}</td>
                            <td class="px-4 py-3 font-sans">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                                    {{ $log->status === 'success' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : '' }}
                                    {{ $log->status === 'failed' ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20' : '' }}
                                    {{ $log->status === 'in_progress' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : '' }}">
                                    {{ $log->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-white font-bold">{{ number_format($log->records_processed) }} Record</td>
                            <td class="px-4 py-3 text-slate-400">{{ $log->started_at?->format('d/m/Y H:i:s') }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ $log->completed_at?->format('d/m/Y H:i:s') ?? '-' }}</td>
                            <td class="px-4 py-3 text-rose-300 max-w-xs truncate font-sans text-xs">
                                {{ $log->error_message ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-slate-400 font-sans">
                                Belum ada catatan riwayat sinkronisasi.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pt-3">
            {{ $syncLogs->links() }}
        </div>
    </div>
</div>
@endsection
