<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-100 text-slate-800">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'SiPintu' }}</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        emerald: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                            950: '#022c22',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        @keyframes marquee {
            0% { transform: translateX(0%); }
            100% { transform: translateX(-50%); }
        }
        .animate-marquee {
            display: flex;
            align-items: center;
            width: max-content;
            will-change: transform;
            animation: marquee 25s linear infinite;
        }
        .animate-marquee:hover {
            animation-play-state: paused;
        }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-full font-sans antialiased bg-slate-100 text-slate-800 selection:bg-emerald-700 selection:text-white relative" x-data="{ mobileMenuOpen: false }">
    
    <!-- Watermark Logo Sekolah di Background Aplikasi -->
    <div class="fixed inset-0 flex items-center justify-center opacity-[0.04] pointer-events-none z-0">
        <img src="{{ asset('images/logo-smkn1bangsri.png') }}" alt="Watermark SMKN 1 Bangsri" class="w-[750px] h-[750px] object-contain">
    </div>

    <!-- Mobile Slide-over Drawer Backdrop & Overlay -->
    <div x-show="mobileMenuOpen"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 md:hidden bg-slate-900/60 backdrop-blur-xs flex"
         x-cloak>
        
        <!-- Mobile Navigation Panel -->
        <div @click.away="mobileMenuOpen = false" class="w-72 bg-white h-full shadow-2xl flex flex-col justify-between relative z-10">
            <!-- Mobile Drawer Brand Header -->
            <div class="h-16 flex items-center justify-between px-4 border-b border-slate-200 bg-white">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-2.5">
                    <img src="{{ asset('images/logo-smkn1bangsri.png') }}" alt="Logo SMKN 1 Bangsri" class="w-8 h-8 object-contain">
                    <div>
                        <span class="font-black text-xs text-emerald-950 tracking-tight flex items-center gap-1">
                            SIPINTU <span class="px-1 py-0.5 text-[8px] bg-emerald-100 text-emerald-800 border border-emerald-300 rounded font-extrabold">MOBILE</span>
                        </span>
                        <span class="block text-[9px] text-emerald-700 font-extrabold uppercase">SMKN 1 BANGSRI</span>
                    </div>
                </a>
                <button @click="mobileMenuOpen = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <!-- Mobile Drawer Nav Links -->
            <nav class="flex-1 px-4 py-4 space-y-1.5 overflow-y-auto">
                @if(auth()->user()->isAdmin())
                    <div class="px-3 pb-2 text-[10px] font-extrabold text-emerald-900 uppercase tracking-wider">Navigasi Admin</div>
                    
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center px-3 py-2 text-xs font-bold rounded-xl transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                        <svg class="w-4 h-4 mr-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        Dashboard
                    </a>

                    <a href="{{ route('admin.apps') }}" class="flex items-center px-3 py-2 text-xs font-bold rounded-xl transition-all {{ request()->routeIs('admin.apps') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                        <svg class="w-4 h-4 mr-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        Katalog Aplikasi
                    </a>

                    <a href="{{ route('admin.users.index') }}" class="flex items-center px-3 py-2 text-xs font-bold rounded-xl transition-all {{ request()->routeIs('admin.users.*') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                        <svg class="w-4 h-4 mr-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        Kelola Pengguna
                    </a>

                    <a href="{{ route('admin.applications.index') }}" class="flex items-center px-3 py-2 text-xs font-bold rounded-xl transition-all {{ request()->routeIs('admin.applications.*') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                        <svg class="w-4 h-4 mr-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        Pendaftaran Aplikasi
                    </a>

                    <a href="{{ route('admin.roles.index') }}" class="flex items-center px-3 py-2 text-xs font-bold rounded-xl transition-all {{ request()->routeIs('admin.roles.*') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                        <svg class="w-4 h-4 mr-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                        Roles & Permission
                    </a>

                    <a href="{{ route('admin.announcements.index') }}" class="flex items-center px-3 py-2 text-xs font-bold rounded-xl transition-all {{ request()->routeIs('admin.announcements.*') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                        <svg class="w-4 h-4 mr-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                        Pengumuman Sekolah
                    </a>

                    <div class="pt-3 px-3 pb-1.5 text-[10px] font-extrabold text-emerald-900 uppercase tracking-wider">Integrasi & Keamanan</div>

                    <a href="{{ route('admin.sijuna.index') }}" class="flex items-center px-3 py-2 text-xs font-bold rounded-xl transition-all {{ request()->routeIs('admin.sijuna.*') ? 'bg-slate-700 text-white shadow-md shadow-slate-700/20' : 'text-slate-700 hover:text-slate-800 hover:bg-slate-50' }}">
                        <svg class="w-4 h-4 mr-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        Integrasi SIJUNA API
                    </a>

                    <a href="{{ route('admin.audit-logs.index') }}" class="flex items-center px-3 py-2 text-xs font-bold rounded-xl transition-all {{ request()->routeIs('admin.audit-logs.*') ? 'bg-slate-700 text-white shadow-md shadow-slate-700/20' : 'text-slate-700 hover:text-slate-800 hover:bg-slate-50' }}">
                        <svg class="w-4 h-4 mr-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                        Audit Log Aktivitas
                    </a>

                    <a href="{{ route('admin.monitoring.index') }}" class="flex items-center px-3 py-2 text-xs font-bold rounded-xl transition-all {{ request()->routeIs('admin.monitoring.*') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                        <svg class="w-4 h-4 mr-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                        Monitoring System
                    </a>

                    <a href="{{ route('admin.analytics.index') }}" class="flex items-center px-3 py-2 text-xs font-bold rounded-xl transition-all {{ request()->routeIs('admin.analytics.*') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                        <svg class="w-4 h-4 mr-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        Analitik & Laporan
                    </a>
                @elseif(auth()->user()->isTeacher())
                    <div class="px-3 pb-2 text-[10px] font-extrabold text-emerald-900 uppercase tracking-wider">Portal Guru SMKN 1 Bangsri</div>
                    
                    <a href="{{ route('teacher.dashboard') }}" class="flex items-center px-3 py-2 text-xs font-bold rounded-xl transition-all {{ request()->routeIs('teacher.dashboard') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                        <svg class="w-4 h-4 mr-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        Dashboard Guru
                    </a>

                    <div class="pt-3 px-3 pb-1.5 text-[10px] font-extrabold text-emerald-900 uppercase tracking-wider">Layanan Akses Terpadu</div>

                    <a href="{{ route('teacher.apps') }}" class="flex items-center px-3 py-2 text-xs font-bold rounded-xl transition-all {{ request()->routeIs('teacher.apps') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                        <svg class="w-4 h-4 mr-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        Aplikasi Terpadu Guru
                    </a>
                @elseif(auth()->user()->isDudi())
                    <div class="px-3 pb-2 text-[10px] font-extrabold text-emerald-900 uppercase tracking-wider">Portal Mitra DUDI</div>
                    
                    <a href="{{ route('dudi.dashboard') }}" class="flex items-center px-3 py-2 text-xs font-bold rounded-xl transition-all {{ request()->routeIs('dudi.dashboard') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                        <svg class="w-4 h-4 mr-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        Dashboard DUDI
                    </a>

                    <div class="pt-3 px-3 pb-1.5 text-[10px] font-extrabold text-emerald-900 uppercase tracking-wider">Layanan Akses Terpadu</div>

                    <a href="{{ route('dudi.apps') }}" class="flex items-center px-3 py-2 text-xs font-bold rounded-xl transition-all {{ request()->routeIs('dudi.apps') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                        <svg class="w-4 h-4 mr-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        Aplikasi Terpadu DUDI
                    </a>
                @elseif(auth()->user()->isStudent())
                    <div class="px-3 pb-2 text-[10px] font-extrabold text-emerald-900 uppercase tracking-wider">Portal Siswa SMKN 1 Bangsri</div>
                    
                    <a href="{{ route('student.dashboard') }}" class="flex items-center px-3 py-2 text-xs font-bold rounded-xl transition-all {{ request()->routeIs('student.dashboard') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                        <svg class="w-4 h-4 mr-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        Dashboard Siswa
                    </a>

                    <div class="pt-3 px-3 pb-1.5 text-[10px] font-extrabold text-emerald-900 uppercase tracking-wider">Layanan Akses Terpadu</div>

                    <a href="{{ route('student.apps') }}" class="flex items-center px-3 py-2 text-xs font-bold rounded-xl transition-all {{ request()->routeIs('student.apps') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                        <svg class="w-4 h-4 mr-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        Aplikasi Terpadu
                    </a>
                @endif
            </nav>

            <!-- Drawer User Footer Profile -->
            <div class="p-4 border-t border-slate-200 bg-emerald-50/50 flex items-center justify-between">
                <div class="flex items-center space-x-3 overflow-hidden">
                    <img src="{{ asset('images/logo-smkn1bangsri.png') }}" class="w-8 h-8 object-contain shrink-0" alt="Logo">
                    <div class="truncate">
                        <div class="text-xs font-black text-emerald-950 truncate">{{ auth()->user()->name }}</div>
                        <div class="text-[10px] text-emerald-800 capitalize truncate font-bold">{{ auth()->user()->getUserTypeName() }}</div>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="p-2 text-slate-500 hover:text-rose-600 hover:bg-white rounded-lg transition-colors" title="Logout">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="min-h-full flex flex-col relative z-10">
        <!-- Top Institutional Ministry Bar -->
        <div class="bg-emerald-800 text-white text-xs font-bold h-9 border-b-2 border-emerald-600 relative z-20 overflow-hidden select-none whitespace-nowrap flex items-center shrink-0">
            <div class="animate-marquee flex items-center">
                <div class="flex items-center space-x-6 shrink-0 px-4 whitespace-nowrap">
                    <img src="{{ asset('images/logo-smkn1bangsri.png') }}" class="w-4 h-4 object-contain shrink-0" alt="Logo">
                    <span>PEMERINTAH PROVINSI JAWA TENGAH &bull; DINAS PENDIDIKAN DAN KEBUDAYAAN &bull; SMKN 1 BANGSRI</span>
                    <span class="text-emerald-400">&bull;</span>
                    <span class="text-emerald-200">GATEWAY RESMI SMKN 1 BANGSRI</span>
                    <span class="text-emerald-400">&bull;</span>
                    <span>NPSN: 20360604</span>
                    <span class="text-emerald-400">&bull;</span>
                    <span class="text-emerald-200">PORTAL AKSES TERPADU</span>
                    <span class="text-emerald-400">&bull;</span>
                </div>
                <div class="flex items-center space-x-6 shrink-0 px-4 whitespace-nowrap" aria-hidden="true">
                    <img src="{{ asset('images/logo-smkn1bangsri.png') }}" class="w-4 h-4 object-contain shrink-0" alt="Logo">
                    <span>PEMERINTAH PROVINSI JAWA TENGAH &bull; DINAS PENDIDIKAN DAN KEBUDAYAAN &bull; SMKN 1 BANGSRI</span>
                    <span class="text-emerald-400">&bull;</span>
                    <span class="text-emerald-200">GATEWAY RESMI SMKN 1 BANGSRI</span>
                    <span class="text-emerald-400">&bull;</span>
                    <span>NPSN: 20360604</span>
                    <span class="text-emerald-400">&bull;</span>
                    <span class="text-emerald-200">PORTAL AKSES TERPADU</span>
                    <span class="text-emerald-400">&bull;</span>
                </div>
            </div>
        </div>

        <div class="flex-1 flex flex-col md:flex-row">
            <!-- Desktop Sidebar Navigation (Hidden on Mobile) -->
            <aside class="hidden md:flex md:w-64 bg-white border-r border-slate-200 flex-col shrink-0">
                <!-- Brand Logo Header -->
                <div class="h-20 flex items-center px-5 border-b border-slate-200 bg-white">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group">
                        <img src="{{ asset('images/logo-smkn1bangsri.png') }}" alt="Logo SMKN 1 Bangsri" class="w-11 h-11 object-contain group-hover:scale-105 transition-transform">
                        <div>
                            <span class="font-black text-sm text-emerald-950 tracking-tight flex items-center gap-1">
                                SIPINTU <span class="px-1.5 py-0.5 text-[9px] bg-emerald-100 text-emerald-800 border border-emerald-300 rounded font-extrabold">GATEWAY</span>
                            </span>
                            <span class="block text-[10px] text-emerald-700 font-extrabold tracking-wider uppercase">SMKN 1 BANGSRI</span>
                        </div>
                    </a>
                </div>

                <!-- Main Navigation Links -->
                <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
                    @if(auth()->user()->isAdmin())
                        <div class="px-3 pb-2 text-[11px] font-extrabold text-emerald-900 uppercase tracking-wider">Navigasi Admin</div>
                        
                        <a href="{{ route('admin.dashboard') }}" class="flex items-center px-3 py-2.5 text-sm font-bold rounded-xl transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                            Dashboard 
                        </a>

                        <a href="{{ route('admin.apps') }}" class="flex items-center px-3 py-2.5 text-sm font-bold rounded-xl transition-all {{ request()->routeIs('admin.apps') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                            Katalog Aplikasi
                        </a>

                        <a href="{{ route('admin.users.index') }}" class="flex items-center px-3 py-2.5 text-sm font-bold rounded-xl transition-all {{ request()->routeIs('admin.users.*') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            Kelola Pengguna
                        </a>

                        <a href="{{ route('admin.applications.index') }}" class="flex items-center px-3 py-2.5 text-sm font-bold rounded-xl transition-all {{ request()->routeIs('admin.applications.*') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                            Pendaftaran Aplikasi
                        </a>

                        <a href="{{ route('admin.roles.index') }}" class="flex items-center px-3 py-2.5 text-sm font-bold rounded-xl transition-all {{ request()->routeIs('admin.roles.*') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            Roles & Permission
                        </a>

                        <a href="{{ route('admin.announcements.index') }}" class="flex items-center px-3 py-2.5 text-sm font-bold rounded-xl transition-all {{ request()->routeIs('admin.announcements.*') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path></svg>
                            Pengumuman Sekolah
                        </a>

                        <div class="pt-4 px-3 pb-2 text-[11px] font-extrabold text-emerald-900 uppercase tracking-wider">Integrasi & Keamanan</div>

                        <a href="{{ route('admin.sijuna.index') }}" class="flex items-center px-3 py-2.5 text-sm font-bold rounded-xl transition-all {{ request()->routeIs('admin.sijuna.*') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            Integrasi SIJUNA API
                        </a>

                        <a href="{{ route('admin.audit-logs.index') }}" class="flex items-center px-3 py-2.5 text-sm font-bold rounded-xl transition-all {{ request()->routeIs('admin.audit-logs.*') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                            Audit Log Aktivitas
                        </a>

                        <a href="{{ route('admin.monitoring.index') }}" class="flex items-center px-3 py-2.5 text-sm font-bold rounded-xl transition-all {{ request()->routeIs('admin.monitoring.*') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                            Monitoring System
                        </a>
                    @elseif(auth()->user()->isTeacher())
                        <div class="px-3 pb-2 text-[11px] font-extrabold text-emerald-900 uppercase tracking-wider">Portal Guru SMKN 1 Bangsri</div>
                        
                        <a href="{{ route('teacher.dashboard') }}" class="flex items-center px-3 py-2.5 text-sm font-bold rounded-xl transition-all {{ request()->routeIs('teacher.dashboard') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                            Dashboard Guru
                        </a>

                        <div class="pt-4 px-3 pb-2 text-[11px] font-extrabold text-emerald-900 uppercase tracking-wider">Layanan Akses Terpadu</div>

                        <a href="{{ route('teacher.apps') }}" class="flex items-center px-3 py-2.5 text-sm font-bold rounded-xl transition-all {{ request()->routeIs('teacher.apps') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                            <svg class="w-5 h-5 mr-3 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                            Aplikasi Terpadu Guru
                        </a>
                    @elseif(auth()->user()->isDudi())
                        <div class="px-3 pb-2 text-[11px] font-extrabold text-emerald-900 uppercase tracking-wider">Portal Mitra DUDI</div>
                        
                        <a href="{{ route('dudi.dashboard') }}" class="flex items-center px-3 py-2.5 text-sm font-bold rounded-xl transition-all {{ request()->routeIs('dudi.dashboard') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                            Dashboard DUDI
                        </a>

                        <div class="pt-4 px-3 pb-2 text-[11px] font-extrabold text-emerald-900 uppercase tracking-wider">Layanan Akses Terpadu</div>

                        <a href="{{ route('dudi.apps') }}" class="flex items-center px-3 py-2.5 text-sm font-bold rounded-xl transition-all {{ request()->routeIs('dudi.apps') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                            <svg class="w-5 h-5 mr-3 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                            Aplikasi Terpadu DUDI
                        </a>
                    @elseif(auth()->user()->isStudent())
                        <div class="px-3 pb-2 text-[11px] font-extrabold text-emerald-900 uppercase tracking-wider">Portal Siswa SMKN 1 Bangsri</div>
                        
                        <a href="{{ route('student.dashboard') }}" class="flex items-center px-3 py-2.5 text-sm font-bold rounded-xl transition-all {{ request()->routeIs('student.dashboard') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                            Dashboard Siswa
                        </a>

                        <div class="pt-4 px-3 pb-2 text-[11px] font-extrabold text-emerald-900 uppercase tracking-wider">Layanan Akses Terpadu</div>

                        <a href="{{ route('student.apps') }}" class="flex items-center px-3 py-2.5 text-sm font-bold rounded-xl transition-all {{ request()->routeIs('student.apps') ? 'bg-emerald-700 text-white shadow-md shadow-emerald-700/20' : 'text-slate-700 hover:text-emerald-800 hover:bg-emerald-50' }}">
                            <svg class="w-5 h-5 mr-3 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                            Aplikasi Terpadu
                        </a>
                    @endif
                </nav>

                <!-- User Footer Profile -->
                <div class="p-4 border-t border-slate-200 bg-emerald-50/50 flex items-center justify-between">
                    <div class="flex items-center space-x-3 overflow-hidden">
                        <img src="{{ asset('images/logo-smkn1bangsri.png') }}" class="w-8 h-8 object-contain shrink-0" alt="Logo">
                        <div class="truncate">
                            <div class="text-sm font-black text-emerald-950 truncate">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-emerald-800 capitalize truncate font-bold">{{ auth()->user()->getUserTypeName() }}</div>
                        </div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="p-2 text-slate-500 hover:text-rose-600 hover:bg-white rounded-lg transition-colors" title="Logout">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                        </button>
                    </form>
                </div>
            </aside>

            <!-- Main Content Body -->
            <main class="flex-1 flex flex-col min-w-0 bg-slate-50/80">
                <!-- Top Header Bar -->
                <header class="h-16 bg-white border-b border-slate-200 px-4 sm:px-6 flex items-center justify-between sticky top-0 z-10 shadow-xs">
                    <div class="flex items-center space-x-3">
                        <!-- Mobile Hamburger Button (Only visible on mobile) -->
                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="p-2 rounded-xl text-slate-700 hover:bg-emerald-50 hover:text-emerald-800 md:hidden border border-slate-200 transition-colors" title="Buka Menu">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        </button>

                        <div class="flex items-center space-x-2">
                            <img src="{{ asset('images/logo-smkn1bangsri.png') }}" class="w-7 h-7 object-contain md:hidden" alt="Logo">
                            <h1 class="text-sm font-extrabold sm:font-black text-emerald-950 tracking-tight whitespace-nowrap">{{ $headerTitle ?? 'Dashboard Gateway' }}</h1>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3 sm:space-x-4">
                        <span class="hidden sm:inline-flex items-center px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                            <span class="w-2 h-2 rounded-full bg-emerald-600 mr-2 animate-pulse"></span>
                            Aktif
                        </span>

                        <a href="{{ route('profile') }}" class="text-xs font-bold text-slate-700 hover:text-emerald-800 transition-colors whitespace-nowrap">
                            Profil Saya
                        </a>
                    </div>
                </header>

                <!-- Alerts Banner -->
                <div class="px-4 sm:px-6 pt-4 sm:pt-6">
                    @if(session('success'))
                        <div class="p-4 mb-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <svg class="w-5 h-5 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span class="font-bold text-xs sm:text-sm">{{ session('success') }}</span>
                            </div>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="p-4 mb-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <svg class="w-5 h-5 text-rose-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span class="font-bold text-xs sm:text-sm">{{ session('error') }}</span>
                            </div>
                        </div>
                    @endif

                    @if(session('info'))
                        <div class="p-4 mb-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <svg class="w-5 h-5 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span class="font-bold text-xs sm:text-sm">{{ session('info') }}</span>
                            </div>
                        </div>
                    @endif

                    @auth
                        @php
                            $userRole = auth()->user()->role;
                            $userAnnouncements = \App\Models\Announcement::active()
                                ->forWeb()
                                ->forRole($userRole)
                                ->latest()
                                ->take(3)
                                ->get();
                        @endphp

                        @foreach($userAnnouncements as $ann)
                            @php
                                $title = is_object($ann) ? ($ann->title ?? '') : (is_array($ann) ? ($ann['title'] ?? '') : '');
                                $content = is_object($ann) ? ($ann->content ?? '') : (is_array($ann) ? ($ann['content'] ?? '') : '');
                                $pubAt = is_object($ann) ? ($ann->published_at ?? null) : null;
                                $diffTime = $pubAt instanceof \Carbon\Carbon ? $pubAt->diffForHumans() : 'Baru saja';
                            @endphp
                            @if(!empty($title))
                                <div class="p-4 mb-4 rounded-2xl border flex items-start justify-between shadow-xs bg-emerald-50 border-emerald-200 text-emerald-900">
                                    <div class="flex items-start space-x-3">
                                        <img src="{{ asset('images/logo-smkn1bangsri.png') }}" class="w-7 h-7 object-contain mt-0.5 shrink-0" alt="Logo">
                                        <div>
                                            <div class="font-black text-sm text-emerald-950 flex flex-wrap items-center gap-2">
                                                <span>{{ $title }}</span>
                                                <span class="px-2 py-0.5 rounded text-[9px] font-extrabold uppercase bg-white border border-emerald-300 text-emerald-800 whitespace-nowrap">PENGUMUMAN SEKOLAH</span>
                                            </div>
                                            <div class="text-xs mt-1 text-slate-700 leading-relaxed font-medium">{{ $content }}</div>
                                            <div class="text-[10px] mt-1.5 text-slate-500 font-semibold">{{ $diffTime }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @endauth
                </div>

                <!-- Page Content -->
                <div class="p-4 sm:p-6 flex-1">
                    {{ $slot ?? '' }}
                    @yield('content')
                </div>

                <!-- Official Footer -->
                <footer class="bg-white border-t border-slate-200 py-3 px-4 sm:px-6 text-center md:text-left text-xs text-slate-500 font-medium flex flex-col md:flex-row items-center justify-between">
                    <div>&copy; {{ date('Y') }} SMK Negeri 1 Bangsri. Hak Cipta Dilindungi Undang-Undang.</div>
                    <div class="text-emerald-800 font-bold mt-1 md:mt-0 font-mono text-[11px]">Identity & SSO Gateway v2.0</div>
                </footer>
            </main>
        </div>
    </div>
</body>
</html>
