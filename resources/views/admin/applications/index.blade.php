@extends('layouts.app', ['headerTitle' => 'Application Registry'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white">Registry Aplikasi Eksternal & OAuth Clients</h2>
            <p class="text-xs text-slate-400 mt-1">Daftarkan aplikasi eksternal dan tentukan role yang boleh mengakses</p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.categories.index') }}" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold rounded-xl transition-all border border-slate-700 flex items-center space-x-2">
                <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <span>Kelola Kategori</span>
            </a>

            <a href="{{ route('admin.applications.create') }}" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-all shadow-lg shadow-indigo-600/30 flex items-center space-x-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>+ Daftarkan Aplikasi Baru</span>
            </a>
        </div>
    </div>

    @if(session('new_client_secret'))
        <div class="p-6 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-slate-200 space-y-3">
            <div class="flex items-center space-x-3 text-amber-400 font-bold text-sm">
                <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span>IMPORTANT: Client Secret Aplikasi {{ session('new_client_name') }}</span>
            </div>
            <p class="text-xs text-slate-300">
                Simpan Client Secret ini sekarang di file <code>.env</code> aplikasi eksternal Anda. Demi keamanan, Client Secret ini tidak akan ditampilkan lagi setelah halaman ini ditutup.
            </p>
            <div class="p-3 bg-slate-950 rounded-xl border border-slate-800 flex items-center justify-between font-mono text-sm text-emerald-400 select-all">
                <span>{{ session('new_client_secret') }}</span>
                <button type="button" onclick="navigator.clipboard.writeText('{{ session('new_client_secret') }}'); alert('Client Secret berhasil disalin!');" class="text-xs text-slate-400 hover:text-white font-sans bg-slate-800 px-3 py-1 rounded">
                    Salin Secret
                </button>
            </div>
        </div>
    @endif

    <div class="bg-slate-900 rounded-2xl border border-slate-800 overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-950 text-slate-400 uppercase font-bold text-[10px] border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Nama Aplikasi</th>
                        <th class="px-6 py-4">Kategori</th>
                        <th class="px-6 py-4">Client ID</th>
                        <th class="px-6 py-4">Base URL & Redirect URI</th>
                        <th class="px-6 py-4">Role Akses Diizinkan</th>
                        <th class="px-6 py-4">Status & Health</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($applications as $app)
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="font-bold text-white text-sm">{{ $app->name }}</div>
                                <div class="text-slate-400 text-xs truncate max-w-xs">{{ $app->description }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($app->category)
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                                        {{ $app->category->name }}
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-lg text-[10px] font-medium bg-slate-800 text-slate-400">
                                        Umum
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-mono text-indigo-300">
                                {{ $app->client_id }}
                            </td>
                            <td class="px-6 py-4 text-slate-300">
                                <div class="font-mono text-xs text-slate-200">{{ $app->base_url }}</div>
                                <div class="text-[11px] text-slate-400 truncate max-w-xs font-mono">{{ $app->redirect_uri }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($app->roles as $role)
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-500/10 text-indigo-300 border border-indigo-500/20 uppercase">
                                            {{ $role->slug }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $app->status === 'active' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-slate-800 text-slate-400' }}">
                                    {{ $app->status }}
                                </span>
                                @if($app->last_health_status)
                                    <div class="mt-1 text-[10px] text-slate-400">
                                        Health: <span class="font-bold uppercase {{ $app->last_health_status === 'online' ? 'text-emerald-400' : 'text-rose-400' }}">{{ $app->last_health_status }}</span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                                <form action="{{ route('admin.applications.test-health', $app) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-[11px] font-semibold">
                                        Test Health
                                    </button>
                                </form>

                                <form action="{{ route('admin.applications.regenerate-secret', $app) }}" method="POST" class="inline" onsubmit="return confirm('Buat ulang Client Secret untuk aplikasi {{ $app->name }}?')">
                                    @csrf
                                    <button type="submit" class="px-2.5 py-1.5 bg-amber-500/10 hover:bg-amber-500/20 text-amber-400 rounded-lg text-[11px] font-semibold">
                                        Reset Secret
                                    </button>
                                </form>

                                <a href="{{ route('admin.applications.edit', $app) }}" class="px-2.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-indigo-300 rounded-lg text-[11px] font-semibold">
                                    Edit
                                </a>

                                <form action="{{ route('admin.applications.destroy', $app) }}" method="POST" class="inline" onsubmit="return confirm('Hapus aplikasi {{ $app->name }} dari registry?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2.5 py-1.5 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 rounded-lg text-[11px] font-semibold">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">
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
