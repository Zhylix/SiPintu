@extends('layouts.app', ['headerTitle' => 'Edit Pengguna Gateway'])

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white">Edit Akun: {{ $user->name }}</h2>
            <p class="text-xs text-slate-400 mt-1">Perbarui data atau reset kata sandi pengguna</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="text-xs text-slate-400 hover:text-white font-semibold">&larr; Kembali</a>
    </div>

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-5">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Peran / Role Utama</label>
            <select name="role" required class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
                <option value="student" {{ old('role', $user->role) === 'student' ? 'selected' : '' }}>Siswa (Role: student)</option>
                <option value="teacher" {{ old('role', $user->role) === 'teacher' ? 'selected' : '' }}>Guru / Pendidik (Role: teacher)</option>
                <option value="dudi" {{ old('role', $user->role) === 'dudi' ? 'selected' : '' }}>DUDI / Mitra Industri (Role: dudi)</option>
                <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Administrator Gateway (Role: admin)</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nama Lengkap</label>
            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Alamat Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Username (Opsional)</label>
                <input type="text" name="username" value="{{ old('username', $user->username) }}"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Reset Kata Sandi (Kosongkan jika tidak diubah)</label>
            <input type="password" name="password" minlength="8" placeholder="Kosongkan jika tidak diubah"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nomor Telepon / WA</label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Status Akun</label>
                <select name="status" required class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
                    <option value="active" {{ old('status', $user->status) === 'active' ? 'selected' : '' }}>Active (Aktif)</option>
                    <option value="inactive" {{ old('status', $user->status) === 'inactive' ? 'selected' : '' }}>Inactive (Non-Aktif)</option>
                    <option value="suspended" {{ old('status', $user->status) === 'suspended' ? 'selected' : '' }}>Suspended (Ditangguhkan)</option>
                </select>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-800 flex justify-end space-x-3">
            <button type="submit" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs rounded-xl transition-all">
                Simpan Perubahan &rarr;
            </button>
        </div>
    </form>
</div>
@endsection
