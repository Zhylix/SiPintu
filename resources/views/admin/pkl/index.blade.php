@extends('layouts.app', ['headerTitle' => 'Manajemen Status PKL (Admin & DUDI)'])

@section('content')
<div class="space-y-6">
    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-800 pb-4">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-bold text-white">Manajemen Status PKL Seluruh Siswa</h3>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                        🛡 Real-time Admin Override
                    </span>
                </div>
                <p class="text-xs text-slate-400 mt-0.5">Hanya Administrator dan Mitra DUDI yang memiliki hak akses mengubah status ini.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950 text-slate-400 uppercase font-bold text-[10px] border-b border-slate-800">
                    <tr>
                        <th class="px-4 py-3">Nama Siswa</th>
                        <th class="px-4 py-3">Perusahaan / DUDI</th>
                        <th class="px-4 py-3">Status PKL</th>
                        <th class="px-4 py-3">Catatan Evaluasi</th>
                        <th class="px-4 py-3">Terakhir Diperbarui</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60 text-slate-300">
                    @foreach($statuses as $item)
                        <tr class="hover:bg-slate-800/40 transition-colors" id="row-status-{{ $item->id }}">
                            <td class="px-4 py-3 font-semibold text-white">
                                <div>{{ $item->student?->name ?? 'Siswa #'.$item->student_id }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $item->student?->email }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-200">{{ $item->company_name }}</div>
                                <div class="text-[10px] text-indigo-400">{{ $item->division }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span id="status-badge-{{ $item->id }}" class="px-2.5 py-1 rounded-full text-[10px] font-bold border transition-all duration-300 {{ $item->badge_color }}">
                                    {{ $item->status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-400 italic text-[11px]" id="status-notes-{{ $item->id }}">
                                {{ $item->notes ?? '-' }}
                            </td>
                            <td class="px-4 py-3 font-mono text-[10px] text-slate-400 whitespace-nowrap" id="status-updater-{{ $item->id }}">
                                {{ $item->updater?->name ?? 'System' }} ({{ $item->updated_at->diffForHumans() }})
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <button onclick="openAdminModal({{ $item->id }}, '{{ addslashes($item->student?->name ?? 'Siswa') }}', '{{ $item->status }}', '{{ addslashes($item->company_name) }}', '{{ addslashes($item->division) }}', '{{ addslashes($item->notes ?? '') }}')"
                                        class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-600 hover:bg-emerald-500 text-white transition shadow-sm flex items-center gap-1 inline-flex ml-auto">
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
            {{ $statuses->links() }}
        </div>
    </div>
</div>

<!-- Modal Edit Status (Admin) -->
<div id="adminModal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 w-full max-w-md space-y-4 shadow-2xl animate-in fade-in zoom-in duration-200">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-sm font-bold text-white flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                Ubah Status PKL Siswa (Admin Override)
            </h3>
            <button onclick="closeAdminModal()" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form id="adminUpdateForm" onsubmit="handleAdminSubmit(event)">
            @csrf
            <input type="hidden" id="admin_modal_id">
            
            <div class="space-y-4 text-xs">
                <div>
                    <label class="text-slate-400 block mb-1 font-semibold">Nama Siswa</label>
                    <input type="text" id="admin_modal_student" disabled class="w-full bg-slate-950 border border-slate-800 text-slate-300 rounded-xl px-3 py-2 text-xs font-semibold">
                </div>

                <div>
                    <label class="text-slate-400 block mb-1 font-semibold">Perusahaan / DUDI</label>
                    <input type="text" id="admin_modal_company" class="w-full bg-slate-950 border border-slate-700 text-white rounded-xl px-3 py-2 text-xs">
                </div>

                <div>
                    <label class="text-slate-400 block mb-1 font-semibold">Divisi / Dept</label>
                    <input type="text" id="admin_modal_division" class="w-full bg-slate-950 border border-slate-700 text-white rounded-xl px-3 py-2 text-xs">
                </div>

                <div>
                    <label class="text-slate-400 block mb-1 font-semibold">Pilih Status PKL Baru</label>
                    <select id="admin_modal_status" class="w-full bg-slate-950 border border-slate-700 text-white rounded-xl px-3 py-2 text-xs font-medium focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                        @foreach($allowedStatuses as $key => $label)
                            <option value="{{ $key }}">{{ $key }} ({{ $label }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-slate-400 block mb-1 font-semibold">Catatan Evaluasi / Keterangan</label>
                    <textarea id="admin_modal_notes" rows="3" placeholder="Tambahkan catatan evaluasi..." class="w-full bg-slate-950 border border-slate-700 text-white rounded-xl p-3 text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none"></textarea>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800 mt-4">
                <button type="button" onclick="closeAdminModal()" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 transition">Batal</button>
                <button type="submit" id="btn-save-admin" class="px-4 py-2 rounded-xl text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-500 transition shadow-lg shadow-emerald-600/20">
                    Simpan Perubahan Real-time
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openAdminModal(id, studentName, status, company, division, notes) {
        document.getElementById('admin_modal_id').value = id;
        document.getElementById('admin_modal_student').value = studentName;
        document.getElementById('admin_modal_status').value = status;
        document.getElementById('admin_modal_company').value = company;
        document.getElementById('admin_modal_division').value = division;
        document.getElementById('admin_modal_notes').value = notes;
        document.getElementById('adminModal').classList.remove('hidden');
    }

    function closeAdminModal() {
        document.getElementById('adminModal').classList.add('hidden');
    }

    async function handleAdminSubmit(e) {
        e.preventDefault();
        const id = document.getElementById('admin_modal_id').value;
        const status = document.getElementById('admin_modal_status').value;
        const company_name = document.getElementById('admin_modal_company').value;
        const division = document.getElementById('admin_modal_division').value;
        const notes = document.getElementById('admin_modal_notes').value;
        const btn = document.getElementById('btn-save-admin');

        btn.disabled = true;
        btn.innerText = 'Menyimpan...';

        try {
            const response = await fetch(`/api/pkl-status/${id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status, company_name, division, notes })
            });

            const data = await response.json();

            if (data.success) {
                const badge = document.getElementById(`status-badge-${id}`);
                const notesElem = document.getElementById(`status-notes-${id}`);
                const updaterElem = document.getElementById(`status-updater-${id}`);

                if (badge) {
                    badge.innerText = data.data.status;
                    badge.className = `px-2.5 py-1 rounded-full text-[10px] font-bold border transition-all duration-300 ${data.data.badge_color}`;
                }
                if (notesElem) notesElem.innerText = data.data.notes || '-';
                if (updaterElem) updaterElem.innerText = `${data.data.updated_by} (baru saja)`;

                showToast(data.message);
                closeAdminModal();
            } else {
                showToast(data.message || 'Gagal mengubah status', 'error');
            }
        } catch (err) {
            console.error(err);
            showToast('Terjadi kesalahan koneksi', 'error');
        } finally {
            btn.disabled = false;
            btn.innerText = 'Simpan Perubahan Real-time';
        }
    }

    function showToast(msg, type = 'success') {
        const toast = document.createElement('div');
        const color = type === 'success' ? 'bg-emerald-600' : 'bg-rose-600';
        toast.className = `fixed bottom-5 right-5 ${color} text-white font-semibold text-xs px-4 py-3 rounded-xl shadow-2xl z-50 animate-bounce`;
        toast.innerText = msg;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }
</script>
@endsection
