@extends('layouts.app', ['headerTitle' => 'Buat Akun Pengguna Baru'])

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-black text-emerald-950">Tambah Akun Guru / DUDI / Admin</h2>
            <p class="text-xs text-slate-600 font-medium mt-1">Akun ini akan disimpan di database Gateway dan menggunakan Password Hash</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="text-xs text-slate-600 hover:text-slate-900 font-bold">&larr; Kembali</a>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-semibold space-y-1">
            @foreach($errors->all() as $error)
                <div>• {{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.store') }}" class="p-6 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-5">
        @csrf

        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Peran / Role Utama</label>
            <select name="role" required class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-semibold text-sm focus:border-emerald-600 focus:bg-white focus:outline-none transition-all">
                <option value="teacher" {{ old('role', old('user_type')) === 'teacher' ? 'selected' : '' }}>Guru / Pendidik (NIP)</option>
                <option value="dudi" {{ old('role', old('user_type')) === 'dudi' ? 'selected' : '' }}>Mitra DUDI (Kode DUDI)</option>
                <option value="student" {{ old('role', old('user_type')) === 'student' ? 'selected' : '' }}>Siswa (NIS)</option>
                <option value="alumni" {{ old('role', old('user_type')) === 'alumni' ? 'selected' : '' }}>Alumni</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nama Lengkap</label>
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: Drs. Bambang Hariyanto / PT Maju Jaya"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-semibold text-sm focus:border-emerald-600 focus:bg-white focus:outline-none transition-all">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Alamat Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="guru@sekolah.id"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-semibold text-sm focus:border-emerald-600 focus:bg-white focus:outline-none transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Username (Opsional)</label>
                <input type="text" name="username" value="{{ old('username') }}" placeholder="guru_bambang"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-semibold text-sm focus:border-emerald-600 focus:bg-white focus:outline-none transition-all">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Kata Sandi (Password Wajib Hashed)</label>
            <input type="password" name="password" required minlength="8" placeholder="Minimal 8 karakter"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-semibold text-sm focus:border-emerald-600 focus:bg-white focus:outline-none transition-all">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Nomor Telepon / WA</label>
                <input type="text" name="phone" value="{{ old('phone') }}" placeholder="0812xxxxxxxx"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-semibold text-sm focus:border-emerald-600 focus:bg-white focus:outline-none transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Status Akun</label>
                <select name="status" required class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 font-semibold text-sm focus:border-emerald-600 focus:bg-white focus:outline-none transition-all">
                    <option value="active">Active (Aktif)</option>
                    <option value="inactive">Inactive (Non-Aktif)</option>
                    <option value="suspended">Suspended (Ditangguhkan)</option>
                </select>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
            <button type="submit" class="px-6 py-3 bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-700/20 transition-all">
                Simpan & Buat Akun &rarr;
            </button>
        </div>
    </form>
</div>
@endsection
