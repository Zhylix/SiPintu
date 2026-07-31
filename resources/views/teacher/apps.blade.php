@extends('layouts.app', ['headerTitle' => 'Katalog Aplikasi'])

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-xl font-black text-emerald-950 tracking-tight">Katalog Aplikasi</h3>
            <p class="text-xs text-slate-600 font-medium mt-1">Akses portal presensi, jurnal mengajar, dan evaluasi siswa via Akses Terpadu.</p>
        </div>
    </div>

    @include('partials.app-catalog-grid')
</div>
@endsection
