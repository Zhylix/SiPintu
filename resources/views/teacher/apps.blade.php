@extends('layouts.app', ['headerTitle' => 'Katalog Aplikasi Guru'])

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-xl font-bold text-white tracking-tight">Katalog Aplikasi SSO Guru / Tenaga Pendidik</h3>
            <p class="text-xs text-slate-400 mt-1">Portal terpusat untuk mengakses aplikasi akademik, e-learning, dan administrasi sekolah.</p>
        </div>
    </div>

    @include('partials.app-catalog-grid')
</div>
@endsection
