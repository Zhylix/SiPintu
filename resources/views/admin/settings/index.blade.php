@extends('layouts.app', ['headerTitle' => 'Pengaturan Logo & Icon PWA'])

@section('content')
<div class="space-y-6 min-w-0 max-w-full">
    <!-- Header Section -->
    <div class="bg-white border border-slate-200 rounded-2xl p-4 sm:p-6 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4 min-w-0 max-w-full">
        <div>
            <div class="flex items-center space-x-2">
                <span class="p-2 bg-emerald-100 text-emerald-800 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </span>
                <h2 class="text-xl font-black text-emerald-950 tracking-tight">Pengaturan Logo & Icon PWA</h2>
            </div>
            <p class="text-xs text-slate-600 font-medium mt-1">
                Kelola identitas visual Gateway SMKN 1 Bangsri. Logo website tersambung langsung dan sinkron dengan Icon aplikasi PWA di Layar Utama HP dan Tab Browser.
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

    <!-- Main Grid: Logo Website, Icon PWA, & Background Login Forms -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 min-w-0 max-w-full">
        
        <!-- CARD 1: LOGO WEBSITE (CRUD) -->
        <div class="bg-white border border-slate-200 rounded-3xl p-4 sm:p-6 shadow-sm flex flex-col justify-between space-y-6 min-w-0 max-w-full" x-data="{ logoPreview: '{{ $siteLogoUrl }}' }">
            <div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 gap-3">
                    <div class="flex items-center space-x-3 min-w-0 flex-1">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-100 border border-emerald-300 flex items-center justify-center font-black text-emerald-800 shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5m0 0h4m-4 0V11m0 0h4m-4 0H9m4 0V5m-4 6V5m0 0H7m2 0h4"></path></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-extrabold text-slate-900 text-sm truncate">Logo Website</h3>
                            <p class="text-[11px] text-slate-600 font-medium truncate">Header, Sidebar, Login & Branding Utama</p>
                        </div>
                    </div>

                    @if($isCustomLogo)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300 shrink-0">
                            Custom Logo
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-slate-100 text-slate-600 border border-slate-200 shrink-0">
                            Logo Bawaan
                        </span>
                    @endif
                </div>

                <!-- Preview Display -->
                <div class="mt-6 flex flex-col items-center justify-center p-6 bg-slate-50 border border-slate-200 rounded-2xl relative overflow-hidden group">
                    <div class="w-32 h-32 flex items-center justify-center bg-white border border-slate-200 rounded-2xl p-3 shadow-md relative">
                        <img :src="logoPreview" alt="Preview Logo Website" class="max-w-full max-h-full object-contain">
                    </div>
                    <div class="mt-3 text-center">
                        <span class="text-[11px] font-bold text-slate-600">Logo Website Aktif</span>
                    </div>
                </div>

                <!-- Form Upload Logo -->
                <form action="{{ route('admin.settings.logo.update') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-2">Unggah Logo Website Baru</label>
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

                    <button type="submit" class="w-full py-3 px-4 bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold rounded-xl shadow-md shadow-emerald-700/20 text-xs flex items-center justify-center space-x-2 transition-all cursor-pointer">
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
                        <button type="submit" class="w-full py-2.5 px-4 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold rounded-xl transition-all flex items-center justify-center space-x-1.5 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            <span>Reset Ke Logo Bawaan</span>
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <!-- CARD 2: ICON PWA & FAVICON (CRUD & SYNC) -->
        <div class="bg-white border border-slate-200 rounded-3xl p-4 sm:p-6 shadow-sm flex flex-col justify-between space-y-6 min-w-0 max-w-full" x-data="{ iconPreview: '{{ $siteIconUrl }}' }">
            <div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 gap-3">
                    <div class="flex items-center space-x-3 min-w-0 flex-1">
                        <div class="w-10 h-10 rounded-2xl bg-teal-100 border border-teal-300 flex items-center justify-center font-black text-teal-800 shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-extrabold text-slate-900 text-sm truncate">Icon Aplikasi & PWA</h3>
                            <p class="text-[11px] text-slate-600 font-medium truncate">Layar Utama HP, Splash, Manifest & Tab</p>
                        </div>
                    </div>

                    @if($isIconConnectedToLogo)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center gap-1 shrink-0">
                            <svg class="w-3 h-3 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                            <span>Tersambung ke Logo</span>
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-100 text-amber-900 border border-amber-300 shrink-0">
                            Ikon Kustom Mandiri
                        </span>
                    @endif
                </div>

                <!-- Simulation Preview: Android Squircle & Tab Favicon -->
                <div class="mt-6 flex flex-col items-center justify-center p-6 bg-slate-50 border border-slate-200 rounded-2xl relative overflow-hidden space-y-3">
                    <div class="flex items-center justify-center gap-6">
                        <!-- Android Adaptive Squircle Icon Simulation -->
                        <div class="flex flex-col items-center space-y-1.5">
                            <div class="w-20 h-20 rounded-[22px] bg-white border border-slate-200/90 p-3 shadow-lg shadow-emerald-900/10 flex items-center justify-center relative group">
                                <img :src="iconPreview" alt="Simulasi Ikon Layar HP" class="max-w-full max-h-full object-contain">
                                <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-emerald-700 text-white rounded-full flex items-center justify-center shadow-xs">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                                </div>
                            </div>
                            <span class="text-[10px] font-extrabold text-slate-700">Layar Utama HP</span>
                        </div>

                        <!-- Browser Tab Simulation -->
                        <div class="flex flex-col items-center space-y-1.5">
                            <div class="px-3 py-2 bg-white border border-slate-200 rounded-xl shadow-xs flex items-center space-x-2">
                                <img :src="iconPreview" alt="Favicon Tab" class="w-4 h-4 object-contain">
                                <span class="text-[11px] font-bold text-slate-800">SiPintu</span>
                            </div>
                            <span class="text-[10px] font-extrabold text-slate-700">Tab Browser</span>
                        </div>
                    </div>

                    <div class="text-center">
                        <span class="text-[11px] font-bold text-slate-600">
                            @if($isIconConnectedToLogo)
                                &check; Ikon PWA tersambung & sama dengan Logo Website
                            @else
                                Ikon PWA menggunakan berkas kustom terpisah
                            @endif
                        </span>
                    </div>
                </div>

                <!-- Form Upload Dedicated Icon (If user wants specific app icon) -->
                <form action="{{ route('admin.settings.icon.update') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider mb-2">Unggah Ikon PWA Mandiri (Opsional)</label>
                        <input type="file" name="icon" accept="image/*" @change="
                            const file = $event.target.files[0];
                            if(file) {
                                const reader = new FileReader();
                                reader.onload = (e) => iconPreview = e.target.result;
                                reader.readAsDataURL(file);
                            }
                        " class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-extrabold file:bg-teal-700 file:text-white hover:file:bg-teal-800 file:cursor-pointer border border-slate-200 rounded-xl bg-slate-50 focus:outline-none">
                        <p class="text-[11px] text-slate-600 mt-1.5 font-medium">Format: PNG, SVG, JPG, WEBP persegi (1:1). Maksimal 2 MB.</p>
                    </div>

                    <button type="submit" class="w-full py-3 px-4 bg-teal-700 hover:bg-teal-800 text-white font-extrabold rounded-xl shadow-md shadow-teal-700/20 text-xs flex items-center justify-center space-x-2 transition-all cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                        <span>Simpan Icon PWA Khusus</span>
                    </button>
                </form>
            </div>

            <!-- Connect to Logo / Reset Button -->
            <div class="pt-4 border-t border-slate-100">
                @if(!$isIconConnectedToLogo)
                    <form action="{{ route('admin.settings.icon.destroy') }}" method="POST" onsubmit="return confirm('Sambungkan kembali icon PWA dengan logo website?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full py-2.5 px-4 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs font-bold rounded-xl transition-all flex items-center justify-center space-x-2 cursor-pointer shadow-xs">
                            <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                            <span>Sambungkan Kembali dengan Logo Website</span>
                        </button>
                    </form>
                @else
                    <form action="{{ route('admin.settings.icon.destroy') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full py-2.5 px-4 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-xs font-bold rounded-xl transition-all flex items-center justify-center space-x-2 cursor-pointer">
                            <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            <span>Sinkronkan Ulang dari Logo Website</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- CARD 3: BACKGROUND LOGIN (CRUD) -->
        <div class="bg-white border border-slate-200 rounded-3xl p-4 sm:p-6 shadow-sm flex flex-col justify-between space-y-6 min-w-0 max-w-full" x-data="{ bgPreview: '{{ $loginBgUrl }}' }">
            <div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 gap-3">
                    <div class="flex items-center space-x-3 min-w-0 flex-1">
                        <div class="w-10 h-10 rounded-2xl bg-emerald-100 border border-emerald-300 flex items-center justify-center font-black text-emerald-800 shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="font-extrabold text-slate-900 text-sm truncate">Background Login (Kustom)</h3>
                            <p class="text-[11px] text-slate-600 font-medium truncate">Tampilan Latar Belakang Halaman Login Portal</p>
                        </div>
                    </div>

                    @if($isCustomLoginBg)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-300 shrink-0">
                            Wallpaper Aktif
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black bg-slate-100 text-slate-600 border border-slate-200 shrink-0">
                            Logo Bawaan
                        </span>
                    @endif
                </div>

                <!-- Preview Display -->
                <div class="mt-6 flex flex-col items-center justify-center p-6 bg-slate-50 border border-slate-200 rounded-2xl relative overflow-hidden group">
                    <div class="w-32 h-32 flex items-center justify-center bg-white border border-slate-200 rounded-2xl p-3 shadow-md relative">
                        <template x-if="bgPreview">
                            <img :src="bgPreview" alt="Preview Background Login" class="max-w-full max-h-full object-contain">
                        </template>
                        <template x-if="!bgPreview">
                            <div class="flex flex-col items-center justify-center text-slate-400 text-center">
                                <svg class="w-6 h-6 mb-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <span class="text-[10px] font-bold text-slate-500">Logo Bawaan</span>
                            </div>
                        </template>
                    </div>
                    <div class="mt-3 text-center">
                        <span class="text-[11px] font-bold text-slate-600">Background Login Saat Ini</span>
                    </div>
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

                    <button type="submit" class="w-full py-3 px-4 bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold rounded-xl shadow-md shadow-emerald-700/20 text-xs flex items-center justify-center space-x-2 transition-all cursor-pointer">
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
                        <button type="submit" class="w-full py-2.5 px-4 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold rounded-xl transition-all flex items-center justify-center space-x-1.5 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            <span>Hapus & Reset Background Login</span>
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <!-- Info Section / Live Simulation Card -->
    <div class="bg-white border border-slate-200 rounded-3xl p-4 sm:p-6 shadow-sm space-y-4 min-w-0 max-w-full">
        <div class="flex items-center space-x-3 pb-3 border-b border-slate-100">
            <div class="p-2 bg-emerald-100 text-emerald-800 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <h3 class="font-extrabold text-slate-900 text-sm">Informasi Koneksi Identitas Visual & PWA</h3>
                <p class="text-xs text-slate-600 font-medium">Bagaimana Logo Website dan Icon Aplikasi PWA Bekerja Secara Terpadu</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-slate-700 font-medium">
            <div class="p-4 bg-emerald-50/70 border border-emerald-200 rounded-2xl space-y-2">
                <div class="font-black text-emerald-950 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                    <span>1. Logo Website (Branding Utama)</span>
                </div>
                <p class="text-slate-600 leading-relaxed">
                    Logo ini tampil di seluruh layout website portal: baris marquee atas, sidebar admin, portal guru/siswa/DUDI, serta formulir login.
                </p>
            </div>

            <div class="p-4 bg-teal-50/70 border border-teal-200 rounded-2xl space-y-2">
                <div class="font-black text-teal-950 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-teal-600"></span>
                    <span>2. Icon PWA (Tersambung Otomatis)</span>
                </div>
                <p class="text-slate-600 leading-relaxed">
                    Ikon PWA secara bawaan <strong>tersambung langsung dengan Logo Website</strong>. Setiap kali Logo Website diperbarui, sistem otomatis menghasilkan varian ikon 72px hingga 512px, ikon adaptif Android, dan apple-touch-icon sehingga tampilannya persis sama.
                </p>
            </div>

            <div class="p-4 bg-emerald-50/70 border border-emerald-200 rounded-2xl space-y-2">
                <div class="font-black text-emerald-950 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                    <span>3. Background Login Portal</span>
                </div>
                <p class="text-slate-600 leading-relaxed">
                    Gambar latar belakang dekoratif khusus halaman <code class="px-1 py-0.5 bg-white border border-emerald-300 rounded font-bold text-emerald-800">/login</code> tanpa mengubah logo resmi sekolah.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
