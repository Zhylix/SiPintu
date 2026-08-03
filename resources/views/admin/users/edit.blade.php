@extends('layouts.app', ['headerTitle' => 'Edit Pengguna Gateway'])

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-black text-emerald-950">Edit Akun: {{ $user->name }}</h2>
            <p class="text-xs text-slate-600 font-medium mt-1">Perbarui data atau reset kata sandi pengguna</p>
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
                <p class="text-[11px] text-amber-700 font-semibold mt-1">● Anda sedang mengedit akun Anda sendiri. Role Administrator tidak dapat diubah ke role lain.</p>
            @else
                <select name="role" required class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-semibold text-sm focus:border-emerald-600 focus:bg-white focus:outline-none transition-all">
                    <option value="student" {{ old('role', $user->role) === 'student' ? 'selected' : '' }}>Siswa</option>
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
                <p class="text-[11px] text-slate-500 font-medium mt-1">📱 Digunakan sebagai tujuan penerima Pengumuman WhatsApp.</p>
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
