@extends('layouts.app', ['headerTitle' => 'Kelola Pengumuman Sistem'])

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
        <div>
            <h2 class="text-xl font-black text-emerald-950 tracking-tight">Kelola Pengumuman Gateway</h2>
            <p class="text-xs text-slate-600 font-medium mt-1">Pengumuman yang dibuat di sini akan tampil secara eksklusif bagi pengguna sesuai role sasaran.</p>
        </div>
        <a href="{{ route('admin.announcements.create') }}" class="inline-flex items-center justify-center px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-700/20">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            + Buat Pengumuman Baru
        </a>
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
                                <span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold bg-slate-100 text-slate-700 border border-slate-200 uppercase">
                                    {{ $announcement->target_role }}
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
                            <td colspan="6" class="px-5 py-12 text-center text-slate-500 font-medium">
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
