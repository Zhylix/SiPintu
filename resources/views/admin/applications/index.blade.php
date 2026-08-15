@extends('layouts.app', ['headerTitle' => 'Application Registry'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-emerald-950">Registry Aplikasi Eksternal & OAuth Clients</h2>
            <p class="text-xs text-slate-600 font-medium mt-1">Daftarkan aplikasi eksternal dan tentukan role yang boleh mengakses</p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.categories.index') }}" class="px-4 py-2.5 bg-slate-100 hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 text-xs font-bold rounded-xl transition-all border border-slate-200 flex items-center space-x-2">
                <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <span>Kelola Kategori</span>
            </a>

            <a href="{{ route('admin.applications.create') }}" class="px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-700/20 flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>+ Daftarkan Aplikasi Baru</span>
            </a>
        </div>
    </div>

    @if(session('new_client_secret'))
        <div class="p-6 rounded-2xl bg-amber-50 border border-amber-200 text-slate-800 space-y-3">
            <div class="flex items-center space-x-3 text-amber-800 font-black text-sm">
                <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span>IMPORTANT: Client Secret Aplikasi {{ session('new_client_name') }}</span>
            </div>
            <p class="text-xs text-slate-600 font-medium">
                Simpan Client Secret ini sekarang di file <code>.env</code> aplikasi eksternal Anda. Demi keamanan, Client Secret ini tidak akan ditampilkan lagi setelah halaman ini ditutup.
            </p>
            <div class="p-3 bg-white rounded-xl border border-amber-200 flex items-center justify-between font-mono text-sm text-emerald-800 font-bold select-all">
                <span>{{ session('new_client_secret') }}</span>
                <button type="button" onclick="navigator.clipboard.writeText('{{ session('new_client_secret') }}'); alert('Client Secret berhasil disalin!');" class="text-xs text-emerald-800 font-sans bg-emerald-100 border border-emerald-300 px-3 py-1 rounded-lg font-bold">
                    Salin Secret
                </button>
            </div>
        </div>
    @endif

    <!-- Patokan Standar Integrasi & Indikator -->
    <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-4 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-200 pb-3">
            <div class="flex items-center space-x-2.5 font-black text-sm tracking-tight text-emerald-950">
                <svg class="w-5 h-5 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>Patokan Standar Integrasi Klien SSO SiPintu</span>
            </div>
            <span class="text-[10px] font-mono bg-white text-slate-700 px-2.5 py-0.5 rounded-full border border-slate-300 font-bold self-start sm:self-auto">
                OAuth 2.0 / OpenID Connect Specification
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs">
                <span class="text-emerald-700 font-extrabold block text-[11px] uppercase">Terkoneksi</span>
                <p class="text-slate-600 mt-1 text-[11px] leading-relaxed">Aplikasi `ACTIVE`, Client ID terverifikasi, & Health Check HTTP 200 merespons.</p>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs">
                <span class="text-rose-600 font-extrabold block text-[11px] uppercase">Terputus</span>
                <p class="text-slate-600 mt-1 text-[11px] leading-relaxed">Aplikasi `INACTIVE` atau URL Health Check tidak merespons (Offline).</p>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs">
                <span class="text-amber-700 font-extrabold block text-[11px] uppercase">Autentikasi Header</span>
                <p class="text-mono text-slate-700 mt-1 text-[11px] leading-relaxed">`X-Client-ID` & `X-Client-Secret` pada Server-to-Server API Gateway.</p>
            </div>
            <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-2xs">
                <span class="text-emerald-700 font-extrabold block text-[11px] uppercase">Target Response Time</span>
                <p class="text-slate-600 mt-1 text-[11px] leading-relaxed">Latency ideal < 200 ms untuk autentikasi SSO seamless.</p>
            </div>
    <!-- Filter & Search Bar -->
    <div class="p-4 bg-white rounded-2xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('admin.applications.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 w-full">
            <!-- Search -->
            <div class="sm:col-span-2 relative flex items-center gap-2">
                <div class="relative flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama aplikasi, client ID, URL..." 
                           class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-semibold placeholder-slate-400 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <button type="submit" class="px-3.5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-700/20 shrink-0 flex items-center gap-1">
                    <span>Cari</span>
                </button>
            </div>

            <!-- Category Filter -->
            <select name="category_id" onchange="this.form.submit()" class="w-full bg-slate-50 border border-slate-200 text-xs text-slate-900 font-semibold rounded-xl px-3 py-2 focus:outline-none focus:border-emerald-600">
                <option value="all">Semua Kategori</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                @endforeach
            </select>

            <!-- Status Filter & Reset -->
            <div class="flex items-center gap-2">
                <select name="status" onchange="this.form.submit()" class="w-full bg-slate-50 border border-slate-200 text-xs text-slate-900 font-semibold rounded-xl px-3 py-2 focus:outline-none focus:border-emerald-600">
                    <option value="all">Semua Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="maintenance" {{ request('status') === 'maintenance' ? 'selected' : '' }}>Maintenance</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>

                @if(request()->anyFilled(['search', 'category_id', 'status']))
                    <a href="{{ route('admin.applications.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-200 flex items-center justify-center shrink-0">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-emerald-50 text-emerald-900 uppercase font-black text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4">Nama Aplikasi</th>
                        <th class="px-6 py-4">Kategori</th>
                        <th class="px-6 py-4">Client ID</th>
                        <th class="px-6 py-4">Base URL & Redirect URI</th>
                        <th class="px-6 py-4">Role Akses Diizinkan</th>
                        <th class="px-6 py-4">Status Koneksi SSO</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white font-sans">
                    @forelse($applications as $app)
                        @php
                            $isConnected = $app->status === 'active' && ($app->last_health_status !== 'offline');
                        @endphp
                        <tr class="hover:bg-emerald-50/50 transition-colors {{ $isConnected ? '' : 'bg-rose-50/30' }}">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-bold text-slate-900 text-sm">{{ $app->name }}</div>
                                <div class="text-slate-600 text-xs truncate max-w-xs font-medium">{{ $app->description }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($app->category)
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                        {{ $app->category->name }}
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        Umum
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-mono font-bold text-emerald-800 select-all">
                                {{ $app->client_id }}
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                <div class="font-mono text-xs font-bold text-slate-800">{{ $app->base_url }}</div>
                                <div class="text-[11px] text-slate-500 truncate max-w-xs font-mono font-medium">{{ $app->redirect_uri }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($app->roles as $role)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300 uppercase">
                                            {{ $role->getDisplayName() }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($isConnected)
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-800 border border-emerald-300 inline-flex items-center gap-1.5 shadow-2xs">
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                                        <span>BERHASIL TERKONEKSI</span>
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-rose-100 text-rose-800 border border-rose-300 inline-flex items-center gap-1">
                                        <span class="w-2 h-2 rounded-full bg-rose-600"></span>
                                        <span>TERPUTUS / PROBLEM</span>
                                    </span>
                                @endif
                                
                                @if($app->last_health_status)
                                    <div class="mt-1 text-[10px] text-slate-500 font-semibold">
                                        Health: <span class="font-bold uppercase {{ $app->last_health_status === 'online' ? 'text-emerald-700' : 'text-rose-600' }}">{{ $app->last_health_status }}</span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                                <form action="{{ route('admin.applications.test-health', $app) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-[11px] font-bold border border-slate-200">
                                        Test Health
                                    </button>
                                </form>

                                <form action="{{ route('admin.applications.regenerate-secret', $app) }}" method="POST" class="inline" onsubmit="return confirm('Buat ulang Client Secret untuk aplikasi {{ $app->name }}?')">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 rounded-lg text-[11px] font-bold">
                                        Reset Secret
                                    </button>
                                </form>

                                <a href="{{ route('admin.applications.edit', $app) }}" class="px-2.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-lg text-[11px] font-bold">
                                    Edit
                                </a>

                                <form action="{{ route('admin.applications.destroy', $app) }}" method="POST" class="inline" onsubmit="return confirm('Hapus aplikasi {{ $app->name }} dari registry?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 rounded-lg text-[11px] font-bold">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-500 font-semibold">
                                Belum ada aplikasi eksternal yang terdaftar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
