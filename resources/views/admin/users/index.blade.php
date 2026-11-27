@extends('layouts.app', ['headerTitle' => 'Kelola Pengguna'])

@section('content')
<div class="space-y-6 min-w-0 max-w-full" x-data="{ 
    openImportModal: false,
    openResetModal: false,
    selectedUser: null,
    resetType: 'default',
    customPassword: '',
    showCustomPassword: false,
    mustChangePassword: true,
    sendWhatsapp: false,
    generatePassword() {
        const num = Math.floor(1000 + Math.random() * 9000);
        this.customPassword = 'Pintu#' + num;
    },
    openReset(user) {
        this.selectedUser = user;
        this.resetType = 'default';
        this.customPassword = '';
        this.showCustomPassword = false;
        this.mustChangePassword = true;
        this.sendWhatsapp = Boolean(user.phone && user.phone !== '0');
        this.openResetModal = true;
    }
}">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-sm min-w-0 max-w-full">
        <div>
            <h2 class="text-xl font-black text-emerald-950 tracking-tight">Manajemen Pengguna Gateway</h2>
            <p class="text-xs text-slate-600 font-medium mt-1">Kelola data pengguna, hak akses, dan import massal data akun.</p>
        </div>
        <div class="flex items-center gap-2.5 flex-wrap">
            <button type="button" @click="openImportModal = true" class="inline-flex items-center justify-center px-4 py-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs font-extrabold rounded-xl transition-all shadow-xs cursor-pointer">
                <svg class="w-4 h-4 mr-2 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Import Excel / CSV
            </button>
            <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-700/20">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Tambah Pengguna Baru
            </a>
        </div>
    </div>

    @if(session('reset_password_success'))
        @php $resData = session('reset_password_success'); @endphp
        <div class="p-5 bg-gradient-to-r from-emerald-50 via-teal-50 to-emerald-50 border-2 border-emerald-400 text-emerald-950 rounded-2xl shadow-sm space-y-3"
             x-data="{ copied: false }">
            <div class="flex items-start justify-between gap-3 flex-wrap sm:flex-nowrap">
                <div class="flex items-center space-x-2.5">
                    <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center shadow-xs shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <h4 class="font-black text-sm text-emerald-950">Kata Sandi Berhasil Direset!</h4>
                        <p class="text-xs text-emerald-800 font-medium">
                            Akun <span class="font-black">{{ $resData['user_name'] }}</span> ({{ $resData['identifier'] }}) siap digunakan dengan kredensial baru.
                        </p>
                    </div>
                </div>
                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-200 text-emerald-900 border border-emerald-300 shrink-0">
                    {{ $resData['role'] ?? 'User' }}
                </span>
            </div>

            <div class="p-3.5 bg-white rounded-xl border border-emerald-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-500 tracking-wider block">Kata Sandi Baru Pengguna:</span>
                    <span class="font-mono text-base font-black text-emerald-900 tracking-wider select-all">{{ $resData['password'] }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" 
                            @click="navigator.clipboard.writeText('{{ $resData['password'] }}'); copied = true; setTimeout(() => copied = false, 2500)"
                            class="px-3.5 py-2 bg-emerald-700 hover:bg-emerald-800 active:scale-95 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all flex items-center gap-1.5 cursor-pointer">
                        <svg x-show="!copied" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        <svg x-show="copied" x-cloak class="w-4 h-4 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span x-text="copied ? 'Tersalin!' : 'Salin Kata Sandi'"></span>
                    </button>
                </div>
            </div>

            <div class="flex items-center gap-4 text-[11px] text-emerald-800 font-semibold flex-wrap pt-1">
                <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Wajib Ganti Sandi: <strong class="ml-0.5">{{ !empty($resData['must_change']) ? 'Ya (Saat Login)' : 'Tidak' }}</strong>
                </span>
                <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    Sinkronisasi SSO Klien: <strong class="ml-0.5">Disiarkan Otomatis</strong>
                </span>
                @if(!empty($resData['wa_sent']))
                    <span class="flex items-center gap-1 text-emerald-700">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        Pemberitahuan WhatsApp: <strong>Terkirim ke Pengguna</strong>
                    </span>
                @elseif(!empty($resData['wa_error']))
                    <span class="flex items-center gap-1 text-amber-700">
                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Pemberitahuan WhatsApp: {{ $resData['wa_error'] }}
                    </span>
                @endif
            </div>
        </div>
    @endif

    @if(session('import_warnings'))
        <div class="p-4 bg-amber-50 border border-amber-200 text-amber-900 rounded-2xl text-xs space-y-1.5 shadow-sm">
            <div class="font-black flex items-center gap-1.5 text-amber-800">
                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>Catatan Baris yang Dilewati / Peringatan Import:</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-amber-700 pl-1">
                @foreach(session('import_warnings') as $warn)
                    <li>{{ $warn }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Filter & Search Bar -->
    <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row gap-4 justify-between items-center min-w-0 max-w-full">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col sm:flex-row gap-3 w-full">
            <!-- Search Input -->
            <div class="relative flex-1 flex items-center gap-2">
                <div class="relative flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, email, username, NIS, NIP, Kode DUDI, atau nomor HP..." 
                           class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-semibold placeholder-slate-400 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <button type="submit" class="px-3.5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-700/20 shrink-0 flex items-center gap-1">
                    <span>Cari</span>
                </button>
            </div>

            <!-- Role Filter -->
            <select name="role" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 text-xs text-slate-900 font-semibold rounded-xl px-3 py-2 focus:outline-none focus:border-emerald-600">
                <option value="all">Semua Role</option>
                <option value="student" {{ request('role') === 'student' ? 'selected' : '' }}>Siswa</option>
                <option value="alumni" {{ request('role') === 'alumni' ? 'selected' : '' }}>Alumni</option>
                <option value="teacher" {{ request('role') === 'teacher' ? 'selected' : '' }}>Guru / Pendidik</option>
                <option value="dudi" {{ request('role') === 'dudi' ? 'selected' : '' }}>Mitra DUDI</option>
                <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Administrator</option>
            </select>

            <!-- Jurusan Filter -->
            <select name="jurusan" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 text-xs text-slate-900 font-semibold rounded-xl px-3 py-2 focus:outline-none focus:border-emerald-600">
                <option value="all">Semua Jurusan</option>
                @foreach($jurusans as $j)
                    <option value="{{ $j->kode_jurusan }}" {{ request('jurusan') === $j->kode_jurusan ? 'selected' : '' }}>
                        Jurusan {{ $j->kode_jurusan }}
                    </option>
                @endforeach
            </select>

            @if(request()->anyFilled(['search', 'role', 'jurusan', 'phone_status', 'status']))
                <a href="{{ route('admin.users.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-200 flex items-center justify-center">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Mobile View Cards -->
    <div class="block md:hidden space-y-4 min-w-0 max-w-full">
        @forelse($users as $user)
            <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm space-y-3 min-w-0 max-w-full">
                <div class="flex items-center justify-between gap-2 min-w-0">
                    <div class="flex items-center space-x-3 min-w-0 flex-1">
                        @if($user->avatar_url)
                            <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" loading="lazy" decoding="async" class="w-10 h-10 rounded-full object-cover border border-emerald-300 shrink-0">
                        @else
                            <div class="w-10 h-10 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center justify-center font-extrabold text-xs shrink-0">
                                {{ $user->initials() }}
                            </div>
                        @endif
                        <div class="min-w-0 flex-1">
                            <div class="font-bold text-slate-900 text-sm truncate">{{ $user->name }}</div>
                            <div class="text-slate-600 text-xs font-medium truncate">{{ $user->email }}</div>
                            @if($user->classroom || $user->jurusan)
                                <div class="mt-0.5 flex flex-wrap items-center gap-1">
                                    @if($user->classroom)
                                        <span class="font-extrabold text-emerald-800 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 text-[10px]">Kelas {{ $user->classroom }}</span>
                                    @endif
                                    @if($user->jurusan)
                                        @php
                                            $uBadgeColor = match($user->jurusan->kode_jurusan) {
                                                'PPLG' => 'bg-orange-50 text-orange-900 border-orange-200',
                                                'AKL' => 'bg-yellow-50 text-yellow-950 border-yellow-300',
                                                'PM' => 'bg-rose-50 text-rose-900 border-rose-200',
                                                'MPLB' => 'bg-sky-50 text-sky-900 border-sky-200',
                                                'TO' => 'bg-white text-slate-900 border-slate-300',
                                                default => 'bg-slate-50 text-slate-800 border-slate-200',
                                            };
                                        @endphp
                                        <span class="font-extrabold px-1.5 py-0.5 rounded border text-[10px] {{ $uBadgeColor }}">Jurusan {{ $user->jurusan->kode_jurusan }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase shrink-0 {{ $user->status === 'active' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-rose-100 text-rose-800 border border-rose-300' }}">
                        {{ $user->status }}
                    </span>
                </div>

                <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-100 text-xs">
                    <div>
                        <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">Role</span>
                        <span class="font-bold text-slate-800 capitalize">{{ $user->getUserTypeName() }}</span>
                        @if($user->isAlumni())
                            <span class="text-[9px] text-teal-700 block font-mono font-bold mt-0.5">M: {{ $user->tahun_masuk }} • L: {{ $user->tahun_lulus }}</span>
                        @endif
                    </div>
                    <div>
                        @if($user->isStudent() || $user->isAlumni())
                            <span class="text-[10px] text-emerald-700 font-bold uppercase tracking-wider block">NIS Siswa</span>
                            <span class="font-mono font-bold text-xs {{ $user->nis ? 'text-emerald-800' : 'text-slate-300 italic' }}">
                                {{ $user->nis ?: '-' }}
                            </span>
                        @elseif($user->isTeacher())
                            <span class="text-[10px] text-blue-700 font-bold uppercase tracking-wider block">NIP Guru</span>
                            <span class="font-mono font-bold text-xs {{ $user->nip ? 'text-blue-800' : 'text-slate-300 italic' }}">
                                {{ $user->nip ?: '-' }}
                            </span>
                        @elseif($user->isDudi())
                            <span class="text-[10px] text-amber-700 font-bold uppercase tracking-wider block">Kode DUDI</span>
                            <span class="font-mono font-bold text-xs {{ $user->dudi_code ? 'text-amber-800' : 'text-slate-300 italic' }}">
                                {{ $user->dudi_code ?: '-' }}
                            </span>
                        @else
                            <span class="text-[10px] text-slate-500 font-bold uppercase tracking-wider block">ID Account</span>
                            <span class="font-mono font-bold text-xs text-slate-700">
                                {{ $user->external_id ?: ($user->username ?: '-') }}
                            </span>
                        @endif
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
                    <button type="button" @click="openReset({
                        id: {{ $user->id }},
                        name: '{{ addslashes($user->name) }}',
                        role: '{{ $user->role }}',
                        identifier: '{{ addslashes($user->nis ?: ($user->nip ?: ($user->dudi_code ?: ($user->external_id ?: $user->username)))) }}',
                        phone: '{{ addslashes($user->phone ?? '') }}',
                        resetUrl: '{{ route('admin.users.reset-password', $user) }}'
                    })" class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 rounded-lg text-xs font-bold transition-all flex items-center gap-1 cursor-pointer">
                        <svg class="w-3.5 h-3.5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        <span>Reset Sandi</span>
                    </button>
                    <a href="{{ route('admin.users.edit', $user) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 rounded-lg text-xs font-bold transition-all">
                        Edit
                    </a>
                    @if(!$user->isAdmin())
                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Hapus akun pengguna {{ $user->name }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-xs font-bold transition-all cursor-pointer">
                                Hapus
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-slate-400 bg-white rounded-2xl border border-slate-200">
                <svg class="w-12 h-12 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                <div class="font-bold text-slate-700 text-sm">Tidak ada data pengguna</div>
                <div class="text-xs text-slate-400 mt-1">Coba ubah kata kunci pencarian atau filter peran</div>
            </div>
        @endforelse

        @if($users->hasPages())
            <div class="p-4 bg-white rounded-2xl border border-slate-200">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    <!-- Desktop Table View -->
    <div class="hidden md:block bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm min-w-0 max-w-full">
        <div class="overflow-x-auto w-full max-w-full">
            <table class="w-full text-left text-xs min-w-[750px]">
                <thead class="bg-emerald-50 text-emerald-900 uppercase font-black text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-5 py-4">Pengguna</th>
                        <th class="px-4 py-4">Role / Type</th>
                        <th class="px-4 py-4">
                            @if(request('role') === 'teacher')
                                NIP Guru
                            @elseif(request('role') === 'student' || request('role') === 'alumni')
                                NIS Siswa
                            @elseif(request('role') === 'dudi')
                                Kode DUDI
                            @else
                                Identifier (NIP / NIS / Kode DUDI)
                            @endif
                        </th>
                        <th class="px-4 py-4">Nomor WhatsApp</th>
                        <th class="px-4 py-4">Status Akun</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 font-sans text-slate-700 bg-white">
                    @forelse($users as $user)
                        <tr class="hover:bg-emerald-50/50 transition-colors">
                            <td class="px-5 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-3">
                                    @if($user->avatar_url)
                                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" loading="lazy" decoding="async" class="w-9 h-9 rounded-full object-cover border border-emerald-300 shrink-0">
                                    @else
                                        <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center justify-center font-extrabold text-xs shrink-0">
                                            {{ $user->initials() }}
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-bold text-slate-900 text-sm">{{ $user->name }}</div>
                                        <div class="text-slate-600 text-xs font-medium">
                                            {{ $user->email }}
                                            @if($user->classroom)
                                                • <span class="font-extrabold text-emerald-800 bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200 text-[10px]">Kelas {{ $user->classroom }}</span>
                                            @endif
                                            @if($user->jurusan)
                                                @php
                                                    $uBadgeColor = match($user->jurusan->kode_jurusan) {
                                                        'PPLG' => 'bg-orange-50 text-orange-900 border-orange-200',
                                                        'AKL' => 'bg-yellow-50 text-yellow-950 border-yellow-300',
                                                        'PM' => 'bg-rose-50 text-rose-900 border-rose-200',
                                                        'MPLB' => 'bg-sky-50 text-sky-900 border-sky-200',
                                                        'TO' => 'bg-white text-slate-900 border-slate-300',
                                                        default => 'bg-slate-50 text-slate-800 border-slate-200',
                                                    };
                                                @endphp
                                                • <span class="font-extrabold px-1.5 py-0.5 rounded border text-[10px] {{ $uBadgeColor }}">Jurusan {{ $user->jurusan->kode_jurusan }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex flex-col gap-1 items-start">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase
                                        {{ $user->role === 'admin' ? 'bg-purple-100 text-purple-800 border border-purple-300' : '' }}
                                        {{ $user->role === 'teacher' ? 'bg-blue-100 text-blue-800 border border-blue-300' : '' }}
                                        {{ $user->role === 'dudi' ? 'bg-amber-100 text-amber-800 border border-amber-300' : '' }}
                                        {{ $user->role === 'student' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : '' }}
                                        {{ $user->role === 'alumni' ? 'bg-cyan-100 text-cyan-800 border border-cyan-300' : '' }}">
                                        {{ $user->getUserTypeName() }}
                                    </span>
                                    @if($user->classroom)
                                        <span class="text-[10px] font-bold text-emerald-800 font-mono">Kelas: {{ $user->classroom }}</span>
                                    @endif
                                    @if($user->jurusan)
                                        @php
                                            $uBadgeColor = match($user->jurusan->kode_jurusan) {
                                                'PPLG' => 'bg-orange-50 text-orange-900 border-orange-200',
                                                'AKL' => 'bg-yellow-50 text-yellow-950 border-yellow-300',
                                                'PM' => 'bg-rose-50 text-rose-900 border-rose-200',
                                                'MPLB' => 'bg-sky-50 text-sky-900 border-sky-200',
                                                'TO' => 'bg-white text-slate-900 border-slate-300',
                                                default => 'bg-slate-50 text-slate-800 border-slate-200',
                                            };
                                        @endphp
                                        <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded border font-mono {{ $uBadgeColor }}">Jurusan: {{ $user->jurusan->kode_jurusan }}</span>
                                    @endif
                                    @if($user->isAlumni())
                                        <div class="flex items-center gap-1 mt-0.5">
                                            <span class="text-[9px] font-black text-emerald-800 bg-emerald-50 px-1.5 py-0.2 rounded border border-emerald-200 font-mono" title="Tahun Masuk: {{ $user->created_at?->translatedFormat('d F Y') }}">Masuk: {{ $user->tahun_masuk }}</span>
                                            <span class="text-[9px] font-black text-teal-800 bg-teal-50 px-1.5 py-0.2 rounded border border-teal-200 font-mono" title="Tahun Lulus: {{ $user->updated_at?->translatedFormat('d F Y') }}">Lulus: {{ $user->tahun_lulus }}</span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap font-mono">
                                @if($user->isTeacher())
                                    @if($user->nip)
                                        <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-900 border border-blue-200 font-bold text-xs inline-flex items-center gap-1.5">
                                            @if(empty(request('role')) || request('role') === 'all')
                                                <span class="text-[10px] uppercase tracking-wider text-blue-600 font-black">NIP:</span>
                                            @endif
                                            {{ $user->nip }}
                                        </span>
                                    @else
                                        <span class="text-slate-300 font-normal italic">-</span>
                                    @endif
                                @elseif($user->isStudent() || $user->isAlumni())
                                    @if($user->nis)
                                        <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-900 border border-emerald-200 font-bold text-xs inline-flex items-center gap-1.5">
                                            @if(empty(request('role')) || request('role') === 'all')
                                                <span class="text-[10px] uppercase tracking-wider text-emerald-600 font-black">NIS:</span>
                                            @endif
                                            {{ $user->nis }}
                                        </span>
                                    @else
                                        <span class="text-slate-300 font-normal italic">-</span>
                                    @endif
                                @elseif($user->isDudi())
                                    @if($user->dudi_code)
                                        <span class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-900 border border-amber-200 font-bold text-xs inline-flex items-center gap-1.5">
                                            @if(empty(request('role')) || request('role') === 'all')
                                                <span class="text-[10px] uppercase tracking-wider text-amber-600 font-black">DUDI:</span>
                                            @endif
                                            {{ $user->dudi_code }}
                                        </span>
                                    @else
                                        <span class="text-slate-300 font-normal italic">-</span>
                                    @endif
                                @else
                                    @if($user->external_id || $user->username)
                                        <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-800 border border-slate-200 font-bold text-xs inline-block">
                                            {{ $user->external_id ?: $user->username }}
                                        </span>
                                    @else
                                        <span class="text-slate-300 font-normal italic">-</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap">
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
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase {{ $user->status === 'active' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-rose-100 text-rose-800 border border-rose-300' }}">
                                    {{ $user->status }}
                                </span>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap text-right space-x-1.5">
                                <button type="button" @click="openReset({
                                    id: {{ $user->id }},
                                    name: '{{ addslashes($user->name) }}',
                                    role: '{{ $user->role }}',
                                    identifier: '{{ addslashes($user->nis ?: ($user->nip ?: ($user->dudi_code ?: ($user->external_id ?: $user->username)))) }}',
                                    phone: '{{ addslashes($user->phone ?? '') }}',
                                    resetUrl: '{{ route('admin.users.reset-password', $user) }}'
                                })" class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 rounded-lg font-bold text-xs transition-colors inline-flex items-center gap-1 cursor-pointer" title="Reset Kata Sandi Akun">
                                    <svg class="w-3.5 h-3.5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                    <span>Reset Sandi</span>
                                </button>
                                <a href="{{ route('admin.users.edit', $user) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 rounded-lg font-bold transition-colors">
                                    Edit
                                </a>
                                @if(!$user->isAdmin())
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Hapus akun pengguna {{ $user->name }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg font-bold transition-colors cursor-pointer">
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

    <!-- Modal Import Data User dari CSV / Excel -->
    <div x-show="openImportModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
         x-cloak>
        <div @click.away="openImportModal = false" 
             x-show="openImportModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
             class="bg-white rounded-3xl shadow-2xl max-w-xl w-full border border-slate-200 overflow-hidden relative">
            
            <!-- Modal Header -->
            <div class="px-6 py-5 bg-gradient-to-r from-emerald-800 to-teal-900 text-white flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-white/10 rounded-xl">
                        <svg class="w-5 h-5 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    </div>
                    <div>
                        <h3 class="font-black text-base">Import Data Pengguna (Excel / CSV)</h3>
                        <p class="text-[11px] text-emerald-200 font-medium">Unggah file spreadsheet untuk menambahkan banyak akun sekaligus</p>
                    </div>
                </div>
                <button type="button" @click="openImportModal = false" class="p-1 rounded-xl text-white/70 hover:text-white hover:bg-white/10 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Modal Body -->
            <form action="{{ route('admin.users.import') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-5" onsubmit="this.querySelector('button[type=submit]').disabled = true; this.querySelector('button[type=submit] span').innerText = 'Memproses Import...';">
                @csrf

                <!-- Download Template Box -->
                <div class="p-4 bg-emerald-50/70 border border-emerald-200 rounded-2xl flex items-center justify-between gap-3">
                    <div class="space-y-0.5">
                        <span class="text-xs font-black text-emerald-950 block">Belum punya format file yang sesuai?</span>
                        <span class="text-[11px] text-emerald-800 font-medium block">Unduh contoh template CSV yang siap dibuka di Microsoft Excel.</span>
                    </div>
                    <a href="{{ route('admin.users.template') }}" class="px-3.5 py-2 bg-white hover:bg-emerald-100 text-emerald-900 border border-emerald-300 rounded-xl text-xs font-extrabold transition-all shadow-2xs shrink-0 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>Unduh Template</span>
                    </a>
                </div>

                <!-- File Input -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-black text-slate-800">
                        File Dokumen (.csv) <span class="text-rose-600">*</span>
                    </label>
                    <input type="file" name="file" required accept=".csv,text/csv,text/plain"
                           class="w-full text-xs text-slate-700 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-emerald-700 file:text-white hover:file:bg-emerald-800 file:cursor-pointer border border-slate-200 rounded-2xl p-2 bg-slate-50 focus:outline-none focus:border-emerald-600">
                    <p class="text-[10px] text-slate-500 font-medium">Format: CSV (*.csv) dengan pemisah koma (,) atau titik koma (;). Ukuran maksimal 10 MB.</p>
                </div>

                <!-- Checkboxes Options -->
                <div class="space-y-3 pt-2 border-t border-slate-100">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="update_existing" value="1" checked class="mt-0.5 rounded text-emerald-700 focus:ring-emerald-500 border-slate-300">
                        <div>
                            <span class="text-xs font-black text-slate-900 block">Perbarui Akun jika Sudah Ada (Update Existing)</span>
                            <span class="text-[11px] text-slate-500 font-medium block">Jika Email atau NIS/NIP sudah terdaftar, data nama, kelas, jurusan, & telepon akan disinkronkan.</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="force_change_password" value="1" checked class="mt-0.5 rounded text-emerald-700 focus:ring-emerald-500 border-slate-300">
                        <div>
                            <span class="text-xs font-black text-slate-900 block">Wajibkan Ganti Password saat Login Pertama</span>
                            <span class="text-[11px] text-slate-500 font-medium block">User baru akan diminta membuat kata sandi baru yang aman saat pertama kali login ke SiPintu.</span>
                        </div>
                    </label>
                </div>

                <!-- Column Reference List -->
                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl text-[11px] space-y-1.5">
                    <span class="font-extrabold text-slate-700 block text-xs">Kolom yang Didukung Template:</span>
                    <p class="text-slate-600 leading-relaxed font-mono text-[10px]">
                        name, email, username, external_id, role, jurusan, classroom, phone, password, force_change_password
                    </p>
                    <p class="text-slate-500 text-[10px]">
                        • Role: <span class="font-bold">student</span>, <span class="font-bold">teacher</span>, <span class="font-bold">dudi</span>, <span class="font-bold">alumni</span>.<br>
                        • Jurusan: <span class="font-bold">PPLG</span>, <span class="font-bold">TO</span>, <span class="font-bold">AKL</span>, <span class="font-bold">PM</span>, <span class="font-bold">MPLB</span>.
                    </p>
                </div>

                <!-- Actions -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button type="button" @click="openImportModal = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-700/20 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        <span>Mulai Proses Import</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Reset Kata Sandi Pengguna -->
    <div x-show="openResetModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
         x-cloak>
        <div @click.away="openResetModal = false" 
             x-show="openResetModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
             class="bg-white rounded-3xl shadow-2xl max-w-lg w-full border border-slate-200 overflow-hidden relative">
            
            <!-- Modal Header -->
            <div class="px-6 py-5 bg-gradient-to-r from-amber-700 via-amber-800 to-amber-900 text-white flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="p-2.5 bg-white/10 rounded-2xl">
                        <svg class="w-6 h-6 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-black text-base text-white">Reset Kata Sandi Akun</h3>
                        <p class="text-[11px] text-amber-200 font-medium">Atur ulang akses login pengguna dengan cepat dan aman</p>
                    </div>
                </div>
                <button type="button" @click="openResetModal = false" class="p-1 rounded-xl text-white/70 hover:text-white hover:bg-white/10 transition-colors cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Target User Card Summary -->
            <div class="px-6 pt-5 pb-1">
                <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl flex items-center justify-between gap-3">
                    <div class="flex items-center space-x-3 min-w-0">
                        <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-900 border border-amber-300 flex items-center justify-center font-black text-sm shrink-0">
                            <span x-text="selectedUser ? selectedUser.name.charAt(0).toUpperCase() : 'U'"></span>
                        </div>
                        <div class="min-w-0">
                            <h4 class="font-bold text-sm text-slate-900 truncate" x-text="selectedUser?.name"></h4>
                            <p class="text-[11px] text-slate-600 font-mono flex items-center gap-1.5 flex-wrap">
                                <span>ID: <strong class="text-slate-800" x-text="selectedUser?.identifier || '-'"></strong></span>
                                <span class="text-slate-300">•</span>
                                <span class="capitalize font-sans font-semibold text-emerald-700" x-text="selectedUser?.role"></span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Form -->
            <form :action="selectedUser?.resetUrl" method="POST" class="p-6 space-y-4" onsubmit="this.querySelector('button[type=submit]').disabled = true; this.querySelector('button[type=submit] span').innerText = 'Memproses Reset...';">
                @csrf

                <!-- Choice: Reset Type -->
                <div class="space-y-2">
                    <label class="block text-xs font-black text-slate-800 uppercase tracking-wider">
                        Pilih Metode Kata Sandi Baru
                    </label>
                    
                    <div class="grid grid-cols-2 gap-2.5">
                        <!-- Option Default -->
                        <label class="relative flex flex-col p-3 rounded-2xl border cursor-pointer transition-all text-left"
                               :class="resetType === 'default' ? 'bg-emerald-50/70 border-emerald-500 ring-2 ring-emerald-500/20' : 'bg-slate-50 border-slate-200 hover:bg-slate-100'">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-black" :class="resetType === 'default' ? 'text-emerald-950' : 'text-slate-800'">
                                    Bawaan Sistem
                                </span>
                                <input type="radio" name="reset_type" value="default" x-model="resetType" class="text-emerald-700 focus:ring-emerald-500">
                            </div>
                            <span class="text-[11px] font-mono font-bold text-emerald-800 bg-white px-2 py-0.5 rounded border border-emerald-200 inline-block w-fit mb-1">
                                password
                            </span>
                            <span class="text-[10px] text-slate-500 font-medium leading-tight">
                                Kata sandi standar bawaan sekolah.
                            </span>
                        </label>

                        <!-- Option Custom / Random -->
                        <label class="relative flex flex-col p-3 rounded-2xl border cursor-pointer transition-all text-left"
                               :class="resetType === 'custom' ? 'bg-amber-50/70 border-amber-500 ring-2 ring-amber-500/20' : 'bg-slate-50 border-slate-200 hover:bg-slate-100'">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-black" :class="resetType === 'custom' ? 'text-amber-950' : 'text-slate-800'">
                                    Kustom / Acak
                                </span>
                                <input type="radio" name="reset_type" value="custom" x-model="resetType" class="text-amber-700 focus:ring-amber-500">
                            </div>
                            <span class="text-[10px] text-slate-600 font-medium leading-tight mt-1">
                                Tentukan manual atau acak otomatis sekali klik.
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Custom Password Input (Shown only when resetType === 'custom') -->
                <div x-show="resetType === 'custom'" x-transition:enter="transition ease-out duration-200" class="space-y-2 pt-1" x-cloak>
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Kata Sandi Baru <span class="text-rose-500">*</span>
                        </label>
                        <button type="button" @click="generatePassword()" class="text-xs font-extrabold text-amber-800 hover:text-amber-900 flex items-center gap-1 cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <span>Acak Otomatis</span>
                        </button>
                    </div>
                    <div class="relative">
                        <input :type="showCustomPassword ? 'text' : 'password'" name="password" x-model="customPassword" minlength="8" :required="resetType === 'custom'" placeholder="Minimal 8 karakter"
                               class="w-full pl-4 pr-11 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-xs font-mono font-bold focus:bg-white focus:outline-none focus:border-amber-600 focus:ring-2 focus:ring-amber-600/20 transition-all">
                        <button type="button" @click="showCustomPassword = !showCustomPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 cursor-pointer">
                            <svg x-show="!showCustomPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            <svg x-show="showCustomPassword" x-cloak class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Checkbox Security Options -->
                <div class="space-y-3 pt-3 border-t border-slate-100">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="must_change_password" value="1" x-model="mustChangePassword" class="mt-0.5 rounded text-amber-700 focus:ring-amber-500 border-slate-300">
                        <div>
                            <span class="text-xs font-black text-slate-900 block">Wajibkan Ganti Password saat Login Berikutnya</span>
                            <span class="text-[11px] text-slate-500 font-medium block">Pengguna akan langsung diarahkan ke layar ganti kata sandi setelah memasukkan kata sandi ini.</span>
                        </div>
                    </label>

                    <template x-if="selectedUser?.phone">
                        <label class="flex items-start gap-2.5 cursor-pointer p-2.5 bg-emerald-50/60 rounded-xl border border-emerald-200">
                            <input type="checkbox" name="send_whatsapp" value="1" x-model="sendWhatsapp" class="mt-0.5 rounded text-emerald-700 focus:ring-emerald-500 border-slate-300">
                            <div>
                                <span class="text-xs font-black text-emerald-950 block">Kirim Kata Sandi Baru via WhatsApp</span>
                                <span class="text-[11px] text-emerald-800 font-medium block">
                                    Pesan resmi akan dikirim ke nomor: <strong class="font-mono" x-text="selectedUser?.phone"></strong>
                                </span>
                            </div>
                        </label>
                    </template>
                </div>

                <!-- Actions -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button type="button" @click="openResetModal = false" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-amber-700 hover:bg-amber-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-amber-700/20 flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                        <span>Konfirmasi Reset Password</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

