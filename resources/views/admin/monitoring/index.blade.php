@extends('layouts.app', ['headerTitle' => 'System Monitoring'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-white">System Monitoring & Application Health Check</h2>
            <p class="text-xs text-slate-400 mt-1">Pantau status koneksi database, cache Redis, queue, dan kesehatan aplikasi eksternal</p>
        </div>

        <form action="{{ route('admin.monitoring.run-health-checks') }}" method="POST">
            @csrf
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-all shadow-md shadow-indigo-600/30">
                Refresh Health Checks
            </button>
        </form>
    </div>

    <!-- Core Infrastructure Status -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Database Primary (MySQL)</span>
            <div class="flex items-center space-x-2">
                <span class="w-3 h-3 rounded-full bg-emerald-400 animate-ping"></span>
                <span class="text-lg font-bold text-white">{{ $dbStatus }}</span>
            </div>
            <p class="text-xs text-slate-400">Pusat data identitas, roles, permissions, dan token</p>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Cache & Identity Store (Redis)</span>
            <div class="flex items-center space-x-2">
                <span class="w-3 h-3 rounded-full bg-emerald-400"></span>
                <span class="text-lg font-bold text-white">{{ $redisStatus }}</span>
            </div>
            <p class="text-xs text-slate-400">Cache identitas siswa <code>user:{external_id}</code></p>
        </div>

        <div class="p-5 rounded-2xl bg-slate-900 border border-slate-800 space-y-2">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Sesi Token SSO Aktif</span>
            <div class="text-2xl font-black text-indigo-400">{{ number_format($activeTokens) }}</div>
            <p class="text-xs text-slate-400">Access Tokens OAuth 2.0 yang sedang berlaku</p>
        </div>
    </div>

    <!-- External Applications Health Status Matrix -->
    <div class="bg-slate-900 rounded-2xl border border-slate-800 p-6 space-y-4">
        <h3 class="text-base font-bold text-white">Status Kesehatan Aplikasi Eksternal (Health Checks)</h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @foreach($applications as $app)
                <div class="p-4 rounded-xl bg-slate-950/80 border border-slate-800 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-white text-sm">{{ $app->name }}</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase
                            {{ $app->last_health_status === 'online' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' }}">
                            {{ $app->last_health_status ?? 'UNKNOWN' }}
                        </span>
                    </div>

                    <div class="text-xs text-slate-400 font-mono truncate" title="{{ $app->health_check_url }}">
                        Health URL: {{ $app->health_check_url ?: 'Belum diatur' }}
                    </div>

                    <div class="text-[11px] text-slate-400 flex justify-between pt-2 border-t border-slate-800">
                        <span>Pengecekan Terakhir:</span>
                        <span class="text-slate-200">{{ $app->last_health_check_at?->diffForHumans() ?? 'Belum pernah' }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
