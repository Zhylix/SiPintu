@extends('layouts.app', ['headerTitle' => 'Status Pengiriman WhatsApp'])

@section('content')
<div class="space-y-6" x-data="{ phoneModalOpen: false, selectedUser: null, phoneInput: '' }">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 font-semibold mb-1">
                <a href="{{ route('admin.announcements.index') }}" class="hover:underline">Pengumuman</a> &rarr; <span>Status Log WhatsApp</span>
            </div>
            <h2 class="text-xl font-black text-emerald-950 tracking-tight">Status Pengiriman WA: {{ $announcement->title }}</h2>
            <p class="text-xs text-slate-600 font-medium mt-1">Status dan riwayat pengiriman pesan ke nomor WhatsApp penerima. Anda dapat mengklik nomor untuk mengubahnya.</p>
        </div>
        <div class="flex items-center gap-3">
            <form action="{{ route('admin.announcements.send-whatsapp', $announcement) }}" method="POST" onsubmit="return confirm('Kirim ulang pengumuman via WhatsApp ke penerima?')">
                @csrf
                <button type="submit" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-700/20">
                    &circlearrowright; Kirim Ulang WA
                </button>
            </form>
            <a href="{{ route('admin.announcements.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 text-xs font-bold rounded-xl transition-all">
                &larr; Kembali
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs font-bold shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    <!-- Logs Table -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-700 uppercase font-black text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-4">Penerima</th>
                        <th class="px-5 py-4">Nomor HP / WhatsApp (Klik untuk Ganti)</th>
                        <th class="px-5 py-4">Status Pengiriman</th>
                        <th class="px-5 py-4">Keterangan / Error Log</th>
                        <th class="px-5 py-4">Waktu Terkirim</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700 bg-white">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="font-bold text-slate-900">{{ $log->user?->name ?? 'Pengguna Unknown' }}</div>
                                <div class="text-[10px] text-slate-500 font-mono">{{ $log->user?->email ?? '-' }}</div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if($log->user)
                                    <button type="button"
                                        @click="selectedUser = { id: {{ $log->user->id }}, name: '{{ addslashes($log->user->name) }}', phone: '{{ $log->user->phone }}' }; phoneInput = '{{ $log->user->phone }}'; phoneModalOpen = true"
                                        class="inline-flex items-center space-x-1.5 px-3 py-1.5 rounded-xl bg-slate-50 hover:bg-emerald-100/80 border border-slate-200 hover:border-emerald-300 transition-all font-mono text-slate-800 font-bold text-xs group cursor-pointer"
                                        title="Klik untuk mengganti nomor WhatsApp user ini">
                                        <span>{{ $log->phone_number !== '-' ? $log->phone_number : ($log->user->phone ?: 'Belum Ada No. WA') }}</span>
                                        <span class="text-xs opacity-60 group-hover:opacity-100">✏️</span>
                                    </button>
                                @else
                                    <span class="font-mono text-slate-800 font-bold">{{ $log->phone_number }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if($log->status === 'sent')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300 uppercase">
                                        ✓ Berhasil Terkirim
                                    </span>
                                @elseif($log->status === 'pending')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300 uppercase">
                                        ⏳ Menunggu Queue
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800 border border-rose-300 uppercase">
                                        ✕ Gagal Terkirim
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-xs font-sans max-w-xs">
                                @if($log->error_message)
                                    <span class="text-rose-600 font-semibold">{{ $log->error_message }}</span>
                                @else
                                    <span class="text-slate-400 font-medium">-</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-slate-600 font-medium text-[11px]">
                                {{ $log->sent_at?->format('d M Y H:i:s') ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-12 text-center text-slate-500 font-medium">
                                Belum ada riwayat pengiriman WhatsApp untuk pengumuman ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- Quick Modal Ganti Nomor WhatsApp -->
    <div x-show="phoneModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="phoneModalOpen" @click="phoneModalOpen = false" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="phoneModalOpen" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl border border-slate-200">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div class="flex items-center space-x-2">
                        <span class="text-xl">📱</span>
                        <h3 class="text-base font-black text-slate-900">Ganti Nomor WhatsApp Penerima</h3>
                    </div>
                    <button @click="phoneModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
                </div>

                <template x-if="selectedUser">
                    <form :action="'{{ url('/admin/users') }}/' + selectedUser.id + '/update-phone'" method="POST" class="mt-4 space-y-4">
                        @csrf
                        @method('PATCH')

                        <div class="p-3 bg-emerald-50 rounded-xl border border-emerald-200">
                            <p class="text-xs text-slate-500 font-medium">Penerima / User:</p>
                            <p class="text-sm font-black text-emerald-950" x-text="selectedUser.name"></p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Nomor WhatsApp Baru:</label>
                            <input type="text" name="phone" x-model="phoneInput" placeholder="Contoh: 08123456789 atau 628123456789"
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-sm font-mono font-bold text-slate-900 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all">
                            <p class="text-[11px] text-slate-500 mt-1.5 font-medium">
                                *Setelah mengubah nomor, Anda dapat mengklik tombol "Kirim Ulang WA" untuk mencoba pengiriman kembali.
                            </p>
                        </div>

                        <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-100">
                            <button type="button" @click="phoneModalOpen = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all border border-slate-200">
                                Batal
                            </button>
                            <button type="submit" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-700/20">
                                Simpan Nomor Baru
                            </button>
                        </div>
                    </form>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection
