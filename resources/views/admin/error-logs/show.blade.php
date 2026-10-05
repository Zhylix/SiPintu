@extends('layouts.app', ['title' => 'Detail Error ' . $errorLog->incident_code . ' - SiPintu Admin', 'headerTitle' => 'Detail Error Insiden'])

@section('content')
<div class="space-y-6" x-data="{ copySuccess: false, showNotesModal: false }">
    
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

    <!-- Back Button & Quick Actions Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200 shadow-xs">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.error-logs.index') }}" class="p-2.5 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-600 transition-colors" title="Kembali ke Daftar Error">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-base font-black text-slate-900">{{ $errorLog->incident_code }}</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase {{ $errorLog->getSeverityClass() }}">
                        {{ $errorLog->method }} {{ $errorLog->status_code }}
                    </span>
                    @php $badge = $errorLog->getStatusBadge(); @endphp
                    <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase border {{ $badge['class'] }}">
                        {{ $badge['label'] }}
                    </span>
                </div>
                <p class="text-xs text-slate-400 font-medium mt-0.5">
                    Terjadi pertama kali {{ $errorLog->created_at->format('d M Y, H:i:s') }} WIB &bull; Frekuensi: <strong>{{ $errorLog->occurrence_count }} kali</strong>
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Resolve Toggle Action -->
            @if($errorLog->status !== 'resolved')
                <button type="button" @click="showNotesModal = true" class="px-4 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold transition-all shadow-xs flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Tandai Selesai</span>
                </button>
            @else
                <form action="{{ route('admin.error-logs.unresolve', $errorLog->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all border border-slate-300 flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Kembalikan ke Belum Selesai</span>
                    </button>
                </form>
            @endif

            @if($errorLog->status !== 'ignored')
                <form action="{{ route('admin.error-logs.ignore', $errorLog->id) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="px-3.5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition-all border border-slate-200 cursor-pointer">
                        Abaikan
                    </button>
                </form>
            @endif

            <form action="{{ route('admin.error-logs.destroy', $errorLog->id) }}" method="POST" onsubmit="return confirm('Hapus permanen data log error ini?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="p-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 transition-colors cursor-pointer" title="Hapus Log">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </form>
        </div>
    </div>

    <!-- Error Summary Card (Exception & Message) -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-xs space-y-4">
        <div class="flex items-start justify-between gap-4">
            <div class="space-y-1">
                <span class="text-[10px] font-extrabold uppercase tracking-wider text-rose-700 bg-rose-50 px-2 py-0.5 rounded border border-rose-200">
                    {{ $errorLog->error_type }}
                </span>
                <h3 class="text-base sm:text-lg font-black text-slate-900 mt-2 leading-snug">
                    {{ $errorLog->message }}
                </h3>
            </div>
            
            <button type="button" 
                    @click="navigator.clipboard.writeText('{{ addslashes($errorLog->message) }}'); copySuccess = true; setTimeout(() => copySuccess = false, 2000)"
                    class="p-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 transition-colors text-xs font-bold flex items-center gap-1 shrink-0" title="Salin Pesan Error">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                <span x-show="!copySuccess">Salin</span>
                <span x-show="copySuccess" class="text-emerald-700 font-bold" x-cloak>Tersalin!</span>
            </button>
        </div>

        @if($errorLog->file)
        <div class="bg-slate-900 text-slate-100 p-4 rounded-2xl font-mono text-xs overflow-x-auto flex items-center justify-between gap-3">
            <div class="truncate">
                <span class="text-rose-400 font-bold">Lokasi Berkas:</span>
                <span class="text-slate-300">{{ $errorLog->file }}</span>
                <span class="text-amber-400 font-black">#L{{ $errorLog->line }}</span>
            </div>
        </div>
        @endif
    </div>

    <!-- 2 Columns: Request Context & User Details -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Web Request Context -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-xs space-y-4">
            <h4 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                Konteks Permintaan HTTP
            </h4>

            <div class="divide-y divide-slate-100 text-xs">
                <div class="py-2.5 flex items-center justify-between">
                    <span class="text-slate-500 font-bold">Metode & Status:</span>
                    <span class="font-mono font-black text-slate-800">{{ $errorLog->method }} (HTTP {{ $errorLog->status_code }})</span>
                </div>
                <div class="py-2.5 space-y-1">
                    <span class="text-slate-500 font-bold block">URL Lengkap:</span>
                    <span class="font-mono text-[11px] text-slate-800 break-all block bg-slate-50 p-2 rounded-xl border border-slate-200 select-all">
                        {{ $errorLog->url }}
                    </span>
                </div>
                <div class="py-2.5 flex items-center justify-between">
                    <span class="text-slate-500 font-bold">Nama Route:</span>
                    <span class="font-mono text-slate-800">{{ $errorLog->route_name ?? '-' }}</span>
                </div>
                <div class="py-2.5 flex items-center justify-between">
                    <span class="text-slate-500 font-bold">IP Address Klien:</span>
                    <span class="font-mono text-slate-800">{{ $errorLog->ip_address ?? '-' }}</span>
                </div>
                <div class="py-2.5 space-y-1">
                    <span class="text-slate-500 font-bold block">User Agent:</span>
                    <span class="text-[11px] text-slate-600 break-all block">{{ $errorLog->user_agent ?? '-' }}</span>
                </div>
            </div>
        </div>

        <!-- Affected User Info -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-xs space-y-4">
            <h4 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                Pengguna Terdampak
            </h4>

            @if($errorLog->user)
                <div class="flex items-center space-x-3 p-3 bg-emerald-50/50 rounded-2xl border border-emerald-200/60">
                    @if($errorLog->user->avatar_url)
                        <img src="{{ $errorLog->user->avatar_url }}" class="w-12 h-12 rounded-full object-cover border border-emerald-300 shadow-xs">
                    @else
                        <div class="w-12 h-12 rounded-full bg-emerald-700 text-white font-black text-sm flex items-center justify-center">
                            {{ strtoupper(substr($errorLog->user->name, 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <h5 class="text-sm font-black text-slate-900">{{ $errorLog->user->name }}</h5>
                        <p class="text-xs text-slate-500 font-medium">{{ $errorLog->user->email }}</p>
                        <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200 capitalize">
                            {{ $errorLog->user->getUserTypeName() }}
                        </span>
                    </div>
                </div>

                <div class="divide-y divide-slate-100 text-xs">
                    <div class="py-2.5 flex items-center justify-between">
                        <span class="text-slate-500 font-bold">User ID:</span>
                        <span class="font-mono text-slate-800">#{{ $errorLog->user->id }}</span>
                    </div>
                    <div class="py-2.5 flex items-center justify-between">
                        <span class="text-slate-500 font-bold">Username / NIS:</span>
                        <span class="font-mono text-slate-800">{{ $errorLog->user->username ?? '-' }}</span>
                    </div>
                    @if($errorLog->user->classroom)
                    <div class="py-2.5 flex items-center justify-between">
                        <span class="text-slate-500 font-bold">Kelas:</span>
                        <span class="font-mono text-slate-800">{{ $errorLog->user->classroom }}</span>
                    </div>
                    @endif
                    @if($errorLog->user->phone)
                    <div class="py-2.5 flex items-center justify-between">
                        <span class="text-slate-500 font-bold">Nomor WhatsApp:</span>
                        <span class="font-mono text-slate-800">{{ $errorLog->user->phone }}</span>
                    </div>
                    @endif
                </div>
            @else
                <div class="p-6 text-center text-slate-400 space-y-2 bg-slate-50 rounded-2xl border border-slate-200">
                    <div class="w-10 h-10 rounded-full bg-slate-200 text-slate-500 flex items-center justify-center mx-auto">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <p class="text-xs font-bold text-slate-700">Pengguna Tamu / Publik (Guest)</p>
                    <p class="text-[11px] text-slate-500">Error terjadi pada sesi yang belum login atau proses otentikasi awal.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Request Input / Payload & Headers -->
    @if(!empty($errorLog->request_data) || !empty($errorLog->headers))
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Sanitized Request Payload -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-xs space-y-3">
            <h4 class="text-xs font-black uppercase tracking-wider text-slate-400 flex items-center justify-between">
                <span>Payload Input (Data Permintaan)</span>
                <span class="text-[10px] text-slate-400 font-normal">Kredensial otomatis disensor</span>
            </h4>

            @if(!empty($errorLog->request_data))
                <pre class="bg-slate-900 text-emerald-400 p-4 rounded-2xl font-mono text-xs overflow-x-auto max-h-60 leading-relaxed">{{ json_encode($errorLog->request_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            @else
                <div class="p-4 bg-slate-50 rounded-2xl text-slate-400 text-xs text-center font-medium">
                    Tidak ada data input pada permintaan ini.
                </div>
            @endif
        </div>

        <!-- Filtered HTTP Headers -->
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-xs space-y-3">
            <h4 class="text-xs font-black uppercase tracking-wider text-slate-400">Header HTTP Klien</h4>
            @if(!empty($errorLog->headers))
                <pre class="bg-slate-900 text-sky-300 p-4 rounded-2xl font-mono text-xs overflow-x-auto max-h-60 leading-relaxed">{{ json_encode($errorLog->headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            @else
                <div class="p-4 bg-slate-50 rounded-2xl text-slate-400 text-xs text-center font-medium">
                    Tidak ada header tercatat.
                </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Stack Trace Section -->
    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-xs space-y-3" x-data="{ copiedTrace: false }">
        <div class="flex items-center justify-between">
            <h4 class="text-xs font-black uppercase tracking-wider text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                Stack Trace Lengkap
            </h4>
            <button type="button" 
                    @click="navigator.clipboard.writeText(`{{ addslashes($errorLog->trace) }}`); copiedTrace = true; setTimeout(() => copiedTrace = false, 2500)"
                    class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                <span x-show="!copiedTrace">Salin Trace</span>
                <span x-show="copiedTrace" class="text-emerald-700 font-bold" x-cloak>Trace Tersalin!</span>
            </button>
        </div>

        <pre class="bg-slate-950 text-slate-200 p-5 rounded-2xl font-mono text-xs overflow-x-auto max-h-96 leading-relaxed selection:bg-rose-900 selection:text-white">{{ $errorLog->trace ?? 'Stack trace tidak tersedia.' }}</pre>
    </div>

    <!-- Resolution History Card -->
    @if($errorLog->status === 'resolved' || !empty($errorLog->resolution_notes))
    <div class="bg-emerald-50/70 border border-emerald-200 p-6 rounded-3xl space-y-2">
        <h4 class="text-xs font-black uppercase tracking-wider text-emerald-950 flex items-center gap-1.5">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Catatan Penanganan & Penyelesaian
        </h4>
        <p class="text-xs text-emerald-900 font-medium leading-relaxed">
            {{ $errorLog->resolution_notes ?: 'Insiden telah diselesaikan oleh administrator.' }}
        </p>
        <span class="text-[10px] text-emerald-700 font-bold block pt-1">
            Diselesaikan oleh: {{ $errorLog->resolver ? $errorLog->resolver->name : 'Administrator' }} &bull; {{ $errorLog->resolved_at ? $errorLog->resolved_at->format('d M Y, H:i:s') : '-' }} WIB
        </span>
    </div>
    @endif

    <!-- Modal Form for Resolve with Notes -->
    <div x-show="showNotesModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         x-cloak>
        <div @click.away="showNotesModal = false" class="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl space-y-4 border border-slate-200">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-black text-slate-900">Selesaikan Insiden Error</h3>
                <button type="button" @click="showNotesModal = false" class="text-slate-400 hover:text-slate-600 p-1">&times;</button>
            </div>

            <p class="text-xs text-slate-500">
                Tandai bahwa kendala pada insiden <strong class="font-mono text-slate-800">{{ $errorLog->incident_code }}</strong> telah diperbaiki.
            </p>

            <form action="{{ route('admin.error-logs.resolve', $errorLog->id) }}" method="POST" class="space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Catatan Perbaikan (Opsional):</label>
                    <textarea name="resolution_notes" rows="3" placeholder="Contoh: Telah diperbaiki bug null query pada controller..." 
                              class="w-full p-3 text-xs rounded-xl border border-slate-200 focus:outline-emerald-600 focus:border-emerald-600 font-medium"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="showNotesModal = false" class="px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold transition-all shadow-xs cursor-pointer">
                        Simpan & Selesaikan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
