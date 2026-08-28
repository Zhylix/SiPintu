@extends('layouts.app', ['headerTitle' => 'Pengaturan Logo & Background'])

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <span class="p-2 bg-emerald-100 text-emerald-800 rounded-xl font-black text-lg">🖼️</span>
                <h2 class="text-xl font-black text-emerald-950 tracking-tight">Pengaturan Logo Website & Background Login</h2>
            </div>
            <p class="text-xs text-slate-600 font-medium mt-1">
                Kelola identitas visual Gateway SMKN 1 Bangsri. Anda dapat mengubah logo website serta membedakan gambar background khusus untuk halaman login portal.
            </p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('login') }}" target="_blank" class="px-4 py-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-xl text-xs font-bold transition-all flex items-center space-x-1.5 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                <span>Lihat Halaman Login</span>
            </a>
        </div>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-2xl text-xs font-bold flex items-center space-x-3 shadow-sm">
            <svg class="w-5 h-5 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('info'))
        <div class="p-4 bg-blue-50 border border-blue-200 text-blue-900 rounded-2xl text-xs font-bold flex items-center space-x-3 shadow-sm">
            <svg class="w-5 h-5 text-blue-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>{{ session('info') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-900 rounded-2xl text-xs font-bold space-y-1 shadow-sm">
            @foreach($errors->all() as $error)
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    <span>{{ $error }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Main Grid: Logo Website & Background Login Forms -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- CARD 1: LOGO WEBSITE (CRUD) -->
        <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm flex flex-col justify-between space-y-6" x-data="{ logoPreview: '{{ $siteLogoUrl }}' }">
            <div>
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-100 border border-emerald-300 flex items-center justify-center font-black text-emerald-800 text-lg">
                            🏫
                        </div>
                        <div>
                            <h3 class="font-extrabold text-slate-900 text-sm">Logo Website (Utama)</h3>
                            <p class="text-[11px] text-slate-600 font-medium">Header, Navigation, Sidebar & Branding</p>
                        </div>
                    </div>

                    @if($isCustomLogo)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">
                            Custom Logo Active
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-slate-100 text-slate-600 border border-slate-200">
                            Logo Bawaan Sistem
                        </span>
                    @endif
                </div>

                <!-- Preview Display -->
                <div class="mt-6 flex flex-col items-center justify-center p-6 bg-slate-50 border border-slate-200 rounded-2xl relative overflow-hidden group">
                    <div class="w-32 h-32 flex items-center justify-center bg-white border border-slate-200 rounded-2xl p-3 shadow-md relative">
                        <img :src="logoPreview" alt="Preview Logo Website" class="max-w-full max-h-full object-contain">
                    </div>
                    <div class="mt-3 text-center">
                        <span class="text-[11px] font-bold text-slate-600">Tampilan Logo Saat Ini</span>
                    </div>
                </div>

                <!-- Form Upload Logo -->
                <form action="{{ route('admin.settings.logo.update') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-2">Unggah Logo Baru</label>
                        <input type="file" name="logo" accept="image/*" @change="
                            const file = $event.target.files[0];
                            if(file) {
                                const reader = new FileReader();
                                reader.onload = (e) => logoPreview = e.target.result;
                                reader.readAsDataURL(file);
                            }
                        " class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-emerald-700 file:text-white hover:file:bg-emerald-800 file:cursor-pointer border border-slate-200 rounded-xl bg-slate-50 focus:outline-none">
                        <p class="text-[11px] text-slate-600 mt-1.5 font-medium">Format: PNG, SVG, JPG, WEBP. Maksimal 2 MB.</p>
                    </div>

                    <button type="submit" class="w-full py-3 px-4 bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold rounded-xl shadow-md shadow-emerald-700/20 text-xs flex items-center justify-center space-x-2 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        <span>Simpan Logo Website</span>
                    </button>
                </form>
            </div>

            <!-- Reset to Default Button -->
            @if($isCustomLogo)
                <div class="pt-4 border-t border-slate-100">
                    <form action="{{ route('admin.settings.logo.destroy') }}" method="POST" onsubmit="return confirm('Kembalikan logo website ke logo standar sekolah SMKN 1 Bangsri?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full py-2.5 px-4 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold rounded-xl transition-all flex items-center justify-center space-x-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            <span>Reset Ke Logo Bawaan</span>
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <!-- CARD 2: BACKGROUND LOGIN (CRUD) -->
        <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm flex flex-col justify-between space-y-6" x-data="{ bgPreview: '{{ $loginBgUrl }}' }">
            <div>
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-100 border border-emerald-300 flex items-center justify-center font-black text-emerald-800 text-lg">
                            🌅
                        </div>
                        <div>
                            <h3 class="font-extrabold text-slate-900 text-sm">Background Login (Kustom)</h3>
                            <p class="text-[11px] text-slate-600 font-medium">Tampilan Latar Belakang Halaman Login Portal</p>
                        </div>
                    </div>

                    @if($isCustomLoginBg)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300">
                            Custom Wallpaper Active
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-slate-100 text-slate-600 border border-slate-200">
                            Watermark Bawaan
                        </span>
                    @endif
                </div>

                <!-- Preview Display -->
                <div class="mt-6 flex flex-col items-center justify-center p-4 bg-slate-100 border border-slate-200 rounded-2xl relative overflow-hidden h-44 shadow-inner">
                    <template x-if="bgPreview">
                        <img :src="bgPreview" alt="Preview Background Login" class="w-full h-full object-cover rounded-xl shadow">
                    </template>
                    <template x-if="!bgPreview">
                        <div class="flex flex-col items-center justify-center text-slate-400 p-4 text-center">
                            <span class="text-3xl mb-1">🖼️</span>
                            <span class="text-xs font-bold text-slate-600">Background Bawaan (Watermark Logo Sekolah)</span>
                            <span class="text-[10px] text-slate-600 mt-0.5">Unggah gambar wallpaper untuk mengganti tampilan latar belakang login.</span>
                        </div>
                    </template>
                </div>

                <!-- Form Upload Login Background -->
                <form action="{{ route('admin.settings.login-bg.update') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-2">Unggah Background Login Baru</label>
                        <input type="file" name="login_bg" accept="image/*" @change="
                            const file = $event.target.files[0];
                            if(file) {
                                const reader = new FileReader();
                                reader.onload = (e) => bgPreview = e.target.result;
                                reader.readAsDataURL(file);
                            }
                        " class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-emerald-700 file:text-white hover:file:bg-emerald-800 file:cursor-pointer border border-slate-200 rounded-xl bg-slate-50 focus:outline-none">
                        <p class="text-[11px] text-slate-600 mt-1.5 font-medium">Format: JPG, PNG, WEBP, SVG. Maksimal 5 MB.</p>
                    </div>

                    <button type="submit" class="w-full py-3 px-4 bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold rounded-xl shadow-md shadow-emerald-700/20 text-xs flex items-center justify-center space-x-2 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        <span>Simpan Background Login</span>
                    </button>
                </form>
            </div>

            <!-- Reset to Default Button -->
            @if($isCustomLoginBg)
                <div class="pt-4 border-t border-slate-100">
                    <form action="{{ route('admin.settings.login-bg.destroy') }}" method="POST" onsubmit="return confirm('Hapus gambar background kustom dan kembalikan ke background bawaan?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full py-2.5 px-4 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold rounded-xl transition-all flex items-center justify-center space-x-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            <span>Hapus & Reset Background Login</span>
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <!-- Info Section / Live Simulation Card -->
    <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-4">
        <div class="flex items-center space-x-3 pb-3 border-b border-slate-100">
            <span class="text-xl">ℹ️</span>
            <div>
                <h3 class="font-extrabold text-slate-900 text-sm">Informasi Pemisahan Identitas Visual</h3>
                <p class="text-xs text-slate-600 font-medium">Memahami perbedaan penggunaan Logo Website vs Background Login</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-700 font-medium">
            <div class="p-4 bg-emerald-50/70 border border-emerald-200 rounded-2xl space-y-2">
                <div class="font-black text-emerald-950 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                    <span>1. Logo Website (Website Branding Logo)</span>
                </div>
                <p class="text-slate-600 leading-relaxed">
                    Logo ini tampil di seluruh layout aplikasi, header marquee, menu sidebar admin, portal siswa, portal guru, portal DUDI, dan footer copyright.
                </p>
            </div>

            <div class="p-4 bg-emerald-50/70 border border-emerald-200 rounded-2xl space-y-2">
                <div class="font-black text-emerald-950 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                    <span>2. Background Login (Login Wallpapers)</span>
                </div>
                <p class="text-slate-600 leading-relaxed">
                    Latar belakang ini tampil secara eksklusif hanya pada halaman <code class="px-1 py-0.5 bg-white border border-emerald-300 rounded font-bold text-emerald-800">/login</code>. Berfungsi menciptakan kesan visual profesional tanpa memengaruhi logo utama website.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
