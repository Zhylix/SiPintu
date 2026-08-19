@extends('layouts.app', ['headerTitle' => 'Buat Pengumuman Baru'])

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-black text-emerald-950 tracking-tight">Buat Pengumuman Baru</h2>
            <p class="text-xs text-slate-600 font-medium mt-1">Isi formulir di bawah ini untuk menerbitkan pengumuman ke pengguna gateway.</p>
        </div>
        <a href="{{ route('admin.announcements.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 text-xs font-bold rounded-xl transition-all">
            &larr; Kembali
        </a>
    </div>

    <!-- Form Card -->
    <form action="{{ route('admin.announcements.store') }}" method="POST" class="bg-white border border-slate-200 rounded-2xl p-6 space-y-5 shadow-sm">
        @csrf

        <!-- Judul -->
        <div>
            <label for="title" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Judul Pengumuman</label>
            <input type="text" name="title" id="title" value="{{ old('title') }}" required placeholder="Contoh: Pemeliharaan Server Akses Terpadu) Malam Ini"
                   class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 font-semibold focus:outline-none focus:border-emerald-600 focus:bg-white transition-all @error('title') border-rose-500 @enderror">
            @error('title')
                <p class="text-xs text-rose-600 font-bold mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Target Role, Channel & Tipe Alert Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Target Role -->
            <div>
                <label for="target_role" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Target Pengguna (Role)</label>
                <select name="target_role" id="target_role" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 font-semibold focus:outline-none focus:border-emerald-600 focus:bg-white transition-all">
                    <option value="all" {{ old('target_role') === 'all' ? 'selected' : '' }}>Semua Pengguna (Termasuk Admin)</option>
                    <option value="user" {{ old('target_role') === 'user' ? 'selected' : '' }}>Pengguna Biasa / User (Siswa, Alumni, Guru, DUDI)</option>
                    <option value="student" {{ old('target_role') === 'student' ? 'selected' : '' }}>Siswa Saja</option>
                    <option value="alumni" {{ old('target_role') === 'alumni' ? 'selected' : '' }}>Alumni Saja</option>
                    <option value="teacher" {{ old('target_role') === 'teacher' ? 'selected' : '' }}>Guru Saja</option>
                    <option value="dudi" {{ old('target_role') === 'dudi' ? 'selected' : '' }}>Mitra DUDI Saja</option>
                </select>
                @error('target_role')
                    <p class="text-xs text-rose-600 font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Saluran Pengumuman -->
            <div>
                <label for="channel" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Saluran Pengumuman</label>
                <select name="channel" id="channel" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 font-semibold focus:outline-none focus:border-emerald-600 focus:bg-white transition-all">
                    <option value="both" {{ old('channel', 'both') === 'both' ? 'selected' : '' }}>Web & WhatsApp</option>
                    <option value="web" {{ old('channel') === 'web' ? 'selected' : '' }}>Web</option>
                    <option value="whatsapp" {{ old('channel') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                </select>
                @error('channel')
                    <p class="text-xs text-rose-600 font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Tipe Alert -->
            <div>
                <label for="type" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Tipe Alert Banner</label>
                <select name="type" id="type" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 font-semibold focus:outline-none focus:border-emerald-600 focus:bg-white transition-all">
                    <option value="info" {{ old('type') === 'info' ? 'selected' : '' }}>Info (Warna Biru / Informasi Umim)</option>
                    <option value="warning" {{ old('type') === 'warning' ? 'selected' : '' }}>Peringatan (Warna Kuning / Perhatian)</option>
                    <option value="danger" {{ old('type') === 'danger' ? 'selected' : '' }}>Bahaya / Urgen (Warna Merah / Penting)</option>
                    <option value="success" {{ old('type') === 'success' ? 'selected' : '' }}>Sukses (Warna Hijau / Pengumuman Positif)</option>
                </select>
                @error('type')
                    <p class="text-xs text-rose-600 font-bold mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Isi Pengumuman -->
        <div>
            <label for="content" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Isi Pesan Pengumuman</label>
            <textarea name="content" id="content" rows="5" required placeholder="Tulis rincian pesan pengumuman di sini..."
                      class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 font-semibold focus:outline-none focus:border-emerald-600 focus:bg-white transition-all @error('content') border-rose-500 @enderror">{{ old('content') }}</textarea>
            @error('content')
                <p class="text-xs text-rose-600 font-bold mt-1">{{ $message }}</p>
            @enderror
        </div>

        <!-- Status Active & WhatsApp Checkboxes -->
        <div class="space-y-3 pt-2">
            <div class="flex items-center space-x-3">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                       class="w-4 h-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                <label for="is_active" class="text-xs font-semibold text-slate-700">
                    Publikasikan langsung sekarang (Aktif)
                </label>
            </div>

            <div class="flex items-center space-x-3 p-3 bg-emerald-50/70 border border-emerald-200 rounded-xl">
                <input type="checkbox" name="send_whatsapp" id="send_whatsapp" value="1" {{ old('send_whatsapp') ? 'checked' : '' }}
                       class="w-4 h-4 rounded border-emerald-400 text-emerald-700 focus:ring-emerald-600">
                <label for="send_whatsapp" class="text-xs font-bold text-emerald-950 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-0.999 3.648 3.742-.981z"/></svg>
                    Kirim pengumuman ini secara otomatis ke nomor WhatsApp pengguna (Queue Asynchronous)
                </label>
            </div>
        </div>

        <!-- Submit Button -->
        <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
            <a href="{{ route('admin.announcements.index') }}" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 text-xs font-bold rounded-xl transition-all">
                Batal
            </a>
            <button type="submit" class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-700/20">
                Terbitkan Pengumuman
            </button>
        </div>
    </form>
</div>
@endsection
