@extends('layouts.app', ['headerTitle' => 'Aplikasi Terpadu (SSO) Guru'])

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-xl font-black text-emerald-950 tracking-tight">Katalog Aplikasi Terpadu (SSO) Guru</h3>
            <p class="text-xs text-slate-600 font-medium mt-1">Akses portal presensi, jurnal mengajar, dan evaluasi siswa via Akses Terpadu (SSO).</p>
        </div>
    </div>

    @include('partials.app-catalog-grid')
</div>
@endsection
