@extends('layouts.app', ['headerTitle' => 'Buat Akun Pengguna Baru'])

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white">Tambah Akun Guru / DUDI / Admin</h2>
            <p class="text-xs text-slate-400 mt-1">Akun ini akan disimpan di database Gateway dan menggunakan Password Hash</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="text-xs text-slate-400 hover:text-white font-semibold">&larr; Batal & Kembali</a>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs space-y-1">
            @foreach($errors->all() as $error)
                <div>• {{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.users.store') }}" class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-5">
        @csrf

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Jenis Akun (Role Primary)</label>
            <select name="user_type" required class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
                <option value="teacher" {{ old('user_type') === 'teacher' ? 'selected' : '' }}>Guru / Pendidik (Role: teacher)</option>
                <option value="dudi" {{ old('user_type') === 'dudi' ? 'selected' : '' }}>DUDI / Mitra Industri (Role: dudi)</option>
                <option value="admin" {{ old('user_type') === 'admin' ? 'selected' : '' }}>Administrator Gateway (Role: admin)</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nama Lengkap</label>
            <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: Drs. Bambang Hariyanto / PT Maju Jaya"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Alamat Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="guru@sekolah.id"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Username (Opsional)</label>
                <input type="text" name="username" value="{{ old('username') }}" placeholder="guru_bambang"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Kata Sandi (Password Wajib Hashed)</label>
            <input type="password" name="password" required minlength="8" placeholder="Minimal 8 karakter"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nomor Telepon / WA</label>
                <input type="text" name="phone" value="{{ old('phone') }}" placeholder="0812xxxxxxxx"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Status Akun</label>
                <select name="status" required class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
                    <option value="active">Active (Aktif)</option>
                    <option value="inactive">Inactive (Non-Aktif)</option>
                    <option value="suspended">Suspended (Ditangguhkan)</option>
                </select>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-800 flex justify-end space-x-3">
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/30 transition-all">
                Simpan & Buat Akun &rarr;
            </button>
        </div>
    </form>
</div>
@endsection
