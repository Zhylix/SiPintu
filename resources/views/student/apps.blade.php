@extends('layouts.app', ['headerTitle' => 'Katalog Aplikasi Siswa'])

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-xl font-bold text-white tracking-tight">Katalog Aplikasi SSO Siswa</h3>
            <p class="text-xs text-slate-400 mt-1">Gunakan Single Sign-On (SSO) untuk mengakses seluruh aplikasi sekolah dan mitra secara langsung.</p>
        </div>
    </div>

    @include('partials.app-catalog-grid')
</div>
@endsection
