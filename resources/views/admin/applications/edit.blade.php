@extends('layouts.app', ['headerTitle' => 'Edit Aplikasi Eksternal'])

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-black text-emerald-950">Edit Konfigurasi: {{ $application->name }}</h2>
            <p class="text-xs text-slate-600 font-medium mt-1">Ubah URI, status, dan role akses aplikasi eksternal</p>
        </div>
        <a href="{{ route('admin.applications.index') }}" class="text-xs text-emerald-800 hover:underline font-bold">&larr; Kembali</a>
    </div>

    <form method="POST" action="{{ route('admin.applications.update', $application) }}" class="p-6 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-5">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Aplikasi Eksternal</label>
                <input type="text" name="name" value="{{ old('name', $application->name) }}" required
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-semibold focus:bg-white focus:border-emerald-600 focus:outline-none transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Slug URL</label>
                <input type="text" name="slug" value="{{ old('slug', $application->slug) }}" required
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-semibold focus:bg-white focus:border-emerald-600 focus:outline-none transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Kategori Aplikasi</label>
                <select name="category_id" class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-semibold focus:bg-white focus:border-emerald-600 focus:outline-none transition-all">
                    <option value="">-- Tanpa Kategori --</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id', $application->category_id) == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">Deskripsi Aplikasi</label>
            <textarea name="description" rows="2"
                class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-semibold focus:bg-white focus:border-emerald-600 focus:outline-none transition-all">{{ old('description', $application->description) }}</textarea>
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">Base URL Aplikasi Eksternal</label>
            <input type="url" name="base_url" value="{{ old('base_url', $application->base_url) }}" required
                class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-semibold focus:bg-white focus:border-emerald-600 focus:outline-none transition-all">
        </div>

        <div>
            <label class="block text-xs font-bold text-slate-700 mb-1.5">OAuth Redirect URI Callback</label>
            <input type="text" name="redirect_uri" value="{{ old('redirect_uri', $application->redirect_uri) }}" required
                class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-semibold focus:bg-white focus:border-emerald-600 focus:outline-none transition-all">
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Logout URI</label>
                <input type="text" name="logout_uri" value="{{ old('logout_uri', $application->logout_uri) }}"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-semibold focus:bg-white focus:border-emerald-600 focus:outline-none transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Health Check URL</label>
                <input type="url" name="health_check_url" value="{{ old('health_check_url', $application->health_check_url) }}"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-semibold focus:bg-white focus:border-emerald-600 focus:outline-none transition-all">
            </div>
        </div>

        <!-- Role Access Matrix -->
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
            <label class="block text-xs font-extrabold text-emerald-950 uppercase tracking-wider">
                Role Akses Diizinkan (Application Role Permission)
            </label>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-1">
                @foreach($roles as $role)
                    @php $isAllowed = $application->roles->contains($role->id); @endphp
                    <label class="flex items-center space-x-2.5 p-3 rounded-xl bg-white border border-slate-200 cursor-pointer hover:border-emerald-500 transition-all">
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}" {{ $isAllowed ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                        <div>
                            <span class="block text-xs font-bold text-slate-900 uppercase">{{ $role->slug }}</span>
                            <span class="block text-[10px] text-slate-500 truncate font-semibold">{{ $role->name }}</span>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Scopes OAuth</label>
                <input type="text" name="scopes" value="{{ old('scopes', $application->scopes) }}" required
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-semibold focus:bg-white focus:border-emerald-600 focus:outline-none transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Status Registrasi</label>
                <select name="status" required class="w-full px-4 py-2.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 text-sm font-semibold focus:bg-white focus:border-emerald-600 focus:outline-none transition-all">
                    <option value="active" {{ old('status', $application->status) === 'active' ? 'selected' : '' }}>Active (Aktif)</option>
                    <option value="maintenance" {{ old('status', $application->status) === 'maintenance' ? 'selected' : '' }}>Maintenance (Pemeliharaan)</option>
                    <option value="inactive" {{ old('status', $application->status) === 'inactive' ? 'selected' : '' }}>Inactive (Non-Aktif)</option>
                </select>
            </div>
        </div>

        <div class="pt-4 border-t border-slate-200 flex justify-end space-x-3">
            <button type="submit" class="px-6 py-3 bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold text-xs rounded-xl shadow-md shadow-emerald-700/20 transition-all">
                Simpan Perubahan &rarr;
            </button>
        </div>
    </form>
</div>
@endsection
