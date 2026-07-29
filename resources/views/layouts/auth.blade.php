<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950 text-slate-100 dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'SiPintu' }}</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full font-sans antialiased bg-slate-950 text-slate-100 flex items-center justify-center p-4 relative overflow-x-hidden">
    <!-- Ambient Background Glows -->
    <div class="fixed top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-indigo-600/20 rounded-full blur-[140px] pointer-events-none"></div>
    <div class="fixed bottom-10 right-10 w-[400px] h-[400px] bg-purple-600/15 rounded-full blur-[120px] pointer-events-none"></div>

    <div class="w-full max-w-md relative z-10">
        <!-- Logo Badge -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-purple-500 shadow-xl shadow-indigo-500/30 text-white font-black text-2xl mb-4 transform hover:scale-105 transition-transform">
                G
            </div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight">SiPintu</h1>
        </div>

        @yield('content')
    </div>
</body>
</html>
