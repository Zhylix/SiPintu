@extends('layouts.app', ['headerTitle' => 'Daftar Peserta Magang & Kelola Status PKL'])

@section('content')
<div class="space-y-6">
    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-4">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-bold text-white">Peserta Magang & Real-Time Status PKL</h3>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-500/10 text-amber-400 border border-amber-500/20">
                        🔑 Wewenang DUDI & Admin
                    </span>
                </div>
                <p class="text-xs text-slate-400 mt-0.5">Ubah dan perbarui status Praktik Kerja Lapangan siswa secara real-time</p>
            </div>
            <div class="text-xs text-slate-400 bg-slate-950 px-3 py-1.5 rounded-lg border border-slate-800">
                ⚡ Mode: <span class="text-emerald-400 font-bold">Real-Time Sync Active</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950 text-slate-400 uppercase font-bold text-[10px] border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Nama Siswa</th>
                        <th class="px-4 py-3">NISN / ID</th>
                        <th class="px-4 py-3">Status Saat Ini</th>
                        <th class="px-4 py-3">Catatan DUDI</th>
                        <th class="px-4 py-3 text-right">Aksi Ubah Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-300">
                    @foreach($interns as $intern)
                        @php
                            $statusRecord = $intern->pklStatus ?? \App\Models\PklStatus::firstOrCreate(
                                ['student_id' => $intern->id],
                                ['status' => 'Aktif Berjalan', 'company_name' => 'PT Telkom Indonesia']
                            );
                        @endphp
                        <tr class="hover:bg-slate-800/40 transition-colors" id="row-student-{{ $intern->id }}">
                            <td class="px-4 py-3 font-semibold text-white">
                                <div>{{ $intern->name }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $intern->email }}</div>
                            </td>
                            <td class="px-4 py-3 font-mono text-amber-300">{{ $intern->external_id ?? '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span id="status-badge-{{ $statusRecord->id }}" class="px-2.5 py-1 rounded-full text-[10px] font-bold border transition-all duration-300 {{ $statusRecord->badge_color }}">
                                    {{ $statusRecord->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span id="status-notes-{{ $statusRecord->id }}" class="text-slate-400 italic text-[11px]">
                                    {{ $statusRecord->notes ?? '-' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button onclick="openStatusModal({{ $statusRecord->id }}, '{{ $intern->name }}', '{{ $statusRecord->status }}', '{{ addslashes($statusRecord->notes ?? '') }}')" 
                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600 hover:bg-indigo-500 text-white transition shadow-sm hover:shadow-indigo-500/20 flex items-center gap-1 inline-flex ml-auto">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Ubah Status
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="pt-4">
            {{ $interns->links() }}
        </div>
    </div>
</div>

<!-- Modal Update Status PKL (Khusus DUDI & Admin) -->
<div id="statusModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-md space-y-4 shadow-2xl animate-in fade-in zoom-in duration-200">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                Ubah Status PKL Siswa
            </h3>
            <button onclick="closeStatusModal()" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form id="updateStatusForm" onsubmit="handleStatusSubmit(event)">
            @csrf
            <input type="hidden" id="modal_status_id">
            
            <div class="space-y-4 text-xs">
                <div>
                    <label class="text-slate-400 block mb-1 font-semibold">Nama Siswa</label>
                    <input type="text" id="modal_student_name" disabled class="w-full bg-slate-950 border border-slate-800 text-slate-300 rounded-xl px-3 py-2 text-xs font-semibold">
                </div>

                <div>
                    <label class="text-slate-400 block mb-1 font-semibold">Pilih Status PKL Baru</label>
                    <select id="modal_status_select" class="w-full bg-slate-950 border border-slate-700 text-white rounded-xl px-3 py-2 text-xs font-medium focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        @foreach($allowedStatuses as $key => $label)
                            <option value="{{ $key }}">{{ $key }} ({{ $label }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-slate-400 block mb-1 font-semibold">Catatan Tambahan (DUDI / Admin)</label>
                    <textarea id="modal_status_notes" rows="3" placeholder="Masukkan instruksi atau catatan evaluasi..." class="w-full bg-slate-950 border border-slate-700 text-white rounded-xl p-3 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none"></textarea>
                </div>

                <div class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl text-[11px] text-amber-300">
                    ℹ status ini akan disinkronkan secara <strong>real-time</strong> ke portal Siswa, Guru, dan Admin.
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800 mt-4">
                <button type="button" onclick="closeStatusModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 transition">Batal</button>
                <button type="submit" id="btn-save-status" class="px-4 py-2 rounded-xl text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-500 transition shadow-lg shadow-emerald-600/20 flex items-center gap-1.5">
                    <span>Simpan Status PKL</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openStatusModal(id, studentName, currentStatus, notes) {
        document.getElementById('modal_status_id').value = id;
        document.getElementById('modal_student_name').value = studentName;
        document.getElementById('modal_status_select').value = currentStatus;
        document.getElementById('modal_status_notes').value = notes;
        document.getElementById('statusModal').classList.remove('hidden');
    }

    function closeStatusModal() {
        document.getElementById('statusModal').classList.add('hidden');
    }

    async function handleStatusSubmit(e) {
        e.preventDefault();
        const id = document.getElementById('modal_status_id').value;
        const status = document.getElementById('modal_status_select').value;
        const notes = document.getElementById('modal_status_notes').value;
        const saveBtn = document.getElementById('btn-save-status');

        saveBtn.disabled = true;
        saveBtn.innerText = 'Menyimpan...';

        try {
            const response = await fetch(`/api/pkl-status/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status, notes })
            });

            const data = await response.json();

            if (data.success) {
                // Update badge & notes UI in real-time
                const badge = document.getElementById(`status-badge-${id}`);
                const notesElem = document.getElementById(`status-notes-${id}`);
                
                if (badge) {
                    badge.innerText = data.data.status;
                    badge.className = `px-2.5 py-1 rounded-full text-[10px] font-bold border transition-all duration-300 ${data.data.badge_color}`;
                }
                if (notesElem) {
                    notesElem.innerText = data.data.notes || '-';
                }

                showToast(data.message, 'success');
                closeStatusModal();
            } else {
                showToast(data.message || 'Gagal mengubah status', 'error');
            }
        } catch (err) {
            console.error('Error updating status:', err);
            showToast('Terjadi kesalahan jaringan/server.', 'error');
        } finally {
            saveBtn.disabled = false;
            saveBtn.innerText = 'Simpan Status PKL';
        }
    }

    function showToast(msg, type = 'success') {
        const toast = document.createElement('div');
        const color = type === 'success' ? 'bg-emerald-600' : 'bg-rose-600';
        toast.className = `fixed bottom-5 right-5 ${color} text-white font-semibold text-xs px-4 py-3 rounded-xl shadow-2xl z-50 animate-bounce flex items-center gap-2`;
        toast.innerHTML = msg;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }
</script>
@endsection
