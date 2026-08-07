<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50 text-slate-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $title ?? 'SiPintu Mobile Gateway' }}</title>
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
                        // Desktop & Mobile Unified Branding Color (Emerald Solid Colors)
                        brand: {
                            primary: '#047857',      // Primary (#047857) - Same as Desktop Gateway
                            hover: '#065F46',        // Primary Hover
                            active: '#064E3B',       // Primary Active
                            soft: '#ECFDF5',         // Primary Soft Background
                            border: '#A7F3D0',       // Primary Soft Border
                            dark: '#022C22',         // Primary Dark Header Text
                        },
                        // Solid Support Colors
                        surface: '#FFFFFF',
                        canvas: '#F8FAFC',
                        border: '#E5E7EB',
                        textPrimary: '#111827',
                        textSecondary: '#6B7280',
                        statusSuccess: '#22C55E',
                        statusWarning: '#F59E0B',
                        statusError: '#EF4444',
                        statusInfo: '#047857',
                    },
                    borderRadius: {
                        'xl': '12px',
                        '2xl': '16px',
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
        /* Mobile Scrollbar Hide */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="h-full font-sans antialiased bg-slate-100 text-slate-900 selection:bg-brand-primary selection:text-white pb-20">
    
    <!-- Mobile App Container (Centered Max-width 480px) -->
    <div class="max-w-md mx-auto min-h-screen bg-white shadow-xl flex flex-col relative border-x border-slate-200">

        <!-- 1. Top Institutional Marquee Bar (Centered & Non-wrapping Text) -->
        <div class="bg-brand-dark text-white text-xs font-bold h-9 border-b-2 border-brand-primary overflow-hidden select-none whitespace-nowrap flex items-center shrink-0">
            <div class="animate-marquee flex items-center">
                <div class="flex items-center space-x-4 shrink-0 px-3 whitespace-nowrap">
                    <img src="{{ asset('images/logo-smkn1bangsri.png') }}" class="w-4 h-4 object-contain shrink-0" alt="Logo">
                    <span class="whitespace-nowrap">PEMERINTAH PROVINSI JAWA TENGAH &bull; SMKN 1 BANGSRI</span>
                    <span class="text-emerald-400">&bull;</span>
                    <span class="text-emerald-200 whitespace-nowrap">SIPINTU MOBILE GATEWAY</span>
                    <span class="text-emerald-400">&bull;</span>
                </div>
                <div class="flex items-center space-x-4 shrink-0 px-3 whitespace-nowrap" aria-hidden="true">
                    <img src="{{ asset('images/logo-smkn1bangsri.png') }}" class="w-4 h-4 object-contain shrink-0" alt="Logo">
                    <span class="whitespace-nowrap">PEMERINTAH PROVINSI JAWA TENGAH &bull; SMKN 1 BANGSRI</span>
                    <span class="text-emerald-400">&bull;</span>
                    <span class="text-emerald-200 whitespace-nowrap">SIPINTU MOBILE GATEWAY</span>
                    <span class="text-emerald-400">&bull;</span>
                </div>
            </div>
        </div>

        <!-- 2. Sticky Mobile Header (Centered & Non-wrapping Text) -->
        <header class="sticky top-0 z-40 bg-white border-b border-slate-200 px-4 py-3 flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-3">
                <img src="{{ asset('images/logo-smkn1bangsri.png') }}" alt="Logo SMKN 1 Bangsri" class="w-9 h-9 object-contain shrink-0">
                <div class="text-left">
                    <div class="flex items-center space-x-1.5">
                        <span class="font-black text-sm text-brand-dark tracking-tight whitespace-nowrap">SIPINTU</span>
                        <span class="px-1.5 py-0.5 text-[9px] bg-brand-soft text-brand-primary border border-brand-border rounded font-extrabold whitespace-nowrap">MOBILE</span>
                    </div>
                    <span class="block text-[9px] text-brand-primary font-extrabold tracking-wider uppercase whitespace-nowrap">SMKN 1 BANGSRI</span>
                </div>
            </div>

            <!-- Profile Badge / Actions (Non-wrapping) -->
            <div class="flex items-center space-x-2 shrink-0">
                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-brand-soft text-brand-primary border border-brand-border flex items-center gap-1 whitespace-nowrap">
                    <span class="w-1.5 h-1.5 rounded-full bg-brand-primary animate-pulse shrink-0"></span>
                    Aktif
                </span>
                <a href="{{ route('profile') }}" class="w-8 h-8 rounded-full bg-slate-100 border border-slate-200 text-slate-700 flex items-center justify-center font-bold text-xs hover:bg-brand-soft hover:text-brand-primary transition-colors shrink-0">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </a>
            </div>
        </header>

        <!-- 3. Dynamic Page Content -->
        <main class="flex-1 p-4 space-y-4 bg-slate-50 text-center">
            {{ $slot ?? '' }}
            @yield('content')
        </main>

        <!-- 4. Fixed Bottom Mobile Navigation Bar (Centered & Non-wrapping Text) -->
        <nav class="fixed bottom-0 max-w-md w-full bg-white border-t border-slate-200 z-50 px-2 py-1.5 flex items-center justify-around shadow-lg">
            <a href="{{ route('dashboard') }}" class="flex flex-col items-center justify-center py-1 px-3 rounded-xl transition-colors {{ request()->routeIs('dashboard', 'admin.dashboard', 'teacher.dashboard', 'student.dashboard', 'dudi.dashboard') ? 'text-brand-primary font-bold bg-brand-soft' : 'text-slate-500 hover:text-slate-800' }}">
                <svg class="w-5 h-5 mb-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 00-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                <span class="text-[10px] whitespace-nowrap">Beranda</span>
            </a>

            @php
                $appsRoute = auth()->user()->isAdmin() ? 'admin.apps' : (auth()->user()->isTeacher() ? 'teacher.apps' : (auth()->user()->isDudi() ? 'dudi.apps' : 'student.apps'));
            @endphp
            <a href="{{ route($appsRoute) }}" class="flex flex-col items-center justify-center py-1 px-3 rounded-xl transition-colors {{ request()->routeIs('*.apps') ? 'text-brand-primary font-bold bg-brand-soft' : 'text-slate-500 hover:text-slate-800' }}">
                <svg class="w-5 h-5 mb-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <span class="text-[10px] whitespace-nowrap">Katalog</span>
            </a>

            <a href="{{ route('mobile-preview') }}" class="flex flex-col items-center justify-center py-1 px-3 rounded-xl transition-colors {{ request()->routeIs('mobile-preview') ? 'text-brand-primary font-bold bg-brand-soft' : 'text-slate-500 hover:text-slate-800' }}">
                <svg class="w-5 h-5 mb-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                <span class="text-[10px] whitespace-nowrap">Mobile UI</span>
            </a>

            <a href="{{ route('profile') }}" class="flex flex-col items-center justify-center py-1 px-3 rounded-xl transition-colors {{ request()->routeIs('profile*') ? 'text-brand-primary font-bold bg-brand-soft' : 'text-slate-500 hover:text-slate-800' }}">
                <svg class="w-5 h-5 mb-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                <span class="text-[10px] whitespace-nowrap">Profil</span>
            </a>
        </nav>
    </div>
</body>
</html>
