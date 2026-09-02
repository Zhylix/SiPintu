@extends('layouts.app', ['headerTitle' => 'Edit Pengguna Gateway'])

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
            @if($user->avatar_url)
                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" loading="lazy" decoding="async" class="w-12 h-12 rounded-2xl object-cover ring-2 ring-emerald-500/20 shadow-sm shrink-0">
            @else
                <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center justify-center font-black text-sm shrink-0">
                    {{ $user->initials() }}
                </div>
            @endif
            <div>
                <h2 class="text-xl font-black text-emerald-950">Edit Akun: {{ $user->name }}</h2>
                <p class="text-xs text-slate-600 font-medium mt-0.5">Perbarui data atau reset kata sandi pengguna</p>
            </div>
        </div>
        <a href="{{ route('admin.users.index') }}" class="text-xs text-slate-600 hover:text-slate-900 font-bold">&larr; Kembali</a>
    </div>

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="p-6 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Peran / Role Utama</label>
            @if(auth()->id() === $user->id)
                <input type="hidden" name="role" value="admin">
                <input type="text" value="Administrator Gateway (Role Utama Anda - Terkunci)" disabled
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-100 border border-slate-200 text-slate-600 font-semibold text-sm cursor-not-allowed">
                <div class="p-4 mt-2 bg-amber-50 border border-amber-200 text-amber-900 rounded-2xl text-xs font-semibold flex items-start space-x-2">
                    <svg class="w-4 h-4 text-amber-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <p>Anda sedang mengedit akun Anda sendiri. Role Administrator tidak dapat diubah ke role lain.</p>
                </div>
            @else
                <select name="role" required class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-semibold text-sm focus:border-emerald-600 focus:bg-white focus:outline-none transition-all">
                    <option value="student" {{ old('role', $user->role) === 'student' ? 'selected' : '' }}>Siswa</option>
                    <option value="alumni" {{ old('role', $user->role) === 'alumni' ? 'selected' : '' }}>Alumni</option>
                    <option value="teacher" {{ old('role', $user->role) === 'teacher' ? 'selected' : '' }}>Guru / Pendidik</option>
                    <option value="dudi" {{ old('role', $user->role) === 'dudi' ? 'selected' : '' }}>Mitra DUDI</option>
                    <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Administrator Gateway</option>
                </select>
            @endif
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-semibold text-sm focus:border-emerald-600 focus:bg-white focus:outline-none transition-all">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Alamat Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-semibold text-sm focus:border-emerald-600 focus:bg-white focus:outline-none transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Username (Opsional)</label>
                <input type="text" name="username" value="{{ old('username', $user->username) }}"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-semibold text-sm focus:border-emerald-600 focus:bg-white focus:outline-none transition-all">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Reset Kata Sandi (Kosongkan jika tidak diubah)</label>
            <input type="password" name="password" minlength="8" placeholder="Kosongkan jika tidak diubah"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-semibold text-sm focus:border-emerald-600 focus:bg-white focus:outline-none transition-all">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nomor Telepon / WhatsApp</label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="Contoh: 08123456789"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-semibold text-sm focus:border-emerald-600 focus:bg-white focus:outline-none transition-all">
                <p class="text-[11px] text-slate-500 font-medium mt-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-emerald-600 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg> Digunakan sebagai tujuan penerima Pengumuman WhatsApp.
                </p>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Status Akun</label>
                <select name="status" required class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-semibold text-sm focus:border-emerald-600 focus:bg-white focus:outline-none transition-all">
                    <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Active (Aktif)</option>
                    <option value="inactive" {{ old('status', $user->status) === 'inactive' ? 'selected' : '' }}>Inactive (Non-Aktif)</option>
                    <option value="suspended" {{ old('status', $user->status) === 'suspended' ? 'selected' : '' }}>Suspended (Ditangguhkan)</option>
                </select>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
            <button type="submit" class="px-6 py-3 bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-700/20 transition-all">
                Simpan Perubahan &rarr;
            </button>
        </div>
    </form>
</div>
@endsection
