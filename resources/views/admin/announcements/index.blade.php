@extends('layouts.app', ['headerTitle' => 'Kelola Pengumuman Sistem'])

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
        <div>
            <h2 class="text-xl font-black text-emerald-950 tracking-tight">Kelola Pengumuman Gateway</h2>
            <p class="text-xs text-slate-600 font-medium mt-1">Pengumuman yang dibuat di sini akan tampil secara eksklusif bagi pengguna dan dapat dikirim via WhatsApp.</p>
        </div>
        <a href="{{ route('admin.announcements.create') }}" class="inline-flex items-center justify-center px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-700/20">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Buat Pengumuman Baru
        </a>
    </div>

    <!-- Alert Success / Info -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs font-bold flex items-center justify-between shadow-sm">
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-xs font-bold flex items-center justify-between shadow-sm">
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- WhatsApp Bot Account Status & Live QR Code Widget -->
    <div x-data="{
        qrModalOpen: false,
        online: {{ isset($botStatus['online']) && $botStatus['online'] ? 'true' : 'false' }},
        connection: '{{ $botStatus['data']['connection'] ?? 'close' }}',
        botPhone: '{{ $botStatus['data']['bot_phone'] ?? '' }}',
        qrCode: '{{ $botStatus['data']['qr_code'] ?? '' }}',
        fetchStatus() {
            fetch('{{ route('admin.announcements.bot-status') }}')
                .then(res => res.json())
                .then(data => {
                    this.online = data.online || false;
                    if (data.data) {
                        this.connection = data.data.connection || 'close';
                        this.botPhone = data.data.bot_phone || '';
                        this.qrCode = data.data.qr_code || '';
                    }
                }).catch(() => { this.online = false; });
        },
        init() {
            this.fetchStatus();
            setInterval(() => this.fetchStatus(), 3000);
        }
    }" x-init="init()" class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-full bg-emerald-100 border border-emerald-300 flex items-center justify-center text-xl shrink-0">
                    🤖
                </div>
                <div>
                    <h3 class="font-extrabold text-slate-900 text-sm flex items-center gap-2">
                        <span>Status Server Bot WhatsApp Sending</span>
                        <template x-if="online && connection === 'open'">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300 uppercase">
                                🟢 Terhubung
                            </span>
                        </template>
                        <template x-if="online && connection === 'connecting'">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 border border-amber-300 uppercase">
                                🟡 Menghubungkan...
                            </span>
                        </template>
                        <template x-if="!online || connection === 'close'">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800 border border-rose-300 uppercase">
                                🔴 Belum Terhubung
                            </span>
                        </template>
                    </h3>
                    <p class="text-xs text-slate-600 font-medium mt-0.5">
                        <template x-if="online && connection === 'open' && botPhone">
                            <span>Nomor Bot Pengirim Aktif: <strong class="font-mono text-emerald-800" x-text="'+' + botPhone"></strong></span>
                        </template>
                        <template x-if="!online || connection !== 'open'">
                            <span>Sistem Baileys belum terhubung ke WhatsApp HP. Silakan scan QR code di bawah.</span>
                        </template>
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <template x-if="!online || connection !== 'open'">
                    <button type="button" @click="qrModalOpen = true" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-700/20 flex items-center space-x-1.5">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m0 14v1m8-8h-1M5 12H4m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z"></path></svg>
                        <span>QR Code Modal</span>
                    </button>
                </template>

                <template x-if="online && connection === 'open'">
                    <form action="{{ route('admin.announcements.logout-bot') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin MENGGANTI NOMOR WHATSAPP BOT?\n\nSesi WhatsApp bot yang sedang terhubung akan di-logout dan QR Code baru akan dibuat untuk di-scan dengan nomor lain.')">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-black rounded-xl transition-all shadow-sm flex items-center space-x-1.5">
                            <span>Logout</span>
                        </button>
                    </form>
                </template>

                <template x-if="!online">
                    <span class="text-xs text-rose-600 font-semibold italic">Tidak Terhubung.</span>
                </template>
            </div>
        </div>

        <!-- Live QR Code Card Display if disconnected / waiting scan -->
        <template x-if="!online || connection !== 'open'">
            <div class="p-5 bg-emerald-50/60 border border-emerald-200 rounded-2xl shadow-inner">
                <template x-if="qrCode">
                    <div class="flex flex-col md:flex-row items-center gap-6">
                        <div class="relative group shrink-0">
                            <img :src="qrCode" alt="QR Code WhatsApp Bot" class="w-48 h-48 bg-white p-2.5 border-2 border-emerald-400 rounded-2xl shadow-md">
                            <div class="absolute -bottom-2 -right-2 bg-emerald-700 text-white text-[9px] font-black px-2 py-0.5 rounded-full shadow">
                                LIVE QR
                            </div>
                        </div>
                        <div class="space-y-2 text-center md:text-left flex-1">
                            <div class="inline-flex items-center space-x-2 bg-emerald-100 text-emerald-900 px-3 py-1 rounded-full text-xs font-black">
                                <span>Scan QR Code</span>
                            </div>
                            <h4 class="text-sm font-black text-slate-900">Langkah Menghubungkan Nomor WhatsApp Pengirim:</h4>
                            <ol class="text-xs text-slate-700 font-medium space-y-1 list-decimal list-inside">
                                <li>Buka aplikasi <strong>WhatsApp</strong> di Smartphone Anda.</li>
                                <li>Buka menu <strong>Pengaturan / Setelan</strong> &rarr; pilih <strong>Perangkat Tertaut (Linked Devices)</strong>.</li>
                                <li>Klik <strong>Tautkan Perangkat (Link a Device)</strong>.</li>
                                <li>Arahkan kamera HP Anda ke gambar <strong>QR Code</strong> di samping.</li>
                            </ol>
                            <p class="text-[11px] text-emerald-800 font-bold flex items-center gap-1 mt-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-600 animate-ping"></span>
                                <span>QR Code diperbarui secara otomatis secara real-time setiap 3 detik.</span>
                            </p>
                        </div>
                    </div>
                </template>
                <template x-if="!qrCode">
                    <div class="flex flex-col sm:flex-row items-center gap-4 text-center sm:text-left p-4 bg-white/80 rounded-xl border border-emerald-100">
                        <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center text-2xl shrink-0 animate-pulse">
                            🔄
                        </div>
                        <div>
                            <h4 class="text-xs font-black text-slate-900">Menyiapkan QR Code WhatsApp...</h4>
                            <p class="text-[11px] text-slate-600 font-medium mt-0.5">
                                <template x-if="!online">
                                    <span>Pastikan server Node.js WhatsApp Bot (port 3000) sudah aktif. Menghubungkan ulang...</span>
                                </template>
                                <template x-if="online">
                                    <span>Memuat QR Code, Mohon tunggu beberapa detik...</span>
                                </template>
                            </p>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <!-- Live Modal Popup QR Code -->
        <div x-show="qrModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="qrModalOpen" @click="qrModalOpen = false" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="qrModalOpen" x-transition:enter="ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-center align-middle transition-all transform bg-white shadow-2xl rounded-3xl border border-slate-200">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center space-x-2">
                            <span class="text-xl">📱</span>
                            <h3 class="text-base font-black text-slate-900">QR Code WhatsApp Bot</h3>
                        </div>
                        <button @click="qrModalOpen = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">&times;</button>
                    </div>

                    <div class="mt-4 space-y-4">
                        <template x-if="qrCode">
                            <div class="flex flex-col items-center justify-center p-4 bg-emerald-50/50 rounded-2xl border border-emerald-200">
                                <img :src="qrCode" alt="QR Code WhatsApp Bot" class="w-64 h-64 bg-white p-3 border-2 border-emerald-400 rounded-2xl shadow-lg">
                                <p class="text-xs text-slate-600 font-bold mt-3">Scan QR Code ini menggunakan aplikasi WhatsApp di HP Anda.</p>
                            </div>
                        </template>
                        <template x-if="!qrCode">
                            <div class="p-8 text-center bg-slate-50 rounded-2xl text-slate-500 font-bold text-xs">
                                <span x-text="connection === 'open' ? 'WhatsApp Bot sudah terhubung!' : 'Menyiapkan QR Code...'"></span>
                            </div>
                        </template>

                        <div class="pt-3 border-t border-slate-100 flex justify-end">
                            <button type="button" @click="qrModalOpen = false" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-extrabold rounded-xl transition-all border border-slate-200">
                                Tutup Modal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row gap-4 justify-between items-center">
        <form method="GET" action="{{ route('admin.announcements.index') }}" class="flex flex-col sm:flex-row gap-3 w-full">
            <!-- Search -->
            <div class="relative flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari judul pengumuman..." 
                       class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-semibold placeholder-slate-400 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>

            <!-- Role Filter -->
            <select name="target_role" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 text-xs text-slate-900 font-semibold rounded-xl px-3 py-2 focus:outline-none focus:border-emerald-600">
                <option value="all_roles">Semua Sasaran Role</option>
                <option value="all" {{ request('target_role') === 'all' ? 'selected' : '' }}>Semua Pengguna (All)</option>
                <option value="user" {{ request('target_role') === 'user' ? 'selected' : '' }}>Pengguna Biasa (User)</option>
                <option value="student" {{ request('target_role') === 'student' ? 'selected' : '' }}>Siswa Saja</option>
                <option value="teacher" {{ request('target_role') === 'teacher' ? 'selected' : '' }}>Guru Saja</option>
                <option value="dudi" {{ request('target_role') === 'dudi' ? 'selected' : '' }}>DUDI Saja</option>
            </select>

            <!-- Type Filter -->
            <select name="type" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 text-xs text-slate-900 font-semibold rounded-xl px-3 py-2 focus:outline-none focus:border-emerald-600">
                <option value="">Semua Tipe Alert</option>
                <option value="info" {{ request('type') === 'info' ? 'selected' : '' }}>Info (Biru)</option>
                <option value="warning" {{ request('type') === 'warning' ? 'selected' : '' }}>Peringatan (Kuning)</option>
                <option value="danger" {{ request('type') === 'danger' ? 'selected' : '' }}>Bahaya / Penting (Merah)</option>
                <option value="success" {{ request('type') === 'success' ? 'selected' : '' }}>Sukses (Hijau)</option>
            </select>

            @if(request()->anyFilled(['search', 'target_role', 'type']))
                <a href="{{ route('admin.announcements.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-200 flex items-center justify-center">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Announcement Table -->
    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-emerald-50 text-emerald-900 uppercase font-black text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-4">Judul & Isi</th>
                        <th class="px-5 py-4">Tipe Alert</th>
                        <th class="px-5 py-4">Target Role</th>
                        <th class="px-5 py-4">Status Active</th>
                        <th class="px-5 py-4">Dibuat Oleh</th>
                        <th class="px-5 py-4 text-center">Pengiriman WA</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700 bg-white">
                    @forelse($announcements as $announcement)
                        <tr class="hover:bg-emerald-50/50 transition-colors">
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-900 text-sm">{{ $announcement->title }}</div>
                                <div class="text-slate-600 mt-1 line-clamp-2 text-xs font-sans max-w-xl font-medium">{{ $announcement->content }}</div>
                                <div class="text-[10px] text-emerald-800 font-bold mt-1">Publikasi: {{ $announcement->published_at?->format('d M Y H:i') ?? '-' }}</div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if($announcement->type === 'info')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-blue-100 text-blue-800 border border-blue-300 uppercase">Info</span>
                                @elseif($announcement->type === 'warning')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-amber-100 text-amber-800 border border-amber-300 uppercase">Peringatan</span>
                                @elseif($announcement->type === 'danger')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-rose-100 text-rose-800 border border-rose-300 uppercase">Bahaya / Urgen</span>
                                @elseif($announcement->type === 'success')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300 uppercase">Sukses</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold uppercase {{ $announcement->target_role === 'user' ? 'bg-teal-100 text-teal-800 border border-teal-300' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                    {{ $announcement->target_role === 'user' ? 'Pengguna (User)' : $announcement->target_role }}
                                </span>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <form action="{{ route('admin.announcements.toggle', $announcement) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="px-3 py-1 rounded-full text-[10px] font-extrabold transition-all {{ $announcement->is_active ? 'bg-emerald-100 text-emerald-800 border border-emerald-300 hover:bg-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200 hover:bg-slate-200' }}">
                                        {{ $announcement->is_active ? '● Aktif (Tampil)' : '○ Non-Aktif' }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap font-bold text-slate-900">
                                {{ $announcement->author?->name ?? 'Admin' }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-center space-y-1">
                                <form action="{{ route('admin.announcements.send-whatsapp', $announcement) }}" method="POST" onsubmit="return confirm('Kirim pengumuman ini via WhatsApp ke seluruh user terkait?')">
                                    @csrf
                                    <button type="submit" class="w-full px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold inline-flex items-center justify-center transition-all shadow-sm">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-0.999 3.648 3.742-.981z"/></svg>
                                        Kirim WA
                                    </button>
                                </form>
                                @if($announcement->whats_app_logs_count > 0)
                                    <a href="{{ route('admin.announcements.whatsapp-logs', $announcement) }}" class="inline-block text-[10px] text-emerald-700 hover:underline font-extrabold">
                                        Lihat Status Log ({{ $announcement->whats_app_logs_count }})
                                    </a>
                                @endif
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-right space-x-2">
                                <a href="{{ route('admin.announcements.edit', $announcement) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold inline-block">
                                    Edit
                                </a>
                                <form action="{{ route('admin.announcements.destroy', $announcement) }}" method="POST" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengumuman ini?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-bold">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-500 font-medium">
                                <svg class="w-10 h-10 mx-auto text-slate-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                                Belum ada pengumuman yang dipublikasikan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($announcements->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $announcements->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
