@extends('layouts.app', ['headerTitle' => 'Kelola Pengguna'])

@section('content')
<div class="space-y-6">
    <!-- Header & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white">Manajemen Pengguna Gateway</h2>
            <p class="text-xs text-slate-400 mt-1">Kelola akun Guru, DUDI, Siswa (SIJUNA), dan Admin Gateway</p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.users.create') }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-all shadow-lg shadow-indigo-600/30 flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>+ Buat Akun Guru / DUDI</span>
            </a>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="p-4 rounded-2xl bg-slate-900 border border-slate-800 flex flex-col md:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-center gap-3 w-full">
            <div class="flex items-center space-x-2">
                <span class="text-xs text-slate-400 font-medium">Filter Role:</span>
                <select name="type" onchange="this.form.submit()" class="px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none">
                    <option value="">Semua Role (All Users)</option>
                    <option value="student" {{ request('type') === 'student' ? 'selected' : '' }}>Siswa (SIJUNA)</option>
                    <option value="teacher" {{ request('type') === 'teacher' ? 'selected' : '' }}>Guru</option>
                    <option value="dudi" {{ request('type') === 'dudi' ? 'selected' : '' }}>DUDI / Industri</option>
                    <option value="admin" {{ request('type') === 'admin' ? 'selected' : '' }}>Admin Gateway</option>
                </select>
            </div>

            <div class="flex-1 min-w-[240px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Berdasarkan Nama, Email, Username, atau External ID..."
                    class="w-full px-4 py-2 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
            </div>

            <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold rounded-xl transition-all">
                Cari & Filter
            </button>
        </form>
    </div>

    <!-- Users Table -->
    <div class="bg-slate-900 rounded-2xl border border-slate-800 overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950 text-slate-400 uppercase font-bold text-[10px] border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Pengguna</th>
                        <th class="px-6 py-4">Role / Type</th>
                        <th class="px-6 py-4">Identifier / External ID</th>
                        <th class="px-6 py-4">Kontak</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-full bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 flex items-center justify-center font-bold text-xs">
                                        {{ $user->initials() }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-white text-sm">{{ $user->name }}</div>
                                        <div class="text-slate-400 text-xs">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider
                                    {{ $user->user_type === 'admin' ? 'bg-purple-500/10 text-purple-400 border border-purple-500/20' : '' }}
                                    {{ $user->user_type === 'teacher' ? 'bg-indigo-500/10 text-indigo-400 border border-indigo-500/20' : '' }}
                                    {{ $user->user_type === 'dudi' ? 'bg-amber-500/10 text-amber-400 border border-amber-500/20' : '' }}
                                    {{ $user->user_type === 'student' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : '' }}">
                                    {{ $user->user_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-mono text-slate-300">
                                {{ $user->external_id ?: ($user->username ?: '-') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-slate-300">
                                {{ $user->phone ?: '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $user->status === 'active' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' }}">
                                    {{ $user->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                                <a href="{{ route('admin.users.edit', $user) }}" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-indigo-300 rounded-lg font-semibold transition-colors">
                                    Edit
                                </a>
                                @if(!$user->isAdmin())
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Hapus akun pengguna {{ $user->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 rounded-lg font-semibold transition-colors">
                                            Hapus
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                                Tidak ada data pengguna yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-800">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection
