@extends('layouts.app', ['title' => 'Log Error Server (500) - SiPintu Admin', 'headerTitle' => 'Log Error Server (500)'])

@section('content')
<div class="space-y-6" x-data="{ selectedIds: [], selectAll: false }">
    
    <!-- Top Alert / Flash Messages -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs font-bold flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2.5">
                <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900 p-1">&times;</button>
        </div>
    @endif

    @if(session('warning'))
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-bold flex items-center justify-between shadow-xs">
            <div class="flex items-center space-x-2.5">
                <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>{{ session('warning') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-amber-700 hover:text-amber-900 p-1">&times;</button>
        </div>
    @endif

    <!-- Header & Action Buttons -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-xs">
        <div>
            <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center font-black">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <h2 class="text-lg font-black text-slate-900 tracking-tight">Log Error & Insiden Sistem (500)</h2>
                    <p class="text-xs text-slate-500 font-medium">Pencatatan otomatis seluruh kendala teknis internal, unhandled exceptions, dan notifikasi real-time untuk admin.</p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5">
            <!-- Trigger Test 500 Error -->
            <form action="{{ route('admin.error-logs.trigger-test') }}" method="POST" onsubmit="return confirm('Jalankan simulasi error server 500 untuk memvalidasi pencatatan otomatis dan pengiriman notifikasi?')">
                @csrf
                <button type="submit" class="px-3.5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all border border-slate-300 flex items-center gap-1.5 shadow-xs cursor-pointer" title="Uji Coba Sistem Notifikasi & Pencatatan">
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    <span>Simulasi Tes Error 500</span>
                </button>
            </form>

            <!-- Clear Resolved Logs -->
            @if($stats['resolved'] > 0)
            <form action="{{ route('admin.error-logs.clear-resolved') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus seluruh riwayat error yang telah selesai/diabaikan?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-3.5 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold transition-all border border-rose-200 flex items-center gap-1.5 shadow-xs cursor-pointer">
                    <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    <span>Bersihkan Log Selesai</span>
                </button>
            </form>
            @endif
        </div>
    </div>

    <!-- Statistics Overview Grid -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4">
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs space-y-1">
            <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider block">Total Insiden</span>
            <div class="text-2xl font-black text-slate-900">{{ number_format($stats['total']) }}</div>
            <span class="text-[10px] text-slate-500 font-medium">Keseluruhan error</span>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border {{ $stats['unresolved'] > 0 ? 'border-rose-300 bg-rose-50/30' : 'border-slate-200' }} shadow-xs space-y-1">
            <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold text-rose-700 uppercase tracking-wider block">Belum Selesai</span>
                @if($stats['unresolved'] > 0)
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-600 animate-ping"></span>
                @endif
            </div>
            <div class="text-2xl font-black text-rose-700">{{ number_format($stats['unresolved']) }}</div>
            <span class="text-[10px] text-rose-600 font-bold">Memerlukan tindakan</span>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs space-y-1">
            <span class="text-[11px] font-extrabold text-emerald-700 uppercase tracking-wider block">Sudah Selesai</span>
            <div class="text-2xl font-black text-emerald-700">{{ number_format($stats['resolved']) }}</div>
            <span class="text-[10px] text-emerald-600 font-medium">Telah ditangani admin</span>
        </div>

        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs space-y-1">
            <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider block">Hari Ini</span>
            <div class="text-2xl font-black text-slate-900">{{ number_format($stats['today']) }}</div>
            <span class="text-[10px] text-slate-500 font-medium">24 jam terakhir</span>
        </div>

        <div class="col-span-2 lg:col-span-1 bg-white p-4 sm:p-5 rounded-2xl border border-slate-200 shadow-xs space-y-1">
            <span class="text-[11px] font-extrabold text-slate-400 uppercase tracking-wider block">User Terdampak</span>
            <div class="text-2xl font-black text-slate-900">{{ number_format($stats['affected_users']) }}</div>
            <span class="text-[10px] text-slate-500 font-medium">Akun siswa/guru/dudi</span>
        </div>
    </div>

    <!-- Filter and Search Toolbar -->
    <div class="bg-white p-4 sm:p-5 rounded-3xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('admin.error-logs.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-center">
            
            <!-- Search -->
            <div class="lg:col-span-4 relative">
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode insiden, endpoint, error, pesan..." 
                       class="w-full pl-9 pr-4 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-emerald-600 focus:border-emerald-600 font-medium">
            </div>

            <!-- Status Filter -->
            <div class="lg:col-span-2">
                <select name="status" class="w-full px-3 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-emerald-600 focus:border-emerald-600 font-medium">
                    <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>Semua Status</option>
                    <option value="unresolved" {{ request('status', 'all') === 'unresolved' ? 'selected' : '' }}>Belum Selesai</option>
                    <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Sudah Selesai</option>
                    <option value="ignored" {{ request('status') === 'ignored' ? 'selected' : '' }}>Diabaikan</option>
                </select>
            </div>

            <!-- Status Code Filter -->
            <div class="lg:col-span-2">
                <select name="status_code" class="w-full px-3 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-emerald-600 focus:border-emerald-600 font-medium">
                    <option value="all" {{ request('status_code') === 'all' ? 'selected' : '' }}>Semua Kode HTTP</option>
                    <option value="500" {{ request('status_code') === '500' ? 'selected' : '' }}>500 (Internal Server)</option>
                    <option value="502" {{ request('status_code') === '502' ? 'selected' : '' }}>502 (Bad Gateway)</option>
                    <option value="503" {{ request('status_code') === '503' ? 'selected' : '' }}>503 (Service Unavailable)</option>
                </select>
            </div>

            <!-- Role Filter -->
            <div class="lg:col-span-2">
                <select name="role" class="w-full px-3 py-2.5 text-xs rounded-xl border border-slate-200 focus:outline-emerald-600 focus:border-emerald-600 font-medium">
                    <option value="all" {{ request('role') === 'all' ? 'selected' : '' }}>Semua Role</option>
                    <option value="student" {{ request('role') === 'student' ? 'selected' : '' }}>Siswa</option>
                    <option value="alumni" {{ request('role') === 'alumni' ? 'selected' : '' }}>Alumni</option>
                    <option value="teacher" {{ request('role') === 'teacher' ? 'selected' : '' }}>Guru</option>
                    <option value="dudi" {{ request('role') === 'dudi' ? 'selected' : '' }}>Mitra DUDI</option>
                    <option value="guest" {{ request('role') === 'guest' ? 'selected' : '' }}>Tamu / Publik</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </div>

            <!-- Submit & Reset -->
            <div class="lg:col-span-2 flex items-center gap-2">
                <button type="submit" class="flex-1 py-2.5 px-3 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-bold transition-all shadow-xs text-center cursor-pointer">
                    Filter
                </button>
                @if(request()->anyFilled(['search', 'status', 'status_code', 'role']))
                <a href="{{ route('admin.error-logs.index') }}" class="p-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition-all text-center" title="Reset Filter">
                    &times;
                </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Batch Action Toolbar -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-100/70 p-3.5 rounded-2xl border border-slate-200">
        <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-slate-700">Aksi Cepat Massal:</span>
            <form action="{{ route('admin.error-logs.batch-resolve') }}" method="POST" onsubmit="return confirm('Tandai seluruh error berstatus Belum Selesai sebagai Selesai?')">
                @csrf
                <input type="hidden" name="resolve_all_unresolved" value="1">
                <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold transition-all shadow-xs cursor-pointer">
                    Selesaikan Semua ({{ $stats['unresolved'] }})
                </button>
            </form>
        </div>

        <form action="{{ route('admin.error-logs.batch-resolve') }}" method="POST" class="flex items-center gap-2" x-show="selectedIds.length > 0" x-cloak>
            @csrf
            <template x-for="id in selectedIds" :key="id">
                <input type="hidden" name="ids[]" :value="id">
            </template>
            <span class="text-xs font-bold text-slate-600" x-text="selectedIds.length + ' item terpilih'"></span>
            <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold transition-all shadow-xs cursor-pointer">
                Tandai Terpilih Selesai
            </button>
        </form>
    </div>

    <!-- Error Logs Table Card -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 border-b border-slate-200 text-[10px] uppercase font-extrabold text-slate-500 tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 w-10 text-center">
                            <input type="checkbox" @change="selectAll = !selectAll; selectedIds = selectAll ? [{{ $errorLogs->pluck('id')->join(',') }}] : []" 
                                   class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        </th>
                        <th class="py-3.5 px-4">Kode & Status</th>
                        <th class="py-3.5 px-4">Pesan & Exception</th>
                        <th class="py-3.5 px-4">Endpoint / URL</th>
                        <th class="py-3.5 px-4">Pengguna</th>
                        <th class="py-3.5 px-4 text-center">Frekuensi</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($errorLogs as $log)
                        @php
                            $badge = $log->getStatusBadge();
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition-colors {{ $log->status === 'unresolved' ? 'bg-rose-50/15' : '' }}">
                            <!-- Checkbox -->
                            <td class="py-3.5 px-4 text-center">
                                <input type="checkbox" :value="{{ $log->id }}" x-model="selectedIds"
                                       class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                            </td>

                            <!-- Incident Code & HTTP Status -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <a href="{{ route('admin.error-logs.show', $log->id) }}" class="font-mono font-bold text-slate-900 hover:text-emerald-700 block transition-colors">
                                    {{ $log->incident_code }}
                                </a>
                                <div class="flex items-center gap-1.5 mt-1">
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-black uppercase {{ $log->getSeverityClass() }}">
                                        {{ $log->method }} {{ $log->status_code }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 font-medium">
                                        {{ $log->created_at->format('H:i') }} WIB
                                    </span>
                                </div>
                            </td>

                            <!-- Error Type & Message -->
                            <td class="py-3.5 px-4 max-w-xs md:max-w-md">
                                <span class="font-extrabold text-slate-900 block truncate" title="{{ $log->error_type }}">
                                    {{ $log->error_type }}
                                </span>
                                <p class="text-[11px] text-slate-500 line-clamp-2 mt-0.5 font-medium leading-relaxed">
                                    {{ $log->message }}
                                </p>
                                @if($log->file)
                                    <span class="text-[10px] font-mono text-slate-400 block truncate mt-0.5" title="{{ $log->file }}:{{ $log->line }}">
                                        {{ basename($log->file) }}:{{ $log->line }}
                                    </span>
                                @endif
                            </td>

                            <!-- Endpoint / URL -->
                            <td class="py-3.5 px-4 max-w-xs">
                                <span class="font-mono text-[11px] text-slate-700 block truncate" title="{{ $log->url }}">
                                    {{ parse_url($log->url, PHP_URL_PATH) ?? $log->url }}
                                </span>
                                <span class="text-[10px] text-slate-400 block mt-0.5 font-mono">
                                    IP: {{ $log->ip_address ?? '-' }}
                                </span>
                            </td>

                            <!-- Affected User -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($log->user)
                                    <div class="flex items-center gap-2">
                                        @if($log->user->avatar_url)
                                            <img src="{{ $log->user->avatar_url }}" class="w-6 h-6 rounded-full object-cover border border-slate-200 shrink-0">
                                        @else
                                            <div class="w-6 h-6 rounded-full bg-slate-100 text-slate-600 font-bold text-[10px] flex items-center justify-center shrink-0">
                                                {{ strtoupper(substr($log->user->name, 0, 1)) }}
                                            </div>
                                        @endif
                                        <div class="truncate max-w-[120px]">
                                            <span class="font-bold text-slate-800 text-xs block truncate">{{ $log->user->name }}</span>
                                            <span class="text-[10px] text-emerald-700 capitalize font-medium">{{ $log->user->getUserTypeName() }}</span>
                                        </div>
                                    </div>
                                @else
                                @endif
                            </td>

                            <!-- Occurrence Count -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-black {{ $log->occurrence_count > 1 ? 'bg-amber-100 text-amber-900 border border-amber-200' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $log->occurrence_count }}x
                                </span>
                                <span class="block text-[9px] text-slate-400 mt-0.5">
                                    {{ $log->last_seen_at ? $log->last_seen_at->diffForHumans() : $log->created_at->diffForHumans() }}
                                </span>
                            </td>

                            <!-- Status Badge -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider border {{ $badge['class'] }}">
                                    {{ $badge['label'] }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('admin.error-logs.show', $log->id) }}" 
                                       class="p-2 rounded-xl bg-slate-100 hover:bg-emerald-50 text-slate-600 hover:text-emerald-700 transition-colors" title="Lihat Detail & Stack Trace">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>

                                    @if($log->status === 'unresolved')
                                    <form action="{{ route('admin.error-logs.resolve', $log->id) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="p-2 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 transition-colors cursor-pointer" title="Tandai Sudah Selesai">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </form>
                                    @endif

                                    <form action="{{ route('admin.error-logs.destroy', $log->id) }}" method="POST" onsubmit="return confirm('Hapus log error ini dari sistem?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 rounded-xl bg-slate-100 hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition-colors cursor-pointer" title="Hapus Log">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400 space-y-2">
                                <div class="w-12 h-12 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center mx-auto">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                </div>
                                <p class="text-sm font-bold text-slate-700">Tidak ada log error server ditemukan.</p>
                                <p class="text-xs text-slate-500">Seluruh layanan SiPintu beroperasi dengan baik dan lancar.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($errorLogs->hasPages())
        <div class="p-4 border-t border-slate-100 bg-slate-50/50">
            {{ $errorLogs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
