@extends('layouts.app', ['headerTitle' => 'Application Registry'])

@section('content')
<div class="space-y-6" x-data="{
    viewMode: localStorage.getItem('sipintu_apps_view_mode') || 'grid',
    showInfoBanner: localStorage.getItem('sipintu_apps_infobanner') !== 'closed',
    copiedKey: null,
    detailModalOpen: false,
    selectedApp: null,
    copyToClipboard(text, key) {
        if (!text) return;
        navigator.clipboard.writeText(text).then(() => {
            this.copiedKey = key;
            setTimeout(() => {
                if (this.copiedKey === key) this.copiedKey = null;
            }, 2000);
        });
    },
    openDetail(appData) {
        this.selectedApp = appData;
        this.detailModalOpen = true;
    },
    closeDetail() {
        this.detailModalOpen = false;
        this.selectedApp = null;
    },
    setViewMode(mode) {
        this.viewMode = mode;
        localStorage.setItem('sipintu_apps_view_mode', mode);
    }
}">

    <!-- Top Header: Title & Action Buttons -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <h2 class="text-xl font-black text-emerald-950 tracking-tight">Registry Aplikasi Eksternal & OAuth Clients</h2>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">
                    SSO Gateway
                </span>
            </div>
            <p class="text-xs text-slate-600 font-medium mt-1">Daftarkan aplikasi downstream, kelola kredensial OAuth 2.0, serta pantau status koneksi dan hak akses role.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2 sm:gap-3 w-full sm:w-auto">
            <a href="{{ route('admin.categories.index') }}" class="px-3.5 py-2 bg-slate-100 hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 text-xs font-bold rounded-xl transition-all border border-slate-200 flex items-center space-x-2 flex-1 sm:flex-none justify-center shrink-0">
                <svg class="w-4 h-4 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <span class="whitespace-nowrap">Kelola Kategori</span>
            </a>

            <a href="{{ route('admin.applications.create') }}" class="px-3.5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-700/20 flex items-center space-x-2 flex-1 sm:flex-none justify-center shrink-0 group">
                <svg class="w-4 h-4 shrink-0 group-hover:rotate-90 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span class="whitespace-nowrap">Tambah Aplikasi Baru</span>
            </a>
        </div>
    </div>

    <!-- Alert: Client Secret Baru Terbuat (One-time Display) -->
    @if(session('new_client_secret'))
        <div class="p-5 rounded-2xl bg-gradient-to-r from-amber-50 to-amber-100/60 border-2 border-amber-300 text-slate-800 space-y-3 max-w-full overflow-hidden shadow-sm">
            <div class="flex items-center space-x-3 text-amber-900 font-black text-sm">
                <div class="w-7 h-7 rounded-xl bg-amber-200 text-amber-800 flex items-center justify-center shrink-0 shadow-2xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <span>PERHATIAN: Salin Client Secret Aplikasi {{ session('new_client_name') }}</span>
            </div>
            <p class="text-xs text-slate-700 font-medium leading-relaxed">
                Simpan Client Secret ini sekarang di file <code>.env</code> aplikasi downstream Anda. Demi keamanan, Client Secret ini telah di-hash di database dan tidak akan ditampilkan lagi setelah Anda meninggalkan halaman ini.
            </p>
            <div class="p-3 bg-white rounded-xl border border-amber-300 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 font-mono text-xs sm:text-sm text-emerald-900 font-bold max-w-full overflow-hidden shadow-inner">
                <span class="break-all font-mono select-all">{{ session('new_client_secret') }}</span>
                <button type="button" @click="copyToClipboard('{{ session('new_client_secret') }}', 'new_secret')" 
                        class="text-xs font-sans px-3.5 py-1.5 rounded-lg font-extrabold shrink-0 self-end sm:self-auto transition-all inline-flex items-center gap-1.5"
                        :class="copiedKey === 'new_secret' ? 'bg-emerald-600 text-white border border-emerald-700 shadow-xs' : 'bg-emerald-100 hover:bg-emerald-200 text-emerald-900 border border-emerald-300'">
                    <template x-if="copiedKey === 'new_secret'">
                        <span class="inline-flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            Tersalin!
                        </span>
                    </template>
                    <template x-if="copiedKey !== 'new_secret'">
                        <span class="inline-flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                            Salin Secret
                        </span>
                    </template>
                </button>
            </div>
        </div>
    @endif

    <!-- Telemetry & Status Summary Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-2xs hover:border-slate-300 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Aplikasi</span>
                <span class="w-8 h-8 rounded-xl bg-slate-100 text-slate-700 flex items-center justify-center font-black text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-slate-900">{{ $stats['total'] ?? $applications->total() }}</span>
                <span class="text-[11px] text-slate-500 font-semibold">klien terdaftar</span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-emerald-200/80 shadow-2xs hover:border-emerald-300 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider">Aktif & Terkoneksi</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-black text-xs">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-emerald-950">{{ $stats['active'] ?? 0 }}</span>
                <span class="text-[11px] text-emerald-700 font-semibold">siap melayani SSO</span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-amber-200/80 shadow-2xs hover:border-amber-300 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-amber-800 uppercase tracking-wider">Maintenance</span>
                <span class="w-8 h-8 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-black text-xs">
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-amber-950">{{ $stats['maintenance'] ?? 0 }}</span>
                <span class="text-[11px] text-amber-700 font-semibold">dalam pemeliharaan</span>
            </div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-rose-200/80 shadow-2xs hover:border-rose-300 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-bold text-rose-800 uppercase tracking-wider">Inaktif / Terputus</span>
                <span class="w-8 h-8 rounded-xl bg-rose-50 text-rose-700 flex items-center justify-center font-black text-xs">
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500"></span>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-black text-rose-950">{{ $stats['inactive'] ?? 0 }}</span>
                <span class="text-[11px] text-rose-700 font-semibold">perlu perhatian</span>
            </div>
        </div>
    </div>

    <!-- Standar Integrasi Klien SSO SiPintu (Collapsible Banner) -->
    <div x-show="showInfoBanner" x-transition class="p-4 sm:p-5 rounded-2xl bg-gradient-to-br from-slate-50 to-emerald-50/40 border border-slate-200 shadow-2xs space-y-3.5">
        <div class="flex items-center justify-between gap-2 border-b border-slate-200/80 pb-3">
            <div class="flex items-center space-x-2.5 font-black text-sm tracking-tight text-emerald-950">
                <svg class="w-5 h-5 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>Patokan Standar Integrasi Klien SSO SiPintu</span>
            </div>
            <div class="flex items-center space-x-2">
                <span class="hidden sm:inline-block text-[10px] font-mono bg-white text-slate-700 px-2.5 py-0.5 rounded-full border border-slate-300 font-bold">
                    OAuth 2.0 / OpenID Connect Specification
                </span>
                <button type="button" @click="showInfoBanner = false; localStorage.setItem('sipintu_apps_infobanner', 'closed')" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-white" title="Tutup panduan ini">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
            <div class="bg-white p-3.5 rounded-xl border border-slate-200/80 shadow-2xs">
                <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-emerald-100 text-emerald-900 border border-emerald-300 inline-block mb-1">ONLINE / CONNECTED</span>
                <p class="text-slate-600 mt-1 text-[11px] leading-relaxed">Status `ACTIVE`, Client ID terverifikasi, dan Health Check URL HTTP 200 merespons.</p>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-slate-200/80 shadow-2xs">
                <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-rose-100 text-rose-900 border border-rose-300 inline-block mb-1">OFFLINE / DISCONNECTED</span>
                <p class="text-slate-600 mt-1 text-[11px] leading-relaxed">Status `INACTIVE` atau URL Health Check aplikasi tidak dapat dihubungi.</p>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-slate-200/80 shadow-2xs">
                <span class="text-amber-700 font-extrabold block text-[11px] uppercase">Autentikasi Header API</span>
                <p class="text-slate-600 mt-1 text-[11px] leading-relaxed">Gunakan <code>X-Client-ID</code> & <code>X-Client-Secret</code> untuk Server-to-Server REST Gateway.</p>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-slate-200/80 shadow-2xs">
                <span class="text-emerald-700 font-extrabold block text-[11px] uppercase">Target Response Time</span>
                <p class="text-slate-600 mt-1 text-[11px] leading-relaxed">Latensi ideal di bawah 200 ms untuk menjaga kenyamanan Single Sign-On pengguna.</p>
            </div>
        </div>
    </div>

    <!-- Filter, Search & View Mode Switcher Toolbar -->
    <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-2xs space-y-3">
        <form method="GET" action="{{ route('admin.applications.index') }}" class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3 w-full">
            <!-- Search Input -->
            <div class="relative flex-1 min-w-0">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama aplikasi, client ID, URL, deskripsi..." 
                       class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-semibold placeholder-slate-400 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all shadow-2xs">
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>

            <!-- Filters Group -->
            <div class="flex flex-wrap sm:flex-nowrap items-center gap-2">
                <!-- Category Filter -->
                <select name="category_id" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 text-xs text-slate-900 font-semibold rounded-xl px-3 py-2.5 focus:outline-none focus:border-emerald-600 cursor-pointer shadow-2xs">
                    <option value="all">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>

                <!-- Status Filter -->
                <select name="status" onchange="this.form.submit()" class="bg-slate-50 border border-slate-200 text-xs text-slate-900 font-semibold rounded-xl px-3 py-2.5 focus:outline-none focus:border-emerald-600 cursor-pointer shadow-2xs">
                    <option value="all">Semua Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active (Online)</option>
                    <option value="maintenance" {{ request('status') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive (Offline)</option>
                </select>

                <!-- Submit Button -->
                <button type="submit" class="px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-xs flex items-center gap-1.5 shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <span>Filter</span>
                </button>

                @if(request()->anyFilled(['search', 'category_id', 'status']))
                    <a href="{{ route('admin.applications.index') }}" class="px-3.5 py-2.5 bg-slate-100 hover:bg-rose-50 hover:text-rose-700 text-slate-600 text-xs font-bold rounded-xl border border-slate-200 flex items-center justify-center shrink-0 transition-colors" title="Bersihkan Filter">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        <span>Reset</span>
                    </a>
                @endif
            </div>

            <!-- View Mode Switcher Toggle: Cards vs Table -->
            <div class="flex items-center space-x-1.5 border-t lg:border-t-0 lg:border-l border-slate-200 pt-2 lg:pt-0 lg:pl-3 shrink-0">
                <span class="text-[11px] font-bold text-slate-500 mr-1 hidden sm:inline-block">Tampilan:</span>
                <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200">
                    <button type="button" @click="setViewMode('grid')" 
                            :class="viewMode === 'grid' ? 'bg-white text-emerald-900 shadow-xs font-extrabold' : 'text-slate-500 hover:text-slate-900'"
                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5" title="Tampilan Kartu Visual (Direkomendasikan)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        <span class="hidden sm:inline">Kartu</span>
                    </button>
                    <button type="button" @click="setViewMode('table')" 
                            :class="viewMode === 'table' ? 'bg-white text-emerald-900 shadow-xs font-extrabold' : 'text-slate-500 hover:text-slate-900'"
                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5" title="Tampilan Tabel Rapi">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                        <span class="hidden sm:inline">Tabel</span>
                    </button>
                </div>
            </div>
        </form>

        <!-- Showing Result Counter -->
        <div class="flex items-center justify-between text-[11px] text-slate-500 font-medium pt-1 border-t border-slate-100">
            <span>Menampilkan <strong class="text-slate-800 font-extrabold">{{ $applications->count() }}</strong> dari <strong class="text-slate-800 font-extrabold">{{ $applications->total() }}</strong> aplikasi terdaftar</span>
            @if(request('search'))
                <span class="text-emerald-800 font-bold bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                    Hasil untuk kata kunci "{{ request('search') }}"
                </span>
            @endif
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 1. TAMPILAN KARTU VISUAL (GRID CARDS VIEW) -->
    <!-- Solusi bebas scroll horizontal: Seluruh info terlihat rapi dalam 1 kartu! -->
    <!-- ========================================================================= -->
    <div x-show="viewMode === 'grid'" x-transition class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($applications as $app)
                @php
                    $isConnected = $app->status === 'active' && ($app->last_health_status !== 'offline');
                    $appData = [
                        'id' => $app->id,
                        'name' => $app->name,
                        'slug' => $app->slug,
                        'category' => $app->category?->name ?? 'Umum',
                        'description' => $app->description ?? 'Tidak ada deskripsi tambahan.',
                        'base_url' => $app->base_url,
                        'redirect_uri' => $app->redirect_uri,
                        'logout_uri' => $app->logout_uri,
                        'client_id' => $app->client_id,
                        'scopes' => $app->scopes,
                        'status' => $app->status,
                        'health_check_url' => $app->health_check_url,
                        'last_health_status' => $app->last_health_status,
                        'last_connected_at' => $app->last_connected_at ? $app->last_connected_at->diffForHumans() : 'Belum pernah terkoneksi',
                        'last_connected_ip' => $app->last_connected_ip ?? '-',
                        'total_api_requests' => $app->total_api_requests ?? 0,
                        'roles' => $app->roles->map(fn($r) => $r->getDisplayName())->values(),
                        'logo_url' => $app->logo_url,
                        'edit_url' => route('admin.applications.edit', $app),
                        'diagnose_url' => route('admin.monitoring.index') . '?diagnose=' . $app->client_id,
                        'test_health_url' => route('admin.applications.test-health', $app),
                        'regenerate_secret_url' => route('admin.applications.regenerate-secret', $app),
                        'destroy_url' => route('admin.applications.destroy', $app),
                        'is_connected' => $isConnected,
                    ];
                @endphp

                <div class="group relative bg-white border border-slate-200 hover:border-emerald-500 rounded-2xl p-5 transition-all duration-300 hover:shadow-xl hover:shadow-emerald-950/5 flex flex-col justify-between space-y-4 overflow-hidden">
                    
                    <!-- Top Status Accent Bar -->
                    <div class="h-1.5 w-full absolute top-0 left-0 {{ $app->status === 'maintenance' ? 'bg-amber-400' : ($isConnected ? 'bg-emerald-500' : 'bg-rose-500') }}"></div>

                    <div class="space-y-3.5 w-full">
                        <!-- Card Header: Logo, Name, Category & Live Status -->
                        <div class="flex items-start justify-between gap-3 pt-1">
                            <div class="flex items-center space-x-3 min-w-0 flex-1">
                                @if($app->logo_url)
                                    <img src="{{ $app->logo_url }}" alt="{{ $app->name }}" class="w-12 h-12 rounded-xl object-cover border border-slate-200 shadow-2xs shrink-0 group-hover:scale-105 transition-transform">
                                @else
                                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-100 to-emerald-200 border border-emerald-300 flex items-center justify-center text-emerald-900 font-black text-base shrink-0 group-hover:scale-105 transition-transform shadow-2xs">
                                        {{ strtoupper(substr($app->name, 0, 2)) }}
                                    </div>
                                @endif

                                <div class="min-w-0 flex-1">
                                    <h3 class="font-black text-slate-900 text-sm group-hover:text-emerald-950 transition-colors line-clamp-1 break-words" title="{{ $app->name }}">
                                        {{ $app->name }}
                                    </h3>
                                    
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        @if($app->category)
                                            <span class="inline-flex items-center text-[10px] font-extrabold text-emerald-800 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200 whitespace-nowrap">
                                                {{ $app->category->name }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center text-[10px] font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200 whitespace-nowrap">
                                                Umum
                                            </span>
                                        @endif

                                        <span class="text-slate-300">•</span>
                                        <span class="text-[10px] font-mono text-slate-500 truncate" title="Slug: {{ $app->slug }}">{{ $app->slug }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Live Connection Badge -->
                            <div class="shrink-0">
                                @if($app->status === 'maintenance')
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1.5 shadow-2xs">
                                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                        <span>MAINTENANCE</span>
                                    </span>
                                @elseif($isConnected)
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-900 border border-emerald-300 inline-flex items-center gap-1.5 shadow-2xs">
                                        <span class="w-2 h-2 rounded-full bg-emerald-600 animate-ping"></span>
                                        <span>ONLINE</span>
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-100 text-rose-900 border border-rose-300 inline-flex items-center gap-1.5 shadow-2xs">
                                        <span class="w-2 h-2 rounded-full bg-rose-600"></span>
                                        <span>OFFLINE</span>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Description -->
                        <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed font-medium">
                            {{ $app->description ?: 'Aplikasi terintegrasi dengan SSO Gateway SMKN 1 Bangsri.' }}
                        </p>

                        <!-- Credentials & Endpoints Box -->
                        <div class="bg-slate-50/90 rounded-xl p-3 border border-slate-200/90 space-y-2 text-xs font-mono">
                            <!-- Client ID -->
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[11px] font-bold text-slate-500 font-sans">Client ID:</span>
                                <div class="flex items-center space-x-1.5 min-w-0">
                                    <code class="font-bold text-emerald-900 truncate max-w-[150px] select-all bg-white px-1.5 py-0.5 rounded border border-slate-200" title="{{ $app->client_id }}">{{ $app->client_id }}</code>
                                    <button type="button" @click="copyToClipboard('{{ $app->client_id }}', 'client_{{ $app->id }}')" 
                                            class="p-1 text-slate-400 hover:text-emerald-700 rounded hover:bg-white transition-colors" title="Salin Client ID">
                                        <template x-if="copiedKey === 'client_{{ $app->id }}'">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        </template>
                                        <template x-if="copiedKey !== 'client_{{ $app->id }}'">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                        </template>
                                    </button>
                                </div>
                            </div>

                            <!-- Base URL -->
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[11px] font-bold text-slate-500 font-sans">Base URL:</span>
                                <div class="flex items-center space-x-1.5 min-w-0">
                                    <a href="{{ $app->base_url }}" target="_blank" rel="noopener noreferrer" class="font-bold text-slate-800 hover:text-emerald-700 truncate max-w-[150px] inline-flex items-center gap-1 group/link" title="{{ $app->base_url }}">
                                        <span class="truncate">{{ $app->base_url }}</span>
                                        <svg class="w-3 h-3 text-slate-400 group-hover/link:text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                    </a>
                                    <button type="button" @click="copyToClipboard('{{ $app->base_url }}', 'url_{{ $app->id }}')" 
                                            class="p-1 text-slate-400 hover:text-emerald-700 rounded hover:bg-white transition-colors" title="Salin Base URL">
                                        <template x-if="copiedKey === 'url_{{ $app->id }}'">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        </template>
                                        <template x-if="copiedKey !== 'url_{{ $app->id }}'">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                        </template>
                                    </button>
                                </div>
                            </div>

                            <!-- Health Status -->
                            <div class="flex items-center justify-between gap-2 pt-1 border-t border-slate-200/80">
                                <span class="text-[11px] font-bold text-slate-500 font-sans">Health Check:</span>
                                @if($app->health_check_url)
                                    <span class="inline-flex items-center gap-1 font-sans text-[10px] font-extrabold px-2 py-0.5 rounded {{ $app->last_health_status === 'online' ? 'bg-emerald-100 text-emerald-900 border border-emerald-200' : 'bg-rose-100 text-rose-900 border border-rose-200' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $app->last_health_status === 'online' ? 'bg-emerald-600' : 'bg-rose-600' }}"></span>
                                        {{ $app->last_health_status === 'online' ? 'HTTP 200 OK' : 'OFFLINE' }}
                                    </span>
                                @else
                                    <span class="text-[10px] font-sans text-slate-400 font-medium">Belum dikonfigurasi</span>
                                @endif
                            </div>
                        </div>

                        <!-- Role Akses Diizinkan (Clean Horizontal Pills) -->
                        <div class="space-y-1.5">
                            <span class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 block">
                                Role Akses Diizinkan ({{ $app->roles->count() }}):
                            </span>
                            <div class="flex flex-wrap gap-1.5 max-h-20 overflow-y-auto pr-1">
                                @forelse($app->roles as $role)
                                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-black uppercase bg-emerald-50 text-emerald-800 border border-emerald-200 shadow-2xs whitespace-nowrap">
                                        {{ $role->getDisplayName() }}
                                    </span>
                                @empty
                                    <span class="text-[11px] text-rose-600 font-semibold italic">Belum ada role diizinkan</span>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Card Actions Footer -->
                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-2 w-full" x-data="{ menuOpen: false }">
                        <!-- Left: Detail Quick Modal Button -->
                        <button type="button" @click="openDetail(@js($appData))" 
                                class="px-2.5 py-1.5 text-[11px] font-extrabold text-slate-700 hover:text-emerald-800 hover:bg-slate-100 rounded-xl transition-all border border-slate-200 flex items-center gap-1 shrink-0" title="Buka ringkasan spesifikasi aplikasi">
                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            <span>Detail</span>
                        </button>

                        <!-- Right: Action Buttons Group -->
                        <div class="flex items-center gap-1.5">
                            <!-- Diagnosa SSO Button -->
                            <a href="{{ route('admin.monitoring.index') }}?diagnose={{ $app->client_id }}" 
                               class="px-2.5 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-xl text-[11px] font-extrabold inline-flex items-center gap-1 transition-all" title="Jalankan Diagnosa SSO Otomatis">
                                <svg class="w-3.5 h-3.5 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span>Diagnosa</span>
                            </a>

                            <!-- Edit Button -->
                            <a href="{{ route('admin.applications.edit', $app) }}" 
                               class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-xl text-[11px] font-extrabold inline-flex items-center gap-1 transition-all">
                                <svg class="w-3.5 h-3.5 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                <span>Edit</span>
                            </a>

                            <!-- Dropdown Menu Opsi Lainnya -->
                            <div class="relative">
                                <button type="button" @click="menuOpen = !menuOpen" @click.outside="menuOpen = false" 
                                        class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 rounded-xl text-xs font-bold transition-all" title="Opsi Tambahan">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                                </button>

                                <div x-show="menuOpen" x-transition:enter="transition ease-out duration-100"
                                     x-transition:enter-start="transform opacity-0 scale-95"
                                     x-transition:enter-end="transform opacity-100 scale-100"
                                     x-transition:leave="transition ease-in duration-75"
                                     x-transition:leave-start="transform opacity-100 scale-100"
                                     x-transition:leave-end="transform opacity-0 scale-95"
                                     class="absolute right-0 bottom-full mb-2 w-48 bg-white rounded-2xl shadow-xl border border-slate-200 py-1.5 z-30 divide-y divide-slate-100"
                                     x-cloak>
                                    
                                    <div class="py-1">
                                        <form action="{{ route('admin.applications.test-health', $app) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="w-full text-left px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 flex items-center space-x-2 transition-colors">
                                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                                <span>Test Health Check</span>
                                            </button>
                                        </form>

                                        <form action="{{ route('admin.applications.regenerate-secret', $app) }}" method="POST" onsubmit="return confirm('Buat ulang Client Secret untuk aplikasi {{ $app->name }}? Aplikasi eksternal harus memperbarui .env!')">
                                            @csrf
                                            <button type="submit" class="w-full text-left px-3.5 py-2 text-xs font-bold text-amber-700 hover:bg-amber-50 flex items-center space-x-2 transition-colors">
                                                <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                <span>Reset Secret Baru</span>
                                            </button>
                                        </form>
                                    </div>

                                    <div class="py-1">
                                        <form action="{{ route('admin.applications.destroy', $app) }}" method="POST" onsubmit="return confirm('PERINGATAN: Hapus aplikasi {{ $app->name }} secara permanen dari registry?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-full text-left px-3.5 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 flex items-center space-x-2 transition-colors">
                                                <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                <span>Hapus Aplikasi</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-2xl border-2 border-dashed border-slate-200 p-12 text-center space-y-4">
                    <div class="w-16 h-16 rounded-full bg-emerald-50 text-emerald-600 mx-auto flex items-center justify-center">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-base font-black text-slate-800">Tidak ada aplikasi ditemukan</h4>
                        <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">Tidak ada aplikasi yang cocok dengan kriteria pencarian atau filter yang Anda pilih saat ini.</p>
                    </div>
                    <div class="flex items-center justify-center gap-3 pt-2">
                        <a href="{{ route('admin.applications.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all">
                            Reset Filter
                        </a>
                        <a href="{{ route('admin.applications.create') }}" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-700/20">
                            Tambah Aplikasi Sekarang
                        </a>
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. TAMPILAN TABEL RAPI & KOMPAK (COMPACT TABLE VIEW) -->
    <!-- Solusi: Sticky nama aplikasi di kiri, role horizontal, aksi kompak -->
    <!-- ========================================================================= -->
    <div x-show="viewMode === 'table'" x-transition class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-2xs">
        <div class="overflow-x-auto w-full">
            <table class="w-full text-left text-xs min-w-[850px] border-collapse">
                <thead class="bg-emerald-50/70 text-emerald-950 uppercase font-black text-[10px] border-b border-slate-200 tracking-wider">
                    <tr>
                        <!-- Sticky First Column Header -->
                        <th class="px-4 py-3.5 sticky left-0 bg-emerald-50 z-20 shadow-xs">Nama Aplikasi</th>
                        <th class="px-4 py-3.5">Kategori</th>
                        <th class="px-4 py-3.5">Client ID</th>
                        <th class="px-4 py-3.5">Endpoint & Redirect URI</th>
                        <th class="px-4 py-3.5">Role Akses</th>
                        <th class="px-4 py-3.5">Status Koneksi SSO</th>
                        <th class="px-4 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200/80 bg-white font-sans">
                    @forelse($applications as $app)
                        @php
                            $isConnected = $app->status === 'active' && ($app->last_health_status !== 'offline');
                            $appData = [
                                'id' => $app->id,
                                'name' => $app->name,
                                'slug' => $app->slug,
                                'category' => $app->category?->name ?? 'Umum',
                                'description' => $app->description ?? 'Tidak ada deskripsi tambahan.',
                                'base_url' => $app->base_url,
                                'redirect_uri' => $app->redirect_uri,
                                'logout_uri' => $app->logout_uri,
                                'client_id' => $app->client_id,
                                'scopes' => $app->scopes,
                                'status' => $app->status,
                                'health_check_url' => $app->health_check_url,
                                'last_health_status' => $app->last_health_status,
                                'last_connected_at' => $app->last_connected_at ? $app->last_connected_at->diffForHumans() : 'Belum pernah terkoneksi',
                                'last_connected_ip' => $app->last_connected_ip ?? '-',
                                'total_api_requests' => $app->total_api_requests ?? 0,
                                'roles' => $app->roles->map(fn($r) => $r->getDisplayName())->values(),
                                'logo_url' => $app->logo_url,
                                'edit_url' => route('admin.applications.edit', $app),
                                'diagnose_url' => route('admin.monitoring.index') . '?diagnose=' . $app->client_id,
                                'test_health_url' => route('admin.applications.test-health', $app),
                                'regenerate_secret_url' => route('admin.applications.regenerate-secret', $app),
                                'destroy_url' => route('admin.applications.destroy', $app),
                                'is_connected' => $isConnected,
                            ];
                        @endphp
                        <tr class="hover:bg-emerald-50/40 transition-colors {{ $isConnected ? '' : 'bg-rose-50/20' }}" x-data="{ tableMenuOpen: false }">
                            <!-- STICKY APPLICATION NAME COLUMN (Tidak akan hilang saat digeser horizontal!) -->
                            <td class="px-4 py-3.5 whitespace-nowrap sticky left-0 bg-white z-10 shadow-xs border-r border-slate-100">
                                <div class="flex items-center space-x-3">
                                    @if($app->logo_url)
                                        <img src="{{ $app->logo_url }}" alt="{{ $app->name }}" class="w-9 h-9 rounded-xl object-cover border border-slate-200 shadow-2xs shrink-0">
                                    @else
                                        <div class="w-9 h-9 rounded-xl bg-emerald-100 border border-emerald-300 flex items-center justify-center text-emerald-900 font-black text-xs shrink-0">
                                            {{ strtoupper(substr($app->name, 0, 2)) }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="font-black text-slate-900 text-xs truncate max-w-[170px] hover:text-emerald-800 transition-colors" title="{{ $app->name }}">
                                            {{ $app->name }}
                                        </div>
                                        <div class="text-[10px] text-slate-500 font-mono truncate max-w-[170px]">
                                            {{ $app->slug }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Kategori -->
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                @if($app->category)
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        {{ $app->category->name }}
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        Umum
                                    </span>
                                @endif
                            </td>

                            <!-- Client ID with Copy Button -->
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <div class="flex items-center space-x-1.5">
                                    <span class="font-mono font-bold text-emerald-900 text-[11px] select-all truncate max-w-[130px] bg-slate-50 px-1.5 py-0.5 rounded border border-slate-200" title="{{ $app->client_id }}">
                                        {{ $app->client_id }}
                                    </span>
                                    <button type="button" @click="copyToClipboard('{{ $app->client_id }}', 't_client_{{ $app->id }}')" 
                                            class="p-1 text-slate-400 hover:text-emerald-700 rounded hover:bg-slate-100 transition-colors" title="Salin Client ID">
                                        <template x-if="copiedKey === 't_client_{{ $app->id }}'">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        </template>
                                        <template x-if="copiedKey !== 't_client_{{ $app->id }}'">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                        </template>
                                    </button>
                                </div>
                            </td>

                            <!-- Endpoints -->
                            <td class="px-4 py-3.5 text-slate-600">
                                <div class="font-mono text-xs font-bold text-slate-800 truncate max-w-[180px] flex items-center gap-1" title="{{ $app->base_url }}">
                                    <a href="{{ $app->base_url }}" target="_blank" rel="noopener noreferrer" class="hover:text-emerald-700 truncate">
                                        {{ $app->base_url }}
                                    </a>
                                </div>
                                <div class="text-[10px] text-slate-500 truncate max-w-[180px] font-mono font-medium mt-0.5" title="Redirect URI: {{ $app->redirect_uri }}">
                                    {{ $app->redirect_uri }}
                                </div>
                            </td>

                            <!-- Role Akses (Horizontal Badges) -->
                            <td class="px-4 py-3.5">
                                <div class="flex flex-wrap gap-1 max-w-[200px]">
                                    @forelse($app->roles->take(3) as $role)
                                        <span class="px-2 py-0.5 rounded text-[9px] font-black bg-emerald-50 text-emerald-800 border border-emerald-200 uppercase whitespace-nowrap">
                                            {{ $role->getDisplayName() }}
                                        </span>
                                    @empty
                                        <span class="text-[10px] text-slate-400 italic">None</span>
                                    @endforelse

                                    @if($app->roles->count() > 3)
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-slate-100 text-slate-600 border border-slate-200 cursor-help" 
                                              title="{{ $app->roles->pluck('display_name')->implode(', ') }}">
                                            +{{ $app->roles->count() - 3 }} lagi
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Status Koneksi SSO -->
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <div>
                                    @if($app->status === 'maintenance')
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-900 border border-amber-300 inline-flex items-center gap-1.5 shadow-2xs">
                                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                            <span>Maintenance</span>
                                        </span>
                                    @elseif($isConnected)
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-900 border border-emerald-300 inline-flex items-center gap-1.5 shadow-2xs">
                                            <span class="w-2 h-2 rounded-full bg-emerald-600 animate-ping"></span>
                                            <span>Connected</span>
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-rose-100 text-rose-900 border border-rose-300 inline-flex items-center gap-1.5 shadow-2xs">
                                            <span class="w-2 h-2 rounded-full bg-rose-600"></span>
                                            <span>Disconnected</span>
                                        </span>
                                    @endif
                                </div>
                                
                                @if($app->last_health_status)
                                    <div class="mt-1">
                                        <span class="text-[9px] font-mono font-bold uppercase {{ $app->last_health_status === 'online' ? 'text-emerald-700' : 'text-rose-700' }}">
                                            Health: {{ $app->last_health_status }}
                                        </span>
                                    </div>
                                @endif
                            </td>

                            <!-- Aksi Terkonsolidasi (Ringkas & Efisien) -->
                            <td class="px-4 py-3.5 whitespace-nowrap text-right">
                                <div class="inline-flex items-center space-x-1.5">
                                    <!-- Detail Modal Trigger -->
                                    <button type="button" @click="openDetail(@js($appData))" 
                                            class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-bold border border-slate-200 transition-colors" title="Lihat Detail Spesifikasi">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </button>

                                    <!-- Diagnosa SSO -->
                                    <a href="{{ route('admin.monitoring.index') }}?diagnose={{ $app->client_id }}" 
                                       class="px-2 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 border border-indigo-200 rounded-lg text-[11px] font-extrabold inline-flex items-center gap-1 transition-colors" title="Diagnosa SSO">
                                        <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <span>Diagnosa</span>
                                    </a>

                                    <!-- Edit -->
                                    <a href="{{ route('admin.applications.edit', $app) }}" 
                                       class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-lg text-[11px] font-extrabold inline-flex items-center gap-1 transition-colors">
                                        Edit
                                    </a>

                                    <!-- Dropdown Menu Opsi -->
                                    <div class="relative">
                                        <button type="button" @click="tableMenuOpen = !tableMenuOpen" @click.outside="tableMenuOpen = false" 
                                                class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg border border-slate-200 transition-colors" title="Opsi Tambahan">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                                        </button>

                                        <div x-show="tableMenuOpen" x-transition 
                                             class="absolute right-0 top-full mt-1.5 w-44 bg-white rounded-xl shadow-xl border border-slate-200 py-1 z-30 divide-y divide-slate-100 text-left"
                                             x-cloak>
                                            <div class="py-1">
                                                <form action="{{ route('admin.applications.test-health', $app) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="w-full text-left px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 flex items-center space-x-2">
                                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                                        <span>Test Health</span>
                                                    </button>
                                                </form>

                                                <form action="{{ route('admin.applications.regenerate-secret', $app) }}" method="POST" onsubmit="return confirm('Buat ulang Client Secret untuk aplikasi {{ $app->name }}?')">
                                                    @csrf
                                                    <button type="submit" class="w-full text-left px-3 py-1.5 text-xs font-bold text-amber-700 hover:bg-amber-50 flex items-center space-x-2">
                                                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                        <span>Reset Secret</span>
                                                    </button>
                                                </form>
                                            </div>

                                            <div class="py-1">
                                                <form action="{{ route('admin.applications.destroy', $app) }}" method="POST" onsubmit="return confirm('Hapus aplikasi {{ $app->name }} dari registry?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="w-full text-left px-3 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50 flex items-center space-x-2">
                                                        <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                        <span>Hapus Aplikasi</span>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-500 font-semibold space-y-2">
                                <div class="text-slate-400 font-medium">Belum ada aplikasi eksternal yang cocok dengan kriteria filter.</div>
                                <a href="{{ route('admin.applications.index') }}" class="inline-block text-xs font-bold text-emerald-700 hover:underline">Reset Filter</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination Links -->
    @if($applications->hasPages())
        <div class="pt-2">
            {{ $applications->links() }}
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- 3. POPUP MODAL DETAIL SPESIFIKASI APLIKASI (QUICK INSPECTION MODAL) -->
    <!-- Memberikan akses cepat ke seluruh konfigurasi OAuth tanpa pindah halaman -->
    <!-- ========================================================================= -->
    <div x-show="detailModalOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
         x-cloak>
        
        <div @click.away="closeDetail()"
             class="bg-white rounded-3xl shadow-2xl border border-slate-200 max-w-2xl w-full overflow-hidden space-y-0 transform transition-all">
            
            <!-- Modal Header -->
            <div class="px-6 py-5 bg-gradient-to-r from-slate-50 to-emerald-50/50 border-b border-slate-200 flex items-start justify-between gap-4">
                <div class="flex items-center space-x-3.5 min-w-0">
                    <template x-if="selectedApp && selectedApp.logo_url">
                        <img :src="selectedApp.logo_url" :alt="selectedApp.name" class="w-12 h-12 rounded-2xl object-cover border border-slate-200 shadow-2xs shrink-0">
                    </template>
                    <template x-if="selectedApp && !selectedApp.logo_url">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-100 border border-emerald-300 flex items-center justify-center text-emerald-900 font-black text-lg shrink-0">
                            <span x-text="selectedApp ? selectedApp.name.substring(0, 2).toUpperCase() : 'AP'"></span>
                        </div>
                    </template>

                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-black text-slate-900 truncate" x-text="selectedApp ? selectedApp.name : ''"></h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800 border border-emerald-300" x-text="selectedApp ? selectedApp.category : ''"></span>
                        </div>
                        <p class="text-xs text-slate-500 font-mono mt-0.5" x-text="selectedApp ? 'Slug: ' + selectedApp.slug : ''"></p>
                    </div>
                </div>

                <button type="button" @click="closeDetail()" class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-white border border-transparent hover:border-slate-200 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Modal Content Body -->
            <div class="p-6 space-y-5 text-xs text-slate-700 max-h-[70vh] overflow-y-auto" x-show="selectedApp">
                <!-- Status & Telemetry Highlight -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                        <span class="text-[10px] font-bold text-slate-500 uppercase block">Status Akun</span>
                        <span class="font-black text-slate-900 uppercase text-xs mt-0.5 block" x-text="selectedApp ? selectedApp.status : ''"></span>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                        <span class="text-[10px] font-bold text-slate-500 uppercase block">Health Check</span>
                        <span class="font-black uppercase text-xs mt-0.5 block" 
                              :class="selectedApp && selectedApp.last_health_status === 'online' ? 'text-emerald-700' : 'text-rose-700'"
                              x-text="selectedApp ? (selectedApp.last_health_status || 'Belum diuji') : ''"></span>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                        <span class="text-[10px] font-bold text-slate-500 uppercase block">Total API Request</span>
                        <span class="font-black text-emerald-950 text-xs mt-0.5 block" x-text="selectedApp ? selectedApp.total_api_requests : '0'"></span>
                    </div>
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                        <span class="text-[10px] font-bold text-slate-500 uppercase block">Terakhir Aktif</span>
                        <span class="font-bold text-slate-700 text-[11px] mt-0.5 block truncate" x-text="selectedApp ? selectedApp.last_connected_at : '-'"></span>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <span class="text-[11px] font-extrabold uppercase text-slate-500 block mb-1">Deskripsi Aplikasi:</span>
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-slate-700 leading-relaxed font-medium" x-text="selectedApp ? selectedApp.description : ''"></div>
                </div>

                <!-- OAuth Configuration -->
                <div class="space-y-3">
                    <span class="text-[11px] font-extrabold uppercase text-slate-500 block">Kredensial & Endpoint OAuth 2.0:</span>
                    
                    <div class="bg-slate-50 rounded-2xl border border-slate-200 divide-y divide-slate-200/80 font-mono text-xs">
                        <div class="p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <span class="text-slate-500 font-sans font-bold text-[11px]">Client ID:</span>
                            <div class="flex items-center space-x-2">
                                <span class="font-bold text-emerald-900 select-all" x-text="selectedApp ? selectedApp.client_id : ''"></span>
                                <button type="button" @click="copyToClipboard(selectedApp.client_id, 'modal_client')" class="text-slate-400 hover:text-emerald-700">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"></path></svg>
                                </button>
                            </div>
                        </div>

                        <div class="p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <span class="text-slate-500 font-sans font-bold text-[11px]">Base URL:</span>
                            <div class="flex items-center space-x-2">
                                <a :href="selectedApp ? selectedApp.base_url : '#'" target="_blank" class="font-bold text-emerald-800 hover:underline flex items-center gap-1">
                                    <span x-text="selectedApp ? selectedApp.base_url : ''"></span>
                                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                </a>
                            </div>
                        </div>

                        <div class="p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <span class="text-slate-500 font-sans font-bold text-[11px]">Redirect URI:</span>
                            <span class="font-bold text-slate-800 select-all break-all" x-text="selectedApp ? selectedApp.redirect_uri : ''"></span>
                        </div>

                        <div class="p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-1" x-show="selectedApp && selectedApp.logout_uri">
                            <span class="text-slate-500 font-sans font-bold text-[11px]">Logout URI:</span>
                            <span class="font-bold text-slate-800 select-all break-all" x-text="selectedApp ? selectedApp.logout_uri : '-'"></span>
                        </div>

                        <div class="p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-1" x-show="selectedApp && selectedApp.health_check_url">
                            <span class="text-slate-500 font-sans font-bold text-[11px]">Health Check URL:</span>
                            <span class="font-bold text-slate-800 select-all break-all" x-text="selectedApp ? selectedApp.health_check_url : '-'"></span>
                        </div>

                        <div class="p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                            <span class="text-slate-500 font-sans font-bold text-[11px]">OAuth Scopes:</span>
                            <span class="font-bold text-emerald-800 font-mono" x-text="selectedApp ? selectedApp.scopes : ''"></span>
                        </div>
                    </div>
                </div>

                <!-- Allowed Roles -->
                <div class="space-y-2">
                    <span class="text-[11px] font-extrabold uppercase text-slate-500 block">Daftar Role yang Diizinkan Mengakses:</span>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="roleName in (selectedApp ? selectedApp.roles : [])" :key="roleName">
                            <span class="px-3 py-1 rounded-lg text-xs font-black uppercase bg-emerald-100 text-emerald-900 border border-emerald-300" x-text="roleName"></span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Modal Footer Buttons -->
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex flex-wrap items-center justify-between gap-3">
                <button type="button" @click="closeDetail()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 rounded-xl font-bold text-xs transition-colors">
                    Tutup
                </button>

                <div class="flex items-center space-x-2" x-show="selectedApp">
                    <a :href="selectedApp ? selectedApp.diagnose_url : '#'" class="px-3.5 py-2 bg-indigo-700 hover:bg-indigo-800 text-white rounded-xl font-extrabold text-xs transition-all shadow-xs flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Diagnosa SSO</span>
                    </a>

                    <a :href="selectedApp ? selectedApp.edit_url : '#'" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl font-extrabold text-xs transition-all shadow-md shadow-emerald-700/20 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        <span>Edit Aplikasi</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
