@extends('layouts.app', ['headerTitle' => 'Integrasi API SIJUNA'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
        <div>
            <h2 class="text-xl font-black text-emerald-950">Integrasi & Sinkronisasi API SIJUNA</h2>
            <p class="text-xs text-slate-600 font-medium mt-1">Konfigurasi koneksi backend Gateway dengan SIJUNA External API</p>
        </div>

        <form action="{{ route('admin.sijuna.sync') }}" method="POST" x-data="{ loading: false }" @submit="loading = true">
            @csrf
            <button type="submit" :disabled="loading" class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 disabled:bg-emerald-600 disabled:opacity-80 text-white text-xs font-extrabold rounded-xl shadow-md shadow-emerald-700/20 flex items-center space-x-2 transition-all cursor-pointer disabled:cursor-not-allowed">
                <svg class="w-4 h-4 shrink-0 transition-transform" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                <span x-text="loading ? 'Menyinkronkan Data SIJUNA...' : 'Jalankan Sinkronisasi Sekarang'">Jalankan Sinkronisasi Sekarang</span>
            </button>
        </form>
    </div>

    <!-- Configuration Summary Box -->
    <div class="p-6 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-4">
        <h3 class="text-xs font-black text-emerald-950 uppercase tracking-wider">Parameter Konfigurasi Backend (config/services.php)</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 text-xs">
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                <span class="text-slate-600 font-semibold block mb-1">SIJUNA API URL Endpoint</span>
                <span class="font-mono text-emerald-800 font-bold block truncate">{{ $config['url'] }}</span>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                <span class="text-slate-600 font-semibold block mb-1">SIJUNA API Token</span>
                <span class="font-mono text-emerald-800 font-bold block">{{ $config['token_masked'] }}</span>
                <span class="text-[10px] text-amber-700 font-bold block mt-1">Terlindungi (Header Only)</span>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                <span class="text-slate-600 font-semibold block mb-1">Timeout & Retries</span>
                <span class="font-bold text-slate-900 block">{{ $config['timeout'] }}s / {{ $config['retry_times'] }} Retries</span>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                <span class="text-slate-600 font-semibold block mb-1">Siswa Tersinkronisasi</span>
                <span class="font-black text-emerald-700 text-base block">{{ number_format($syncedStudentsCount) }} Akun</span>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                <span class="text-slate-600 font-semibold block mb-1">Alumni Tersinkronisasi</span>
                <span class="font-black text-cyan-700 text-base block">{{ number_format($syncedAlumniCount ?? 0) }} Akun</span>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                <span class="text-slate-600 font-semibold block mb-1">Guru Tersinkronisasi</span>
                <span class="font-black text-teal-700 text-base block">{{ number_format($syncedTeachersCount ?? 0) }} Akun</span>
            </div>
        </div>
    </div>

    <!-- Sync Logs History Table -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm space-y-4 p-6">
        <h3 class="text-base font-black text-emerald-950">Riwayat Sinkronisasi (Sync Logs)</h3>

        <div class="overflow-x-auto border border-slate-200 rounded-xl">
            <table class="w-full text-left text-xs">
                <thead class="bg-emerald-50 text-emerald-900 uppercase font-black text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">ID Log</th>
                        <th class="px-4 py-3">Tipe Sync</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Jumlah Data Diproses</th>
                        <th class="px-4 py-3">Waktu Mulai</th>
                        <th class="px-4 py-3">Waktu Selesai</th>
                        <th class="px-4 py-3">Pesan Error / Detail</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 font-mono text-slate-700 bg-white">
                    @forelse($syncLogs as $log)
                        <tr class="hover:bg-emerald-50/50">
                            <td class="px-4 py-3 font-bold text-slate-500">#{{ $log->id }}</td>
                            <td class="px-4 py-3 font-sans">
                                <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200 inline-flex items-center">
                                    {{ $log->sync_type_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-sans">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase
                                    {{ $log->status === 'success' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : '' }}
                                    {{ $log->status === 'failed' ? 'bg-rose-100 text-rose-800 border border-rose-300' : '' }}
                                    {{ $log->status === 'in_progress' ? 'bg-amber-100 text-amber-800 border border-amber-300' : '' }}">
                                    {{ $log->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-900 font-bold">{{ number_format($log->records_processed) }} Record</td>
                            <td class="px-4 py-3 text-slate-600 font-semibold">{{ $log->started_at?->format('d/m/Y H:i:s') }}</td>
                            <td class="px-4 py-3 text-slate-600 font-semibold">{{ $log->completed_at?->format('d/m/Y H:i:s') ?? '-' }}</td>
                            <td class="px-4 py-3 text-rose-600 max-w-xs truncate font-sans text-xs font-medium">
                                {{ $log->error_message ?: '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-slate-500 font-sans font-medium">
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
