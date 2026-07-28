@extends('layouts.app', ['headerTitle' => 'Aplikasi SSO Terintegrasi Siswa'])

@section('content')
<div class="space-y-6">
    <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 space-y-4">
        <div>
            <h3 class="text-base font-bold text-white">Registry Aplikasi SSO Siswa</h3>
            <p class="text-xs text-slate-400 mt-0.5">Daftar layanan dan aplikasi eksternal yang dapat diakses dengan akun Siswa Anda</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
            @forelse($applications as $app)
                <div class="p-5 rounded-xl bg-slate-950/80 border border-slate-800 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-bold text-white">{{ $app->name }}</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">Aktif</span>
                    </div>
                    <p class="text-xs text-slate-400">{{ $app->description }}</p>
                    <div class="pt-3 border-t border-slate-800 flex justify-end">
                        <a href="{{ route('oauth.authorize', ['client_id' => $app->client_id, 'redirect_uri' => $app->redirect_uri, 'response_type' => 'code', 'scope' => 'openid profile email']) }}" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-lg transition-all">
                            Masuk via SSO &rarr;
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-3 p-6 text-center text-xs text-slate-400 bg-slate-950/40 rounded-xl border border-slate-800">
                    Tidak ada aplikasi eksternal yang terdaftar untuk role Siswa.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
