@extends('layouts.app', ['headerTitle' => 'Katalog Aplikasi Mitra DUDI'])

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-xl font-bold text-white tracking-tight">Katalog Aplikasi Mitra DUDI</h3>
            <p class="text-xs text-slate-400 mt-1">Layanan terintegrasi dan sistem kemitraan industri yang dapat diakses oleh instansi Mitra DUDI.</p>
        </div>
    </div>

    @include('partials.app-catalog-grid')
</div>
@endsection
