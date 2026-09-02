<!DOCTYPE html>
@php
    $siteLogoUrl = \App\Models\Setting::getLogoUrl();
    $loginBg = \App\Models\Setting::get('login_background');
    $isCustomLoginBg = !empty($loginBg) && \Illuminate\Support\Facades\Storage::disk('public')->exists($loginBg);
    $loginBgUrl = \App\Models\Setting::getLoginBgUrl();
@endphp
<html lang="id" class="h-full bg-slate-50 text-slate-800">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Login' }}</title>
    <!-- Website Logo favicon for browser tab -->
    <link rel="icon" href="{{ $siteLogoUrl }}" sizes="any">
    <link rel="shortcut icon" href="{{ $siteLogoUrl }}">
    <link rel="apple-touch-icon" href="{{ $siteLogoUrl }}">
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
<body class="h-full font-sans antialiased bg-slate-100 text-slate-800 flex flex-col justify-between min-h-screen relative overflow-x-hidden selection:bg-emerald-700 selection:text-white">
    
    <!-- Background Login Page (Default Logo Sekolah / Kustom Wallpaper) -->
    <div class="fixed inset-0 flex items-center justify-center {{ $isCustomLoginBg ? 'opacity-80' : 'opacity-[0.06]' }} pointer-events-none z-0 p-4">
        <img src="{{ $loginBgUrl }}" alt="Background Login SMKN 1 Bangsri" class="w-[650px] h-[650px] object-contain">
    </div>

    <!-- Official Top Institutional Header Bar -->
    <div class="bg-emerald-950 text-white text-xs font-bold h-9 border-b-2 border-emerald-600 relative z-10 overflow-hidden select-none whitespace-nowrap flex items-center shrink-0">
        <div class="animate-marquee flex items-center">
            <div class="flex items-center space-x-6 shrink-0 px-4 whitespace-nowrap">
                <img src="{{ $siteLogoUrl }}" class="w-4 h-4 object-contain shrink-0" alt="Logo">
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
                <img src="{{ $siteLogoUrl }}" class="w-4 h-4 object-contain shrink-0" alt="Logo">
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

    <!-- Main Content Container -->
    <div class="w-full max-w-md mx-auto my-auto p-4 relative z-10">
        <!-- Logo Badge Header -->
        <div class="text-center mb-6">
            <a href="/" class="inline-block mb-3">
                <img src="{{ $siteLogoUrl }}" alt="Logo SMKN 1 Bangsri" class="w-24 h-24 mx-auto object-contain drop-shadow-md hover:scale-105 transition-transform">
            </a>
            <h1 class="text-2xl font-black text-emerald-950 tracking-tight">
                SiPintu <span class="text-emerald-700 font-extrabold">SKANSABA</span>
            </h1>
            <p class="text-xs font-bold text-slate-600 tracking-wider uppercase mt-0.5">SMK NEGERI 1 BANGSRI</p>
        </div>

        @yield('content')
    </div>

    <!-- Official School Footer -->
    <footer class="bg-white border-t border-slate-200 py-3 text-center text-xs text-slate-500 relative z-10 font-medium">
        <div>&copy; {{ date('Y') }} SMK Negeri 1 Bangsri. Hak Cipta Dilindungi Undang-Undang.</div>
    </footer>
</body>
</html>
