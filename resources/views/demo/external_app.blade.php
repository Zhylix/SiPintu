<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950 text-slate-100 dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulasi Aplikasi Eksternal (SSO Client Test)</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="h-full font-sans antialiased bg-slate-950 text-slate-100 p-4 md:p-8">
    <div class="max-w-5xl mx-auto space-y-6">
        <!-- Top App Switcher Header -->
        <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 flex flex-col md:flex-row items-center justify-between gap-4 shadow-2xl">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-400 flex items-center justify-center font-bold text-xl">
                    ⚡
                </div>
                <div>
                    <span class="text-[10px] font-bold text-amber-400 uppercase tracking-widest block">SIMULATOR APLIKASI EKSTERNAL</span>
                    <h1 class="text-xl font-black text-white">{{ $app->name }}</h1>
                    <p class="text-xs text-slate-400 font-mono">{{ $app->base_url }}</p>
                </div>
            </div>

            <!-- Switch App Tabs -->
            <div class="flex items-center space-x-2 bg-slate-950 p-1.5 rounded-2xl border border-slate-800 text-xs">
                <a href="{{ route('demo.index', ['appSlug' => 'pkl']) }}" class="px-3 py-2 rounded-xl transition-all font-semibold {{ $appSlug === 'pkl' ? 'bg-amber-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white' }}">
                    Aplikasi PKL
                </a>
                <a href="{{ route('demo.index', ['appSlug' => 'akademik']) }}" class="px-3 py-2 rounded-xl transition-all font-semibold {{ $appSlug === 'akademik' ? 'bg-amber-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white' }}">
                    Aplikasi Akademik
                </a>
                <a href="{{ route('demo.index', ['appSlug' => 'presensi']) }}" class="px-3 py-2 rounded-xl transition-all font-semibold {{ $appSlug === 'presensi' ? 'bg-amber-500 text-slate-950 font-bold' : 'text-slate-400 hover:text-white' }}">
                    Aplikasi Presensi
                </a>
            </div>
        </div>

        <!-- System Architecture Flow Explanation Banner -->
        <div class="p-5 rounded-2xl bg-indigo-950/40 border border-indigo-500/20 text-xs text-indigo-200 space-y-2">
            <div class="flex items-center space-x-2 font-bold text-indigo-300 text-sm">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Skenario Akses Langsung (Direct Access & SSO Flow)</span>
            </div>
            <p class="leading-relaxed text-slate-300">
                Pengguna dapat membuka <strong>{{ $app->name }}</strong> secara langsung tanpa harus mengunjungi Gateway terlebih dahulu. Aplikasi akan mengecek session lokalnya. Jika belum login, pengguna diarahkan ke Gateway untuk melakukan SSO, kemudian kembali secara otomatis.
            </p>
        </div>

        @if(isset($error))
            <div class="p-5 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs flex items-center space-x-3">
                <svg class="w-6 h-6 text-rose-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <div>
                    <span class="font-bold block">Gagal Otentikasi SSO</span>
                    <span>{{ $error }}</span>
                </div>
            </div>
        @endif

        @if(session('success'))
            <div class="p-5 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-emerald-300 text-xs flex items-center space-x-3">
                <svg class="w-6 h-6 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <div>
                    <span class="font-bold block">Sukses SSO</span>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        <!-- Session Status Container -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Left Side: Local Session Status -->
            <div class="md:col-span-2 p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-6">
                <div class="flex items-center justify-between border-b border-slate-800 pb-4">
                    <div>
                        <h2 class="text-base font-bold text-white">Status Session Lokal Aplikasi Eksternal</h2>
                        <span class="text-xs text-slate-400">Database & Session Mandiri {{ $app->name }}</span>
                    </div>

                    @if($localSession)
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/30 flex items-center space-x-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>LOGGED IN (Session Lokal Aktif)</span>
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-rose-500/10 text-rose-400 border border-rose-500/30 flex items-center space-x-2">
                            <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                            <span>NOT LOGGED IN (Belum Ada Session)</span>
                        </span>
                    @endif
                </div>

                @if($localSession)
                    <!-- Logged In User Payload View -->
                    <div class="p-5 rounded-2xl bg-slate-950 border border-slate-800 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-indigo-600 to-purple-600 text-white font-black text-lg flex items-center justify-center shadow-lg shadow-indigo-600/30">
                                    {{ substr($localSession['user']['name'] ?? 'U', 0, 1) }}
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-white">{{ $localSession['user']['name'] }}</h3>
                                    <span class="text-xs text-slate-400 font-mono">{{ $localSession['user']['email'] }}</span>
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-bold uppercase bg-amber-500/10 text-amber-400 border border-amber-500/20">
                                {{ $localSession['user']['role'] }}
                            </span>
                        </div>

                        <div class="pt-3 border-t border-slate-800 space-y-4 text-xs">
                            <!-- Password Policy Notice Card -->
                            <div class="p-4 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 space-y-1">
                                <div class="flex items-center space-x-2 font-bold text-amber-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                    <span>Aturan Penggantian Password (SiPintu Gateway Policy)</span>
                                </div>
                                <p class="text-[11px] leading-relaxed text-amber-200">
                                    User <strong>WAJIB mengganti password di SiPintu Gateway</strong>. Penggantian password di aplikasi eksternal ini tidak diizinkan. Password hash lokal otomatis disinkronkan dari SiPintu API (<code>{{ substr($localSession['synced_password'] ?? '********', 0, 20) }}...</code>).
                                </p>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-indigo-300 font-bold">1. Payload Identitas & Profile SIJUNA (GET /api/v1/user/profile):</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] bg-indigo-500/20 text-indigo-300 font-mono">Gateway API Proxy</span>
                                </div>
                                <pre class="p-4 rounded-xl bg-slate-900 border border-slate-800 font-mono text-indigo-300 text-xs overflow-x-auto select-all max-h-48">{{ json_encode($localSession['user'], JSON_PRETTY_PRINT) }}</pre>
                            </div>

                            @if(isset($localSession['sijuna_students']))
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-amber-300 font-bold">2. Data Siswa SIJUNA via Gateway (GET /api/v1/sijuna/students):</span>
                                    <span class="px-2 py-0.5 rounded text-[10px] bg-amber-500/20 text-amber-300 font-mono">Redis Cached via Gateway</span>
                                </div>
                                <pre class="p-4 rounded-xl bg-slate-900 border border-slate-800 font-mono text-amber-300 text-xs overflow-x-auto select-all max-h-48">{{ json_encode($localSession['sijuna_students'], JSON_PRETTY_PRINT) }}</pre>
                            </div>
                            @endif
                        </div>

                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <a href="{{ route('demo.logout', ['appSlug' => $appSlug]) }}" class="px-5 py-2.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 font-bold text-xs rounded-xl border border-rose-500/30 transition-all">
                            Tutup Session Lokal (Logout {{ $app->name }})
                        </a>

                        <a href="{{ route('demo.login', ['appSlug' => $appSlug]) }}" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl transition-all">
                            Tes Re-Authenticate via SSO
                        </a>
                    </div>
                @else
                    <!-- Not Logged In Screen -->
                    <div class="p-8 text-center bg-slate-950/60 rounded-2xl border border-dashed border-slate-800 space-y-4">
                        <div class="w-12 h-12 rounded-full bg-slate-800 text-slate-400 flex items-center justify-center mx-auto">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-white">User Belum Terautentikasi di {{ $app->name }}</h3>
                            <p class="text-xs text-slate-400 mt-1 max-w-md mx-auto">
                                Saat membuka aplikasi eksternal ini secara langsung, sistem mendeteksi belum ada session lokal. Pengguna akan diarahkan secara transparan ke SiPintu.
                            </p>
                        </div>

                        <a href="{{ route('demo.login', ['appSlug' => $appSlug]) }}" class="inline-block py-3 px-6 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-400 hover:to-orange-400 text-slate-950 font-black rounded-xl shadow-lg shadow-amber-500/20 text-xs transition-all transform active:scale-95">
                            Buka Aplikasi & Redirect ke Gateway SSO &rarr;
                        </a>
                    </div>
                @endif
            </div>

            <!-- Right Side: Config & Client Information -->
            <div class="p-6 rounded-3xl bg-slate-900 border border-slate-800 space-y-4 text-xs">
                <h3 class="text-sm font-bold text-white border-b border-slate-800 pb-3">Konfigurasi OAuth Client</h3>

                <div class="space-y-3">
                    <div>
                        <span class="text-slate-400 block mb-0.5">Client ID Aplikasi:</span>
                        <code class="text-indigo-300 font-mono font-bold block p-2 bg-slate-950 rounded-lg border border-slate-800 truncate">{{ $app->client_id }}</code>
                    </div>

                    <div>
                        <span class="text-slate-400 block mb-0.5">Redirect URI Callback:</span>
                        <code class="text-slate-300 font-mono text-[11px] block p-2 bg-slate-950 rounded-lg border border-slate-800 truncate">{{ route('demo.callback', ['appSlug' => $appSlug]) }}</code>
                    </div>

                    <div>
                        <span class="text-slate-400 block mb-0.5">Allowed Roles di Gateway:</span>
                        <div class="flex flex-wrap gap-1 mt-1">
                            @foreach($app->roles as $role)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                    {{ $role->slug }}
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-800">
                    <a href="{{ route('admin.dashboard') }}" class="block w-full text-center py-2.5 px-4 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold rounded-xl transition-all">
                        &larr; Ke Admin Panel Gateway
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
