@extends('layouts.app', ['headerTitle' => 'Buat Pengumuman Baru'])

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight">Buat Pengumuman Baru</h2>
            <p class="text-xs text-slate-400 mt-1">Isi formulir di bawah ini untuk menerbitkan pengumuman ke pengguna gateway.</p>
        </div>
        <a href="{{ route('admin.announcements.index') }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-semibold rounded-xl transition-all">
            &larr; Kembali
        </a>
    </div>

    <!-- Form Card -->
    <form action="{{ route('admin.announcements.store') }}" method="POST" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-5 shadow-xl">
        @csrf

        <!-- Judul -->
        <div>
            <label for="title" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Judul Pengumuman</label>
            <input type="text" name="title" id="title" value="{{ old('title') }}" required placeholder="Contoh: Pemeliharaan Server SSO Malam Ini"
                   class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-indigo-500 @error('title') border-rose-500 @enderror">
            @error('title')
                <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Target Role & Tipe Alert Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Target Role -->
            <div>
                <label for="target_role" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Target Pengguna (Role)</label>
                <select name="target_role" id="target_role" required class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-indigo-500">
                    <option value="all" {{ old('target_role') === 'all' ? 'selected' : '' }}>Semua Pengguna (Siswa, Guru, DUDI)</option>
                    <option value="student" {{ old('target_role') === 'student' ? 'selected' : '' }}>Siswa Saja</option>
                    <option value="teacher" {{ old('target_role') === 'teacher' ? 'selected' : '' }}>Guru Saja</option>
                    <option value="dudi" {{ old('target_role') === 'dudi' ? 'selected' : '' }}>Mitra DUDI Saja</option>
                </select>
                @error('target_role')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Tipe Alert -->
            <div>
                <label for="type" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Tipe Alert Banner</label>
                <select name="type" id="type" required class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-indigo-500">
                    <option value="info" {{ old('type') === 'info' ? 'selected' : '' }}>Info (Warna Biru / Informasi Umim)</option>
                    <option value="warning" {{ old('type') === 'warning' ? 'selected' : '' }}>Peringatan (Warna Kuning / Perhatian)</option>
                    <option value="danger" {{ old('type') === 'danger' ? 'selected' : '' }}>Bahaya / Urgen (Warna Merah / Penting)</option>
                    <option value="success" {{ old('type') === 'success' ? 'selected' : '' }}>Sukses (Warna Hijau / Pengumuman Positif)</option>
                </select>
                @error('type')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Isi Pengumuman -->
        <div>
            <label for="content" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Isi Pesan Pengumuman</label>
            <textarea name="content" id="content" rows="5" required placeholder="Tulis rincian pesan pengumuman di sini..."
                      class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-indigo-500 @error('content') border-rose-500 @enderror">{{ old('content') }}</textarea>
            @error('content')
                <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Status Active Checkbox -->
        <div class="flex items-center space-x-3 pt-2">
            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                   class="w-4 h-4 rounded border-slate-800 bg-slate-950 text-indigo-600 focus:ring-indigo-500">
            <label for="is_active" class="text-xs font-medium text-slate-300">
                Publikasikan langsung sekarang (Aktif)
            </label>
        </div>

        <!-- Submit Button -->
        <div class="pt-4 border-t border-slate-800 flex justify-end space-x-3">
            <a href="{{ route('admin.announcements.index') }}" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold rounded-xl transition-all">
                Batal
            </a>
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-all shadow-lg shadow-indigo-600/30">
                Terbitkan Pengumuman
            </button>
        </div>
    </form>
</div>
@endsection
