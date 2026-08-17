<!DOCTYPE html>
<html lang="id" class="h-full bg-white text-slate-800">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SiPintu</title>
    <meta name="description" content="Portal Resmi Gateway SMKN 1 Bangsri.">

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
    </style>
</head>
<body class="h-full font-sans antialiased bg-slate-50 text-slate-800 relative overflow-x-hidden selection:bg-emerald-700 selection:text-white">
    
    <!-- Watermark Logo Sekolah di Background -->
    <div class="fixed inset-0 flex items-center justify-center opacity-[0.05] pointer-events-none z-0">
        <img src="{{ asset('images/logo-smkn1bangsri.png') }}" alt="Watermark SMKN 1 Bangsri" class="w-[800px] h-[800px] object-contain">
    </div>

    <!-- Official Top Institutional Header Bar -->
    <div class="bg-emerald-950 text-white text-xs font-bold h-9 border-b-2 border-emerald-600 relative z-20 overflow-hidden select-none whitespace-nowrap flex items-center shrink-0">
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

    <div class="relative z-10 flex flex-col min-h-screen">
        <!-- Navigation Header -->
        <header class="sticky top-0 z-50 bg-white border-b border-slate-200 shadow-xs">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-20">
                    <!-- Brand Logo -->
                    <a href="/" class="flex items-center space-x-3.5 group">
                        <img src="{{ asset('images/logo-smkn1bangsri.png') }}" alt="Logo SMKN 1 Bangsri" class="w-12 h-12 object-contain group-hover:scale-105 transition-all drop-shadow-sm">
                        <div>
                            <span class="font-black text-lg text-emerald-950 tracking-tight flex items-center gap-2">
                                SIPINTU <span class="px-2 py-0.5 text-[10px] bg-emerald-100 text-emerald-800 border border-emerald-300 rounded font-extrabold">GATEWAY</span>
                            </span>
                            <span class="block text-[11px] text-emerald-800 font-extrabold tracking-wider uppercase">SMK NEGERI 1 BANGSRI</span>
                        </div>
                    </a>

                    <!-- Navigation Links -->
                    <nav class="hidden md:flex items-center space-x-8 text-sm font-bold text-slate-700">
                        <a href="#layanan" class="hover:text-emerald-700 transition-colors">Akses Terpadu</a>
                        <a href="#portal" class="hover:text-emerald-700 transition-colors">Portal Peran</a>
                        <a href="#sijuna" class="hover:text-emerald-700 transition-colors">Integrasi SIJUNA</a>
                        <a href="#tentang" class="hover:text-emerald-700 transition-colors">Tentang Sekolah</a>
                    </nav>

                    <!-- Auth Action Button -->
                    <div class="flex items-center space-x-4">
                        @auth
                            <a href="{{ route('dashboard') }}" class="inline-flex items-center space-x-2 px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl shadow-md transition-all">
                                <span>Buka Dashboard</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center space-x-2 px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl shadow-md transition-all">
                                <span>Masuk Portal Gateway &rarr;</span>
                            </a>
                        @endauth
                    </div>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="py-16 md:py-20 relative bg-gradient-to-b from-white to-slate-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                    <!-- Left Hero Intro Content -->
                    <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
                        <div class="inline-flex items-center space-x-2.5 px-4 py-2 rounded-full bg-emerald-100 border border-emerald-300 text-emerald-900 text-xs font-extrabold">
                            <img src="{{ asset('images/logo-smkn1bangsri.png') }}" class="w-4 h-4 object-contain" alt="Logo">
                            <span>Portal Akses Terpadu Resmi SMKN 1 Bangsri</span>
                        </div>

                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-emerald-950 tracking-tight leading-tight">
                            Portal Akses Terpadu <span class="text-emerald-800">SMK Negeri 1 Bangsri</span>
                        </h1>

                        <p class="text-base sm:text-lg text-slate-600 font-medium leading-relaxed max-w-2xl mx-auto lg:mx-0">
                            <strong>SiPintu</strong> adalah sistem gateway identitas resmi yang menghubungkan seluruh aplikasi siswa, portal pengajar, aplikasi mitra DUDI, dan SIJUNA API dalam satu akun terpadu.
                        </p>

                        <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 pt-2">
                            <a href="{{ route('login') }}" class="w-full sm:w-auto px-8 py-4 bg-emerald-700 hover:bg-emerald-800 text-white font-extrabold text-sm rounded-2xl shadow-lg transition-all flex items-center justify-center space-x-3">
                                <span>Masuk ke Gateway (Akses Terpadu)</span>
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </a>
                            <a href="#portal" class="w-full sm:w-auto px-6 py-4 bg-white hover:bg-slate-50 text-slate-700 font-bold text-sm rounded-2xl border border-slate-300 transition-all flex items-center justify-center space-x-2 shadow-xs">
                                <span>Lihat Fitur & Portal</span>
                            </a>
                        </div>

                        <!-- Live Stats Bar -->
                        <div class="grid grid-cols-3 gap-4 pt-8 border-t border-slate-200 max-w-xl mx-auto lg:mx-0">
                            <div>
                                <div class="text-2xl sm:text-3xl font-black text-emerald-800">3.500+</div>
                                <div class="text-xs text-slate-600 font-bold mt-0.5">Siswa Terdaftar</div>
                            </div>
                            <div>
                                <div class="text-2xl sm:text-3xl font-black text-emerald-800">120+</div>
                                <div class="text-xs text-slate-600 font-bold mt-0.5">Guru & Staff</div>
                            </div>
                            <div>
                                <div class="text-2xl sm:text-3xl font-black text-emerald-800">45+</div>
                                <div class="text-xs text-slate-600 font-bold mt-0.5">Mitra Industri DUDI</div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Hero Visual Showcase Card -->
                    <div class="lg:col-span-5 relative">
                        <div class="relative rounded-3xl bg-white border border-slate-200 p-6 sm:p-8 shadow-xl">
                            <!-- Header of visual card -->
                            <div class="flex items-center justify-between pb-6 border-b border-slate-100">
                                <div class="flex items-center space-x-3">
                                    <img src="{{ asset('images/logo-smkn1bangsri.png') }}" class="w-10 h-10 object-contain" alt="Logo">
                                    <div>
                                        <div class="text-sm font-black text-emerald-950">Status Gateway Active</div>
                                        <div class="text-xs text-emerald-800 font-bold">SMKN 1 BANGSRI SSO System</div>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">
                                    OAuth2 Valid
                                </span>
                            </div>

                            <!-- Showcase Applications Preview -->
                            <div class="space-y-3.5 py-6">
                                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-9 h-9 rounded-lg bg-emerald-700 text-white flex items-center justify-center font-black text-xs">
                                            SJ
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-slate-900">SIJUNA Academic Portal</div>
                                            <div class="text-[10px] text-slate-500 font-semibold">Data Siswa & Akademik</div>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded border border-emerald-300">Synced</span>
                                </div>

                                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-9 h-9 rounded-lg bg-emerald-800 text-white flex items-center justify-center font-black text-xs">
                                            PK
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-slate-900">Portal PKL & Industri DUDI</div>
                                            <div class="text-[10px] text-slate-500 font-semibold">Monitoring & Evaluasi Perusahaan</div>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded border border-emerald-300">Synced</span>
                                </div>

                                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-9 h-9 rounded-lg bg-emerald-900 text-white flex items-center justify-center font-black text-xs">
                                            GR
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold text-slate-900">Aplikasi Presensi Guru</div>
                                            <div class="text-[10px] text-slate-500 font-semibold">Laporan Jurnal Mengajar</div>
                                        </div>
                                    </div>
                                    <span class="text-[10px] font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded border border-emerald-300">Synced</span>
                                </div>
                            </div>

                            <!-- Footer info -->
                            <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-600">
                                <span>Keamanan Terenkripsi</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- School Role Portals Section -->
        <section id="portal" class="py-16 bg-white border-y border-slate-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-12">
                <div class="text-center max-w-3xl mx-auto space-y-3">
                    <span class="text-xs font-extrabold uppercase tracking-widest text-emerald-800 bg-emerald-100 px-3 py-1 rounded-full border border-emerald-300">
                        Multi-Peran Terintegrasi
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-black text-emerald-950 tracking-tight">
                        Portal Akses Khusus Sesuai Peran Anda
                    </h2>
                    <p class="text-sm text-slate-600 font-medium">
                        Setiap pengguna SMKN 1 Bangsri memiliki dashboard dan otorisasi aplikasi yang disesuaikan.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Portal Siswa -->
                    <div class="p-8 rounded-3xl bg-white border border-slate-200 hover:border-emerald-500 transition-all duration-300 group shadow-md flex flex-col justify-between">
                        <div class="space-y-4">
                            <div class="w-14 h-14 rounded-2xl bg-emerald-100 border border-emerald-300 text-emerald-800 flex items-center justify-center font-black text-2xl group-hover:scale-105 transition-transform">
                                🎓
                            </div>
                            <h3 class="text-xl font-black text-emerald-950 group-hover:text-emerald-700 transition-colors">Portal Siswa</h3>
                            <p class="text-xs text-slate-600 leading-relaxed font-medium">
                                Siswa mengakses katalog aplikasi pembelajaran, presensi digital, nilai SIJUNA, serta menandai aplikasi favorit.
                            </p>
                        </div>
                        <div class="pt-6 border-t border-slate-100 mt-6">
                            <a href="{{ route('login') }}" class="text-xs font-bold text-emerald-800 hover:underline flex items-center space-x-1">
                                <span>Masuk Portal Siswa</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </a>
                        </div>
                    </div>

                    <!-- Portal Guru -->
                    <div class="p-8 rounded-3xl bg-white border border-slate-200 hover:border-emerald-500 transition-all duration-300 group shadow-md flex flex-col justify-between">
                        <div class="space-y-4">
                            <div class="w-14 h-14 rounded-2xl bg-emerald-100 border border-emerald-300 text-emerald-800 flex items-center justify-center font-black text-2xl group-hover:scale-105 transition-transform">
                                📚
                            </div>
                            <h3 class="text-xl font-black text-emerald-950 group-hover:text-emerald-700 transition-colors">Portal Guru & Pengajar</h3>
                            <p class="text-xs text-slate-600 leading-relaxed font-medium">
                                Akses Single Sign-On (SSO) ke aplikasi-aplikasi internal pendidik, jurnal mengajar, dan katalog terpadu.
                            </p>
                        </div>
                        <div class="pt-6 border-t border-slate-100 mt-6">
                            <a href="{{ route('login') }}" class="text-xs font-bold text-emerald-800 hover:underline flex items-center space-x-1">
                                <span>Masuk Portal Guru</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </a>
                        </div>
                    </div>

                    <!-- Portal Mitra DUDI -->
                    <div class="p-8 rounded-3xl bg-white border border-slate-200 hover:border-emerald-500 transition-all duration-300 group shadow-md flex flex-col justify-between">
                        <div class="space-y-4">
                            <div class="w-14 h-14 rounded-2xl bg-emerald-100 border border-emerald-300 text-emerald-800 flex items-center justify-center font-black text-2xl group-hover:scale-105 transition-transform">
                                🏢
                            </div>
                            <h3 class="text-xl font-black text-emerald-950 group-hover:text-emerald-700 transition-colors">Portal Mitra DUDI</h3>
                            <p class="text-xs text-slate-600 leading-relaxed font-medium">
                                Akses Single Sign-On (SSO) untuk perusahaan mitra industri dalam mengakses layanan dan sistem integrasi sekolah.
                            </p>
                        </div>
                        <div class="pt-6 border-t border-slate-100 mt-6">
                            <a href="{{ route('login') }}" class="text-xs font-bold text-emerald-800 hover:underline flex items-center space-x-1">
                                <span>Masuk Portal DUDI</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer id="tentang" class="mt-auto bg-white border-t border-slate-200 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-8 pb-8 border-b border-slate-200">
                    <div class="md:col-span-6 space-y-3">
                        <div class="flex items-center space-x-3">
                            <img src="{{ asset('images/logo-smkn1bangsri.png') }}" class="w-9 h-9 object-contain" alt="Logo">
                            <span class="font-black text-base text-emerald-950">SMK NEGERI 1 BANGSRI</span>
                        </div>
                        <p class="text-xs text-slate-600 max-w-md leading-relaxed font-medium">
                            Jl. K.H. Achmad Fauzan No. 1, Bangsri, Kabupaten Jepara, Jawa Tengah 59453. <br>
                            Sistem Identity Provider
                        </p>
                    </div>

                    <div class="md:col-span-3 space-y-2">
                        <div class="text-xs font-extrabold text-emerald-950 uppercase tracking-wider">Tautan Cepat</div>
                        <ul class="space-y-1.5 text-xs text-slate-600 font-semibold">
                            <li><a href="{{ route('login') }}" class="hover:text-emerald-700 transition-colors">Portal Login</a></li>
                            <li><a href="#portal" class="hover:text-emerald-700 transition-colors">Peran & Akses</a></li>
                            <li><a href="#layanan" class="hover:text-emerald-700 transition-colors">Layanan SSO</a></li>
                        </ul>
                    </div>

                    <div class="md:col-span-3 space-y-2">
                        <div class="text-xs font-extrabold text-emerald-950 uppercase tracking-wider">Bantuan & Kontak</div>
                        <p class="text-xs text-slate-600 font-medium">
                            Tim IT Infrastructure SMKN 1 Bangsri <br>
                            Email: <span class="text-emerald-800 font-mono font-bold">admin@smkn1bangsri.sch.id</span>
                        </p>
                    </div>
                </div>

                <div class="pt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 font-medium">
                    <div>© {{ date('Y') }} SMK Negeri 1 Bangsri. Hak Cipta Dilindungi.</div>
                    <div class="mt-2 sm:mt-0 font-mono text-[11px] text-emerald-800 font-bold">SiPintu Identity & SSO Gateway v2.0</div>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>
