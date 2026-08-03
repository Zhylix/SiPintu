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
                        <th class="px-6 py-4">Status & Health</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white font-sans">
                    @forelse($applications as $app)
                        <tr class="hover:bg-emerald-50/50 transition-colors">
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
                            <td class="px-6 py-4 whitespace-nowrap font-mono font-bold text-emerald-800">
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
                                <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase {{ $app->status === 'active' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $app->status }}
                                </span>
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
