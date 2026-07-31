@extends('layouts.app', ['headerTitle' => 'System Monitoring'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
        <div>
            <h2 class="text-xl font-black text-emerald-950">System Monitoring & Application Health Check</h2>
            <p class="text-xs text-slate-600 font-medium mt-1">Pantau status koneksi database, cache Redis, queue, dan kesehatan aplikasi eksternal</p>
        </div>

        <form action="{{ route('admin.monitoring.run-health-checks') }}" method="POST">
            @csrf
            <button type="submit" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-700/20">
                Refresh Health Checks
            </button>
        </form>
    </div>

    <!-- Core Infrastructure Status -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-2">
            <span class="text-xs font-extrabold text-slate-500 uppercase tracking-wider block">Database Primary (MySQL)</span>
            <div class="flex items-center space-x-2">
                <span class="w-3 h-3 rounded-full bg-emerald-600 animate-ping"></span>
                <span class="text-lg font-black text-emerald-950">{{ $dbStatus }}</span>
            </div>
            <p class="text-xs text-slate-600 font-medium">Pusat data identitas, roles, permissions, dan token</p>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-2">
            <span class="text-xs font-extrabold text-slate-500 uppercase tracking-wider block">Cache & Identity Store (Redis)</span>
            <div class="flex items-center space-x-2">
                <span class="w-3 h-3 rounded-full bg-emerald-600"></span>
                <span class="text-lg font-black text-emerald-950">{{ $redisStatus }}</span>
            </div>
            <p class="text-xs text-slate-600 font-medium">Cache identitas siswa <code class="bg-slate-100 px-1 py-0.5 rounded text-emerald-800 font-mono font-bold">user:{external_id}</code></p>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-2">
            <span class="text-xs font-extrabold text-slate-500 uppercase tracking-wider block">Sesi Token Akses Terpadu Aktif</span>
            <div class="text-2xl font-black text-emerald-700">{{ number_format($activeTokens) }}</div>
            <p class="text-xs text-slate-600 font-medium">Access Tokens OAuth 2.0 yang sedang berlaku</p>
        </div>
    </div>

    <!-- External Applications Health Status Matrix -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-4 shadow-sm">
        <h3 class="text-base font-black text-emerald-950">Status Kesehatan Aplikasi Eksternal (Health Checks)</h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($applications as $app)
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-900 text-sm">{{ $app->name }}</span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase
                            {{ $app->last_health_status === 'online' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-rose-100 text-rose-800 border border-rose-300' }}">
                            {{ $app->last_health_status ?? 'UNKNOWN' }}
                        </span>
                    </div>

                    <div class="text-xs text-emerald-800 font-mono font-bold truncate" title="{{ $app->health_check_url }}">
                        Health URL: {{ $app->health_check_url ?: 'Belum diatur' }}
                    </div>

                    <div class="text-[11px] text-slate-600 font-medium flex justify-between pt-2 border-t border-slate-200">
                        <span>Pengecekan Terakhir:</span>
                        <span class="text-slate-900 font-bold">{{ $app->last_health_check_at ? \Illuminate\Support\Carbon::parse($app->last_health_check_at)->diffForHumans() : 'Belum pernah' }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
