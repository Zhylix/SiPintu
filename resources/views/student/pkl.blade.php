@extends('layouts.app', ['headerTitle' => 'Jurnal & Status PKL Siswa'])

@section('content')
<div class="space-y-6">
    <!-- Real-time Status Card -->
    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4 relative overflow-hidden">
        <div class="flex flex-col md:flex-row md:items-center justify-between border-b border-slate-800 pb-4 gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-bold text-white">Detail Penempatan PKL / Industri</h3>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping mr-1"></span>
                        REAL-TIME SYNC
                    </span>
                </div>
                <p class="text-xs text-slate-400 mt-0.5">Informasi tempat, pembimbing, dan status terkini Praktik Kerja Lapangan</p>
            </div>
            
            <div class="flex items-center gap-3">
                <span id="pkl-status-badge" class="px-3.5 py-1.5 rounded-full text-xs font-bold transition-all duration-300 border {{ $pklStatus->badge_color }}">
                    Status: <span id="pkl-status-text">{{ $pklDetails['status'] }}</span>
                </span>
            </div>
        </div>

        <!-- Role Restriction Notice -->
        <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-between text-xs text-amber-300">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                <span><strong>Hak Akses Terkunci:</strong> Status PKL hanya dapat diubah oleh <strong>Mitra DUDI</strong> dan <strong>Administrator</strong>. Siswa hanya dapat memantau secara real-time.</span>
            </div>
            <span class="text-[10px] text-amber-400/80 font-mono hidden sm:block">Protected Action</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
            <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <span class="text-slate-400 block mb-1">Perusahaan / DUDI</span>
                <span id="pkl-company" class="text-sm font-bold text-white block">{{ $pklDetails['company_name'] }}</span>
                <span class="text-[11px] text-slate-400 block mt-1">{{ $pklDetails['address'] }}</span>
            </div>
            <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <span class="text-slate-400 block mb-1">Divisi / Department</span>
                <span id="pkl-division" class="text-sm font-bold text-indigo-300 block">{{ $pklDetails['division'] }}</span>
            </div>
            <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <span class="text-slate-400 block mb-1">Guru Pembimbing Sekolah</span>
                <span class="text-sm font-bold text-white block">{{ $pklDetails['mentor'] }}</span>
            </div>
            <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <span class="text-slate-400 block mb-1">Pembimbing Industri (DUDI)</span>
                <span class="text-sm font-bold text-amber-300 block">{{ $pklDetails['dudi_supervisor'] }}</span>
            </div>
        </div>

        <!-- Notes / Catatan DUDI -->
        <div class="p-4 rounded-xl bg-slate-950/40 border border-slate-800 text-xs">
            <span class="text-slate-400 block mb-1 font-semibold">Catatan dari DUDI / Admin:</span>
            <p id="pkl-notes" class="text-slate-300 italic">{{ $pklDetails['notes'] ?? 'Belum ada catatan tambahan.' }}</p>
            <span id="pkl-updated-by" class="text-[10px] text-slate-500 mt-2 block font-mono">Diperbarui oleh: {{ $pklDetails['updated_by'] }} ({{ $pklDetails['updated_at']->diffForHumans() }})</span>
        </div>
    </div>

    <!-- Daily Activity Logbook Table -->
    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
        <h3 class="text-base font-bold text-white">Logbook Aktivitas Harian Siswa</h3>
        
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950 text-slate-400 uppercase font-bold text-[10px] border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Uraian Kegiatan / Task</th>
                        <th class="px-4 py-3">Status Verifikasi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-300 font-mono">
                    @foreach($pklDetails['logs'] as $log)
                        <tr class="hover:bg-slate-800/40">
                            <td class="px-4 py-3 whitespace-nowrap text-slate-400">{{ $log['date'] }}</td>
                            <td class="px-4 py-3 font-sans font-medium text-white">{{ $log['activity'] }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    {{ $log['status'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Real-time Status Polling & SSE script -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        let currentStatus = "{{ $pklDetails['status'] }}";
        
        async function checkLiveStatus() {
            try {
                const response = await fetch("{{ route('pkl-status.live') }}", {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await response.json();
                if (data.success && data.status) {
                    const newStatus = data.status.status;
                    const badge = document.getElementById('pkl-status-badge');
                    const text = document.getElementById('pkl-status-text');
                    const notes = document.getElementById('pkl-notes');
                    const updatedBy = document.getElementById('pkl-updated-by');

                    if (text && text.innerText !== newStatus) {
                        text.innerText = newStatus;
                        badge.className = 'px-3.5 py-1.5 rounded-full text-xs font-bold transition-all duration-300 border ' + data.status.badge_color;
                        if (notes) notes.innerText = data.status.notes || 'Belum ada catatan tambahan.';
                        if (updatedBy) updatedBy.innerText = `Diperbarui oleh: ${data.status.updated_by} (${data.status.updated_at})`;
                        
                        // Show Realtime Toast
                        showToast(`Status PKL Anda diperbarui oleh DUDI/Admin menjadi: ${newStatus}`);
                        currentStatus = newStatus;
                    }
                }
            } catch (err) {
                console.error("Realtime poll error:", err);
            }
        }

        function showToast(msg) {
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-5 right-5 bg-emerald-600 text-white font-semibold text-xs px-4 py-3 rounded-xl shadow-2xl z-50 animate-bounce flex items-center gap-2';
            toast.innerHTML = `<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> ${msg}`;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 6000);
        }

        // Poll every 3 seconds for instant real-time UI updates
        setInterval(checkLiveStatus, 3000);
    });
</script>
@endsection
