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

    <form method="POST" action="{{ route('admin.applications.update', $application) }}" enctype="multipart/form-data" class="p-6 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-5" x-data="{ logoPreview: @js($application->logo_url), removeLogo: false }">
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

        <!-- Kelola Logo Website / Aplikasi -->
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <label class="block text-xs font-extrabold text-emerald-950 uppercase tracking-wider">
                        Logo Website / Aplikasi Eksternal
                    </label>
                    <p class="text-xs text-slate-600 font-medium">Ubah atau hapus logo resmi website aplikasi.</p>
                </div>

                @if($application->logo)
                    <button type="button" @click="removeLogo = true; logoPreview = null;" x-show="!removeLogo" class="text-xs text-rose-600 hover:text-rose-800 font-bold bg-rose-50 hover:bg-rose-100 border border-rose-200 px-3 py-1 rounded-lg transition-all">
                        Hapus Logo Saat Ini
                    </button>
                @endif
            </div>
            
            <div class="flex items-center gap-4 pt-1">
                <div class="w-16 h-16 rounded-2xl bg-white border-2 border-dashed border-emerald-300 flex items-center justify-center overflow-hidden shrink-0 shadow-sm relative">
                    <template x-if="logoPreview && !removeLogo">
                        <img :src="logoPreview" class="w-full h-full object-cover" alt="Preview Logo">
                    </template>
                    <template x-if="!logoPreview || removeLogo">
                        <div class="text-center p-2">
                            <svg class="w-7 h-7 text-emerald-600 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </template>
                </div>

                <div class="flex-1 space-y-2">
                    <input type="file" name="logo" id="logo_input" accept="image/*"
                           @change="const file = $event.target.files[0]; if (file) { logoPreview = URL.createObjectURL(file); removeLogo = false; }"
                           class="block w-full text-xs text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-emerald-100 file:text-emerald-800 hover:file:bg-emerald-200 cursor-pointer">
                    <input type="hidden" name="remove_logo" :value="removeLogo ? 1 : 0">
                    <p x-show="removeLogo" class="text-xs text-rose-600 font-bold">Logo akan dihapus setelah perubahan disimpan.</p>
                    <p x-show="!removeLogo" class="text-[11px] text-slate-500 font-medium">Format: PNG, JPG, JPEG, WEBP, SVG. Maksimal 2MB.</p>
                </div>
            </div>
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
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-1">
                @foreach($roles as $role)
                    @php $isAllowed = $application->roles->contains($role->id); @endphp
                    <label class="flex items-center space-x-2.5 p-3 rounded-xl bg-white border border-slate-200 cursor-pointer hover:border-emerald-500 transition-all min-w-0">
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}" {{ $isAllowed ? 'checked' : '' }} class="rounded border-slate-300 text-emerald-700 focus:ring-emerald-600 shrink-0">
                        <div class="min-w-0 flex-1">
                            <span class="block text-xs font-bold text-slate-900 uppercase truncate" title="{{ $role->getDisplayName() }}">{{ $role->getDisplayName() }}</span>
                            <span class="block text-[10px] text-slate-500 truncate font-semibold" title="{{ $role->name }}">{{ $role->name }}</span>
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
