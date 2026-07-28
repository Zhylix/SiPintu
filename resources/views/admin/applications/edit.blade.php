@extends('layouts.app', ['headerTitle' => 'Edit Aplikasi Eksternal'])

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-bold text-white">Edit Konfigurasi: {{ $application->name }}</h2>
            <p class="text-xs text-slate-400 mt-1">Ubah URI, status, dan role akses aplikasi eksternal</p>
        </div>
        <a href="{{ route('admin.applications.index') }}" class="text-xs text-slate-400 hover:text-white font-semibold">&larr; Kembali</a>
    </div>

    <form method="POST" action="{{ route('admin.applications.update', $application) }}" class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-5">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Nama Aplikasi Eksternal</label>
                <input type="text" name="name" value="{{ old('name', $application->name) }}" required
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Slug URL</label>
                <input type="text" name="slug" value="{{ old('slug', $application->slug) }}" required
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Deskripsi Aplikasi</label>
            <textarea name="description" rows="2"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">{{ old('description', $application->description) }}</textarea>
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">Base URL Aplikasi Eksternal</label>
            <input type="url" name="base_url" value="{{ old('base_url', $application->base_url) }}" required
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
        </div>

        <div>
            <label class="block text-xs font-semibold text-slate-300 mb-1.5">OAuth Redirect URI Callback</label>
            <input type="text" name="redirect_uri" value="{{ old('redirect_uri', $application->redirect_uri) }}" required
                class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Logout URI</label>
                <input type="text" name="logout_uri" value="{{ old('logout_uri', $application->logout_uri) }}"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Health Check URL</label>
                <input type="url" name="health_check_url" value="{{ old('health_check_url', $application->health_check_url) }}"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
            </div>
        </div>

        <!-- Role Access Matrix -->
        <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-3">
            <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider">
                Role Akses Diizinkan (Application Role Permission)
            </label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
                @foreach($roles as $role)
                    @php $isAllowed = $application->roles->contains($role->id); @endphp
                    <label class="flex items-center space-x-2.5 p-3 rounded-xl bg-slate-900 border border-slate-800 cursor-pointer hover:border-indigo-500/50 transition-all">
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}" {{ $isAllowed ? 'checked' : '' }} class="rounded bg-slate-950 border-slate-700 text-indigo-600 focus:ring-indigo-500">
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
                <input type="text" name="scopes" value="{{ old('scopes', $application->scopes) }}" required
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Status Registrasi</label>
                <select name="status" required class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-sm focus:border-indigo-500 focus:outline-none">
                    <option value="active" {{ old('status', $application->status) === 'active' ? 'selected' : '' }}>Active (Aktif)</option>
                    <option value="maintenance" {{ old('status', $application->status) === 'maintenance' ? 'selected' : '' }}>Maintenance (Pemeliharaan)</option>
                    <option value="inactive" {{ old('status', $application->status) === 'inactive' ? 'selected' : '' }}>Inactive (Non-Aktif)</option>
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
