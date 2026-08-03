@extends('layouts.app', ['headerTitle' => 'Kelola Pengguna Gateway'])

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
        <div>
            <h2 class="text-xl font-black text-emerald-950 tracking-tight">Manajemen Pengguna Gateway</h2>
            <p class="text-xs text-slate-600 font-medium mt-1">Kelola data pengguna, peran akses (roles), serta status nomor WhatsApp.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-700/20">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            + Tambah Pengguna Baru
        </a>
    </div>

    <!-- Alert Success / Error -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs font-bold shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-xs font-bold shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    <!-- Filter & Search Bar -->
    <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row gap-4 justify-between items-center">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col sm:flex-row gap-3 w-full">
            <!-- Search Input -->
            <div class="relative flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, email, username, NIS, atau nomor HP..." 
                       class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-semibold placeholder-slate-400 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>

            <!-- Role Filter -->
            <select name="role" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 text-xs text-slate-900 font-semibold rounded-xl px-3 py-2 focus:outline-none focus:border-emerald-600">
                <option value="all">Semua Role</option>
                <option value="student" {{ request('role') === 'student' ? 'selected' : '' }}>Siswa</option>
                <option value="teacher" {{ request('role') === 'teacher' ? 'selected' : '' }}>Guru / Pendidik</option>
                <option value="dudi" {{ request('role') === 'dudi' ? 'selected' : '' }}>Mitra DUDI</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Administrator</option>
            </select>

            <!-- Phone Status Filter -->
            <select name="phone_status" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 text-xs text-slate-900 font-semibold rounded-xl px-3 py-2 focus:outline-none focus:border-emerald-600">
                <option value="all">Semua Status Nomor</option>
                <option value="with_phone" {{ request('phone_status') === 'with_phone' ? 'selected' : '' }}>✓ Ada No. WA</option>
                <option value="without_phone" {{ request('phone_status') === 'without_phone' ? 'selected' : '' }}>⚠️ Belum Ada No. WA</option>
            </select>

            <!-- Status Filter -->
            <select name="status" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 text-xs text-slate-900 font-semibold rounded-xl px-3 py-2 focus:outline-none focus:border-emerald-600">
                <option value="">Semua Status Akun</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Non-Aktif</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Ditangguhkan</option>
            </select>

            @if(request()->anyFilled(['search', 'role', 'phone_status', 'status']))
                <a href="{{ route('admin.users.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-200 flex items-center justify-center">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Mobile View Cards -->
    <div class="block md:hidden space-y-4">
        @forelse($users as $user)
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center justify-center font-extrabold text-xs">
                            {{ $user->initials() }}
                        </div>
                        <div>
                            <div class="font-bold text-slate-900 text-sm">{{ $user->name }}</div>
                            <div class="text-slate-600 text-xs font-medium">{{ $user->email }}</div>
                        </div>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase {{ $user->status === 'active' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-rose-100 text-rose-800 border border-rose-300' }}">
                        {{ $user->status }}
                    </span>
                </div>

                <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-100 text-xs">
                    <div>
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Role</span>
                        <span class="font-bold text-slate-800 capitalize">{{ $user->getUserTypeName() }}</span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">NIS / ID</span>
                        <span class="font-mono font-bold text-emerald-800 text-xs break-all">
                            {{ $user->external_id ?: ($user->username ?: '-') }}
                        </span>
                    </div>
                    <div>
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">No. WhatsApp</span>
                        <span class="font-mono font-bold text-xs {{ $user->phone ? 'text-emerald-700' : 'text-rose-500 italic' }}">
                            {{ $user->phone ?: 'Belum diisi' }}
                        </span>
                    </div>
                </div>

                <!-- Footer: Actions -->
                <div class="flex items-center justify-end pt-2 border-t border-slate-100 space-x-2">
                    <a href="{{ route('admin.users.edit', $user) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition-all">
                        Edit
                    </a>
                    @if(!$user->isAdmin())
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Hapus akun pengguna {{ $user->name }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-bold transition-all">
                                Hapus
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-8 text-center bg-white rounded-2xl border border-slate-200 text-slate-500 font-medium text-xs">
                Tidak ada pengguna yang ditemukan.
            </div>
        @endforelse

        @if($users->hasPages())
            <div class="p-4 bg-white rounded-2xl border border-slate-200">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    <!-- Desktop Table View -->
    <div class="hidden md:block bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-emerald-50 text-emerald-900 uppercase font-black text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">Pengguna</th>
                        <th class="px-6 py-4">Role / Type</th>
                        <th class="px-6 py-4">Identifier / NIS</th>
                        <th class="px-6 py-4">Nomor WhatsApp</th>
                        <th class="px-6 py-4">Status Akun</th>
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
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase
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
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($user->phone && $user->phone !== '0')
                                    <div class="inline-flex items-center space-x-1.5 px-2.5 py-1">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                        <span class="font-mono font-bold text-slate-800 text-xs">{{ $user->phone }}</span>
                                    </div>
                                @else
                                    <div class="inline-flex items-center space-x-1.5 px-2.5 py-1">
                                        <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                        <span class="text-slate-500 font-bold italic text-[11px]">Belum diisi</span>
                                    </div>
                                @endif
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
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500 font-medium">
                                Tidak ada data pengguna yang sesuai dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-4 border-t border-slate-200">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
