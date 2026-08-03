@extends('layouts.app', ['headerTitle' => 'Kelola Pengguna'])

@section('content')
<div class="space-y-6">
    <!-- Header & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
        <div>
            <h2 class="text-xl font-black text-emerald-950">Manajemen Pengguna Gateway</h2>
            <p class="text-xs text-slate-600 font-medium mt-1">Kelola akun Guru, DUDI, Siswa (SIJUNA), dan Admin Gateway</p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.users.create') }}" class="px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-700/20 flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Buat Akun Guru / DUDI</span>
            </a>
        </div>
    </div>

    <!-- Filter & Search Bar -->
    <div class="p-4 rounded-2xl bg-white border border-slate-200 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap items-center gap-3 w-full">
            <div class="flex items-center space-x-2">
                <span class="text-xs text-slate-600 font-bold">Filter Role:</span>
                <select name="type" onchange="this.form.submit()" class="px-3 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-900 font-semibold focus:outline-none focus:border-emerald-600">
                    <option value="">Semua Role (All Users)</option>
                    <option value="student" {{ request('type', request('role')) === 'student' ? 'selected' : '' }}>Siswa (SIJUNA)</option>
                    <option value="teacher" {{ request('type', request('role')) === 'teacher' ? 'selected' : '' }}>Guru</option>
                    <option value="dudi" {{ request('type', request('role')) === 'dudi' ? 'selected' : '' }}>DUDI / Industri</option>
                    <option value="admin" {{ request('type', request('role')) === 'admin' ? 'selected' : '' }}>Admin Gateway</option>
                </select>
            </div>

            <div class="flex-1 min-w-[240px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari Berdasarkan Nama, Email, Username, atau External ID..."
                    class="w-full px-4 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-900 font-semibold placeholder-slate-400 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all">
            </div>

            <button type="submit" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-200 transition-all">
                Cari & Filter
            </button>
        </form>
    </div>

    <!-- Mobile Card View (No horizontal scrolling needed on small screens) -->
    <div class="block md:hidden space-y-4">
        @forelse($users as $user)
            <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm space-y-4">
                <!-- User Basic Info Header -->
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center space-x-3 min-w-0">
                        <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center justify-center font-extrabold text-sm shrink-0">
                            {{ $user->initials() }}
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-bold text-slate-900 text-sm truncate">{{ $user->name }}</h3>
                            <p class="text-slate-600 text-xs font-medium truncate">{{ $user->email }}</p>
                        </div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider shrink-0
                        {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-800 border border-purple-300' : '' }}
                        {{ $user->role === 'teacher' ? 'bg-blue-100 text-blue-800 border border-blue-300' : '' }}
                        {{ $user->role === 'dudi' ? 'bg-amber-100 text-amber-800 border border-amber-300' : '' }}
                        {{ $user->role === 'student' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : '' }}">
                        {{ $user->getUserTypeName() }}
                    </span>
                </div>

                <!-- User Details Grid -->
                <div class="grid grid-cols-2 gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100 text-xs">
                    <div>
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">NIS / External ID</span>
                        <span class="font-mono font-bold text-emerald-800 text-xs break-all">
                            {{ $user->external_id ?: ($user->username ?: '-') }}
                        </span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Kontak / Phone</span>
                        <span class="text-slate-700 font-semibold text-xs">
                            {{ $user->phone ?: '-' }}
                        </span>
                    </div>
                </div>

                <!-- Footer: Status & Actions -->
                <div class="flex items-center justify-between pt-2 border-t border-slate-100 gap-3">
                    <div>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase {{ $user->status === 'active' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-rose-100 text-rose-800 border border-rose-300' }}">
                            {{ $user->status }}
                        </span>
                    </div>

                    <div class="flex items-center space-x-2">
                        <a href="{{ route('admin.users.edit', $user) }}" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition-all">
                            Edit
                        </a>
                        @if(!$user->isAdmin())
                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Hapus akun pengguna {{ $user->name }}?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="px-3.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-bold transition-all">
                                    Hapus
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="p-8 text-center bg-white rounded-2xl border border-slate-200 text-slate-500 font-medium text-xs">
                Tidak ada data pengguna yang ditemukan.
            </div>
        @endforelse

        @if($users->hasPages())
            <div class="bg-white rounded-2xl border border-slate-200 p-4">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    <!-- Desktop Users Table -->
    <div class="hidden md:block bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-emerald-50 text-emerald-900 uppercase font-black text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">Pengguna</th>
                        <th class="px-6 py-4">Role / Type</th>
                        <th class="px-6 py-4">Identifier / External ID</th>
                        <th class="px-6 py-4">Kontak</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 font-sans text-slate-700 bg-white">
                    @forelse($users as $user)
                        <tr class="hover:bg-emerald-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-3">
                                    <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center justify-center font-extrabold text-xs">
                                        {{ $user->initials() }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-900 text-sm">{{ $user->name }}</div>
                                        <div class="text-slate-600 text-xs font-medium">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider
                                    {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-800 border border-purple-300' : '' }}
                                    {{ $user->role === 'teacher' ? 'bg-blue-100 text-blue-800 border border-blue-300' : '' }}
                                    {{ $user->role === 'dudi' ? 'bg-amber-100 text-amber-800 border border-amber-300' : '' }}
                                    {{ $user->role === 'student' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : '' }}">
                                    {{ $user->getUserTypeName() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-mono font-bold text-emerald-800">
                                {{ $user->external_id ?: ($user->username ?: '-') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-slate-600 font-medium">
                                {{ $user->phone ?: '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase {{ $user->status === 'active' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-rose-100 text-rose-800 border border-rose-300' }}">
                                    {{ $user->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                                <a href="{{ route('admin.users.edit', $user) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 rounded-lg font-bold transition-colors">
                                    Edit
                                </a>
                                @if(!$user->isAdmin())
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Hapus akun pengguna {{ $user->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg font-bold transition-colors">
                                            Hapus
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500 font-medium">
                                Tidak ada data pengguna yang ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-200">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection
