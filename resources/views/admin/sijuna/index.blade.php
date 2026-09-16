@extends('layouts.app', ['headerTitle' => 'Integrasi API SIJUNA'])

@section('content')
<div class="space-y-6 min-w-0 max-w-full">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-sm min-w-0 max-w-full">
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

    <!-- Sync Result Report Banner (Notifikasi Hasil Sinkronisasi) -->
    @if(session('sync_report'))
        @php
            $report = session('sync_report');
            $types = $report['types'] ?? [];
            $skippedItems = $report['skipped_items'] ?? [];
            $warnings = $report['warnings'] ?? [];
        @endphp
        <div class="p-5 rounded-2xl bg-white border border-emerald-300 shadow-md space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-slate-100 gap-2">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center font-bold shrink-0">
                        <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <h4 class="font-black text-sm text-emerald-950">Laporan Rincian Sinkronisasi SIJUNA</h4>
                        <p class="text-xs text-slate-500 font-medium">Data yang berhasil diambil per tipe dan evaluasi data yang dilewati.</p>
                    </div>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-black uppercase bg-emerald-100 text-emerald-900 border border-emerald-300 self-start sm:self-auto">
                    Total Berhasil: {{ number_format($report['total_fetched'] ?? 0) }} Data
                </span>
            </div>

            <!-- Tipe Data yang Diambil -->
            <div>
                <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider block mb-2">1. Jumlah Data yang Berhasil Diambil (Berdasarkan Tipe)</span>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                    <div class="p-3.5 rounded-xl bg-emerald-50/70 border border-emerald-200 flex items-center justify-between">
                        <div>
                            <span class="font-extrabold text-emerald-950 block text-xs">Siswa Aktif</span>
                            <span class="text-[10px] text-slate-500">Terdaftar dalam kelas</span>
                        </div>
                        <span class="font-black text-emerald-700 text-base font-mono">{{ number_format($types['siswa'] ?? 0) }}</span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-teal-50/70 border border-teal-200 flex items-center justify-between">
                        <div>
                            <span class="font-extrabold text-teal-950 block text-xs">Alumni</span>
                            <span class="text-[10px] text-slate-500">Status Lulus (Graduated)</span>
                        </div>
                        <span class="font-black text-teal-700 text-base font-mono">{{ number_format($types['alumni'] ?? 0) }}</span>
                    </div>
                    <div class="p-3.5 rounded-xl bg-cyan-50/70 border border-cyan-200 flex items-center justify-between">
                        <div>
                            <span class="font-extrabold text-cyan-950 block text-xs">Guru & Tenaga Pendidik</span>
                            <span class="text-[10px] text-slate-500">Akun GTK SIJUNA</span>
                        </div>
                        <span class="font-black text-cyan-700 text-base font-mono">{{ number_format($types['guru'] ?? 0) }}</span>
                    </div>
                </div>
            </div>

            <!-- Data yang belum diambil / dilewati beserta alasannya -->
            @if(!empty($skippedItems))
                <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 space-y-2 text-xs">
                    <div class="font-black text-amber-900 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-amber-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        <span>{{ count($skippedItems) }} Data Belum Diambil / Dilewati:</span>
                    </div>
                    <div class="max-h-48 overflow-y-auto space-y-1.5 pr-1">
                        @foreach($skippedItems as $skip)
                            <div class="p-2 rounded-lg bg-white/80 border border-amber-200 text-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                                <span class="font-bold text-slate-900">{{ $skip['identifier'] }}</span>
                                <span class="text-amber-900 font-semibold bg-amber-100/70 px-2 py-0.5 rounded text-[11px]">{{ $skip['reason'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Warning API atau error sambungan -->
            @if(!empty($warnings))
                <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 space-y-1.5 text-xs text-rose-900">
                    <div class="font-black flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-rose-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Peringatan / Error Sambungan SIJUNA API:</span>
                    </div>
                    @foreach($warnings as $warn)
                        <p class="font-medium text-[11px] pl-5">&bull; {{ $warn }}</p>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <!-- Configuration Summary Box -->
    <div class="p-4 sm:p-6 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-4 min-w-0 max-w-full">
        <h3 class="text-xs font-black text-emerald-950 uppercase tracking-wider">Parameter Konfigurasi Backend (config/services.php)</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 text-xs">
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                <span class="text-slate-600 font-semibold block mb-1">SIJUNA API URL Endpoint</span>
                <span class="font-mono text-emerald-800 font-bold block truncate">{{ $config['url'] }}</span>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 overflow-hidden">
                <span class="text-slate-600 font-semibold block mb-1">SIJUNA API Token</span>
                <span class="font-mono text-emerald-800 font-bold block truncate max-w-full overflow-hidden" title="{{ $config['token_masked'] }}">{{ $config['token_masked'] }}</span>
                <span class="text-[10px] text-amber-700 font-bold block mt-1">Terlindungi (Header Only)</span>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                <span class="text-slate-600 font-semibold block mb-1">Timeout & Retries</span>
                <span class="font-bold text-slate-900 block">{{ $config['timeout'] }}s / {{ $config['retry_times'] }} Retries</span>
                <span class="text-[10px] text-emerald-700 font-bold block mt-1">Jadwal: Per 3 Hari (00:00)</span>
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
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm space-y-4 p-4 sm:p-6 min-w-0 max-w-full">
        <h3 class="text-base font-black text-emerald-950">Riwayat Sinkronisasi (Sync Logs)</h3>

        <div class="overflow-x-auto border border-slate-200 rounded-xl w-full max-w-full">
            <table class="w-full text-left text-xs min-w-[650px]">
                <thead class="bg-emerald-50 text-emerald-900 uppercase font-black text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">ID Log</th>
                        <th class="px-4 py-3">Tipe Sync</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Jumlah Data Diproses</th>
                        <th class="px-4 py-3">Waktu Mulai</th>
                        <th class="px-4 py-3">Waktu Selesai</th>
                        <th class="px-4 py-3">Rincian Tipe & Alasan / Error</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 font-mono text-slate-700 bg-white">
                    @forelse($syncLogs as $log)
                        @php
                            $details = $log->details ?? [];
                            $hasSkipped = !empty($details['skipped_count']) && $details['skipped_count'] > 0;
                        @endphp
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
                            <td class="px-4 py-3 text-slate-900 font-bold">
                                {{ number_format($log->records_processed) }} Record
                                @if(!empty($details['students_count']) || !empty($details['alumni_count']) || !empty($details['teachers_count']))
                                    <span class="block text-[10px] font-normal text-slate-500 mt-0.5">
                                        @if(isset($details['students_count'])) {{ $details['students_count'] }} Siswa @endif
                                        @if(isset($details['alumni_count'])) • {{ $details['alumni_count'] }} Alumni @endif
                                        @if(isset($details['teachers_count'])) {{ $details['teachers_count'] }} Guru @endif
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600 font-semibold">{{ $log->started_at?->format('d/m/Y H:i:s') }}</td>
                            <td class="px-4 py-3 text-slate-600 font-semibold">{{ $log->completed_at?->format('d/m/Y H:i:s') ?? '-' }}</td>
                            <td class="px-4 py-3 font-sans text-xs max-w-sm">
                                @if($log->status === 'failed')
                                    <span class="text-rose-600 font-bold block">{{ $log->error_message ?: 'Gagal tanpa pesan spesifik' }}</span>
                                @elseif($hasSkipped)
                                    <div class="space-y-1">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300 inline-block">
                                            {{ $details['skipped_count'] }} Data Dilewati
                                        </span>
                                        <p class="text-[11px] text-slate-600 line-clamp-2" title="{{ $log->error_message }}">
                                            {{ $log->error_message }}
                                        </p>
                                    </div>
                                @elseif($log->error_message)
                                    <span class="text-slate-600 text-[11px]">{{ $log->error_message }}</span>
                                @else
                                    <span class="text-emerald-700 font-medium text-[11px] inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        Tersinkron Penuh
                                    </span>
                                @endif
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
