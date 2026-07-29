@extends('layouts.app', ['headerTitle' => 'Daftarkan Aplikasi Eksternal Baru'])

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white">Registrasi Aplikasi Eksternal Baru</h2>
            <p class="text-xs text-slate-400 mt-1">Konfigurasikan OAuth Client ID, Secret, Redirect URI, dan Hak Akses Role</p>
        </div>
        <a href="{{ route('admin.applications.index') }}" class="text-xs text-slate-400 hover:text-white font-semibold">&larr; Batal & Kembali</a>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs space-y-1">
            @foreach($errors->all() as $error)
                <div>• {{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.applications.store') }}" class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-5">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nama Aplikasi Eksternal</label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="Contoh: Aplikasi Rapor Eksternal"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Slug URL</label>
                <input type="text" name="slug" value="{{ old('slug') }}" required placeholder="aplikasi-rapor"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Deskripsi Aplikasi</label>
            <textarea name="description" rows="2" placeholder="Deskripsi singkat fungsi aplikasi eksternal..."
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">{{ old('description') }}</textarea>
        </div>

        <!-- OAuth Client Credentials Box -->
        <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-3">
            <h4 class="text-xs font-bold text-indigo-400 uppercase tracking-wider">Kredensial OAuth 2.0 Client (Auto-Generated)</h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1">Client ID</label>
                    <input type="text" name="client_id" value="{{ old('client_id', $generatedClientId) }}" readonly
                        class="w-full px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-indigo-300 font-mono text-xs focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 mb-1">Client Secret (Plain Text - Generated)</label>
                    <input type="text" name="client_secret" value="{{ old('client_secret', $generatedSecret) }}" readonly
                        class="w-full px-4 py-2 rounded-xl bg-slate-900 border border-slate-800 text-emerald-400 font-mono text-xs focus:outline-none">
                </div>
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Base URL Aplikasi Eksternal</label>
            <input type="url" name="base_url" value="{{ old('base_url') }}" required placeholder="https://pkl.sekolah.id"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">OAuth Redirect URI Callback</label>
            <input type="text" name="redirect_uri" value="{{ old('redirect_uri') }}" required placeholder="https://pkl.sekolah.id/callback"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
            <p class="text-[11px] text-slate-400 mt-1">URI tempat Gateway akan mengirimkan Authorization Code setelah SSO login berhasil.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Logout URI</label>
                <input type="text" name="logout_uri" value="{{ old('logout_uri') }}" placeholder="https://pkl.sekolah.id/logout"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Health Check URL (Monitoring)</label>
                <input type="url" name="health_check_url" value="{{ old('health_check_url') }}" placeholder="https://pkl.sekolah.id/api/health"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
            </div>
        </div>

        <!-- Role Access Matrix -->
        <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-3">
            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider">
                Role Akses Diizinkan (Application Role Permission)
            </label>
            <p class="text-xs text-slate-400">Pilih role mana saja yang diperbolehkan oleh Gateway untuk mengakses aplikasi eksternal ini:</p>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
                @foreach($roles as $role)
                    <label class="flex items-center space-x-2.5 p-3 rounded-xl bg-slate-900 border border-slate-800 cursor-pointer hover:border-indigo-500/50 transition-all">
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}" checked class="rounded bg-slate-950 border-slate-700 text-indigo-600 focus:ring-indigo-500">
                        <div>
                            <span class="block text-xs font-bold text-white uppercase">{{ $role->slug }}</span>
                            <span class="block text-[10px] text-slate-400 truncate">{{ $role->name }}</span>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Scopes OAuth</label>
                <input type="text" name="scopes" value="{{ old('scopes', 'openid profile email') }}" required
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Status Registrasi</label>
                <select name="status" required class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
                    <option value="active">Active (Aktif)</option>
                    <option value="maintenance">Maintenance (Pemeliharaan)</option>
                    <option value="inactive">Inactive (Non-Aktif)</option>
                </select>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-800 flex justify-end space-x-3">
            <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white font-bold text-xs rounded-xl shadow-lg shadow-indigo-600/30 transition-all">
                Daftarkan Aplikasi & Simpan Kredensial &rarr;
            </button>
        </div>
    </form>
</div>
@endsection
