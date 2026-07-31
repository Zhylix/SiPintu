@extends('layouts.app', ['headerTitle' => 'Katalog Aplikasi Terpadu (SSO) Siswa'])

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-xl font-black text-emerald-950 tracking-tight">Katalog Aplikasi Terpadu (SSO) Siswa</h3>
            <p class="text-xs text-slate-600 font-medium mt-1">Gunakan layanan Akses Terpadu untuk mengakses seluruh aplikasi sekolah dan mitra secara langsung.</p>
        </div>
    </div>

    @include('partials.app-catalog-grid')
</div>
@endsection
