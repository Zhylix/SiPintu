@extends('layouts.app', ['headerTitle' => 'WhatsApp Gateway'])

@section('content')
<div class="space-y-6 min-w-0 max-w-full" x-data="whatsappGatewayApp()">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white border border-slate-200 rounded-2xl p-4 sm:p-5 shadow-sm min-w-0 max-w-full">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 text-[11px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-800 rounded-lg">Multi-Session Engine</span>
                <span class="text-xs text-slate-400">•</span>
                <span class="text-xs text-slate-500 font-semibold">Baileys WebSocket Microservice</span>
            </div>
            <h2 class="text-xl font-black text-emerald-950 tracking-tight mt-1">Multi-Session WhatsApp Gateway</h2>
            <p class="text-xs text-slate-600 font-medium mt-1">Satu engine gateway terpusat yang dapat dipasangi nomor WhatsApp berbeda untuk masing-masing aplikasi downstream.</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('admin.applications.create') }}" class="inline-flex items-center px-3.5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Daftarkan Aplikasi Baru
            </a>
            <a href="{{ route('admin.whatsapp-gateway.logs') }}" class="inline-flex items-center px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-colors">
                <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Audit Log Pengiriman
            </a>
            <button @click="openTestModal('default', 'SiPintu Pusat')" class="inline-flex items-center px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-extrabold rounded-xl transition-all shadow-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                Kirim Pesan Uji Coba
            </button>
        </div>
    </div>

    <!-- Metric Stat Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm flex items-center space-x-3.5">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-center text-emerald-700 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
            <div class="min-w-0">
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider truncate">Total Sesi Aplikasi</div>
                <div class="text-xl font-black text-slate-900 mt-0.5">{{ $stats['total_apps'] }} Sesi</div>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm flex items-center space-x-3.5">
            <div class="w-11 h-11 rounded-xl bg-teal-50 border border-teal-200 flex items-center justify-center text-teal-700 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div class="min-w-0">
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider truncate">Nomor Terhubung</div>
                <div class="text-xl font-black text-emerald-800 mt-0.5 flex items-center gap-1.5">
                    <span>{{ $stats['connected_sessions'] }}</span>
                    <span class="inline-flex w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                </div>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm flex items-center space-x-3.5">
            <div class="w-11 h-11 rounded-xl bg-sky-50 border border-sky-200 flex items-center justify-center text-sky-700 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
            </div>
            <div class="min-w-0">
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider truncate">Gateway Hari Ini</div>
                <div class="text-xl font-black text-slate-900 mt-0.5">{{ number_format($stats['today_gateway_messages']) }} Pesan</div>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm flex items-center space-x-3.5">
            <div class="w-11 h-11 rounded-xl bg-indigo-50 border border-indigo-200 flex items-center justify-center text-indigo-700 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"></path></svg>
            </div>
            <div class="min-w-0">
                <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider truncate">Total Pesan Gateway</div>
                <div class="text-xl font-black text-slate-900 mt-0.5">{{ number_format($stats['total_gateway_messages']) }}</div>
            </div>
        </div>
    </div>

    <!-- Central SiPintu Default Bot Card -->
    <div class="bg-gradient-to-r from-emerald-900 to-slate-900 text-white rounded-2xl p-5 shadow-lg relative overflow-hidden">
        <div class="absolute -right-8 -bottom-8 w-48 h-48 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 relative z-10">
            <div class="flex items-start sm:items-center space-x-3.5">
                <div class="w-12 h-12 rounded-xl bg-emerald-700/50 border border-emerald-500/40 flex items-center justify-center text-emerald-300 shrink-0 shadow-inner">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs font-black px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">DEFAULT / CENTRAL BOT</span>
                        <span class="text-xs text-slate-400">ID Sesi: <code class="font-mono text-emerald-300">default</code></span>
                    </div>
                    <h3 class="text-base font-black text-white mt-1">Nomor WhatsApp Pusat SiPintu (Pengumuman & Fallback)</h3>
                    <p class="text-xs text-slate-300 mt-0.5">Digunakan untuk pengumuman massal sekolah, notifikasi akun/OTP, dan fallback otomatis saat nomor khusus aplikasi downstream offline.</p>
                </div>
            </div>

            <div class="flex items-center gap-3 flex-wrap sm:flex-nowrap shrink-0">
                <div class="text-right">
                    <div class="text-[10px] text-slate-300 font-bold uppercase tracking-wider">Status Nomor Pusat</div>
                    <div class="text-sm font-extrabold flex items-center justify-end gap-1.5 mt-0.5">
                        @if(($centralBotStatus['data']['connection'] ?? '') === 'open')
                            <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                            <span class="text-emerald-300">+{{ $centralBotStatus['data']['bot_phone'] ?? 'Terhubung' }}</span>
                        @else
                            <span class="w-2 h-2 rounded-full bg-rose-400"></span>
                            <span class="text-rose-300">Belum Terhubung</span>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button @click="openQrModal('default', 'SiPintu Pusat')" class="px-3.5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-black rounded-xl transition-colors shadow-sm flex items-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                        Scan QR Pusat
                    </button>
                    @if(($centralBotStatus['data']['connection'] ?? '') === 'open' || !empty($centralBotStatus['data']['bot_phone']))
                        <button @click="disconnectSession('default', 'Nomor Pusat SiPintu')" class="px-3.5 py-2 bg-rose-500/20 hover:bg-rose-600 text-white border border-rose-400/30 text-xs font-bold rounded-xl transition-all flex items-center gap-1.5 shadow-sm" title="Putus / Disconnect Nomor WhatsApp Pusat">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                            Disconnect
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Downstream Applications WhatsApp Sessions Table -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h3 class="text-base font-black text-slate-900 tracking-tight">Sesi Nomor WhatsApp Aplikasi Downstream</h3>
                <p class="text-xs text-slate-500 font-medium mt-0.5">Setiap aplikasi downstream dapat memiliki nomor WhatsApp tersendiri dengan melakukan scan QR terpisah.</p>
            </div>
            <button @click="refreshAllStatus()" :disabled="isRefreshing" class="inline-flex items-center px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-extrabold rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5 mr-1.5" :class="{ 'animate-spin': isRefreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Segarkan Status
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/80 text-slate-500 uppercase tracking-wider font-extrabold border-b border-slate-100">
                    <tr>
                        <th class="py-3 px-4">Aplikasi Downstream</th>
                        <th class="py-3 px-4">Session ID Bot</th>
                        <th class="py-3 px-4">Nomor WhatsApp</th>
                        <th class="py-3 px-4">Status Koneksi</th>
                        <th class="py-3 px-4">Fallback Pusat</th>
                        <th class="py-3 px-4 text-center">Terkirim</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($applications as $app)
                        @php
                            $sessionId = $app->getEffectiveWaSessionId();
                            $isConnected = $app->live_connection === 'open';
                            $hasPhoneNumber = !empty($app->live_phone) || !empty($app->wa_phone_number);
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center space-x-3">
                                    @if($app->logo_url)
                                        <img src="{{ $app->logo_url }}" class="w-8 h-8 rounded-lg object-contain bg-white border border-slate-200 p-0.5 shrink-0" alt="{{ $app->name }}">
                                    @else
                                        <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-800 font-black text-xs flex items-center justify-center shrink-0">
                                            {{ strtoupper(substr($app->name, 0, 2)) }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="font-extrabold text-slate-900 truncate">{{ $app->name }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ $app->client_id }}</div>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="font-mono text-[11px] px-2 py-0.5 bg-slate-100 text-slate-800 rounded font-semibold">{{ $sessionId }}</span>
                            </td>

                            <td class="py-3.5 px-4">
                                @if($app->live_phone)
                                    <div class="font-bold text-slate-900 flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                                        <span>+{{ $app->live_phone }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">Belum tertaut</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4">
                                @if($isConnected)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5 animate-pulse"></span>
                                        Terhubung
                                    </span>
                                @elseif($app->live_connection === 'connecting')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 animate-spin"></span>
                                        Menghubungkan
                                    </span>
                                @elseif(!$app->live_enabled)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-slate-100 text-slate-600">
                                        💤 Hibernasi (OFF)
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-50 text-rose-700">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400 mr-1.5"></span>
                                        Terputus
                                    </span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4">
                                @if($app->wa_auto_fallback)
                                    <span class="text-[11px] font-bold text-emerald-700 flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                                        Aktif
                                    </span>
                                @else
                                    <span class="text-[11px] font-bold text-slate-400">Nonaktif</span>
                                @endif
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <span class="font-bold text-slate-800">{{ number_format($app->sent_logs_count ?? 0) }}</span>
                                <span class="text-[10px] text-slate-400 font-normal">/ {{ number_format($app->total_logs_count ?? 0) }}</span>
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <!-- Button Scan QR -->
                                    <button @click="openQrModal('{{ $sessionId }}', {{ json_encode($app->name) }})" class="p-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 rounded-lg transition-colors" title="{{ $isConnected ? 'Lihat / Perbarui Sesi QR' : 'Tautkan Nomor WA (Scan QR)' }}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                                    </button>

                                    <!-- Button Test Message -->
                                    <button @click="openTestModal('{{ $sessionId }}', {{ json_encode($app->name) }})" class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors" title="Uji Coba Kirim Pesan">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                    </button>

                                    <!-- Button Config -->
                                    <button @click="openConfigModal({{ json_encode(['id' => $app->id, 'name' => $app->name, 'slug' => $app->slug, 'wa_session_id' => $app->wa_session_id, 'wa_auto_fallback' => (bool) $app->wa_auto_fallback]) }})" class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors" title="Pengaturan Sesi & Fallback">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                    </button>

                                    <!-- Button Disconnect Nomor WhatsApp -->
                                    @if($isConnected || $hasPhoneNumber)
                                        <button @click="disconnectSession('{{ $sessionId }}', {{ json_encode($app->name) }})" class="p-1.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-lg transition-colors" title="Disconnect / Putus Nomor WhatsApp">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center">
                                <div class="max-w-md mx-auto space-y-3">
                                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center mx-auto border border-emerald-200">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                    </div>
                                    <h4 class="text-xs font-black text-slate-800">Belum Ada Aplikasi Downstream</h4>
                                    <p class="text-[11px] text-slate-500 leading-relaxed">
                                        Aplikasi downstream (seperti SIJUNA, Tracer Study, dll) yang didaftarkan ke SiPintu akan otomatis muncul di tabel ini dan siap dipasangi nomor WhatsApp khusus masing-masing.
                                    </p>
                                    <div class="pt-1">
                                        <a href="{{ route('admin.applications.create') }}" class="inline-flex items-center px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl shadow-md transition-all">
                                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                            Tambah Aplikasi Downstream Sekarang
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Logs Card -->
    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4 sm:p-5">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
            <h3 class="text-sm font-extrabold text-slate-900">Aktivitas Terakhir Pengiriman Gateway</h3>
            <a href="{{ route('admin.whatsapp-gateway.logs') }}" class="text-xs font-bold text-emerald-700 hover:text-emerald-800 flex items-center">
                Lihat Semua Log
                <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="text-slate-400 uppercase text-[10px] font-black">
                    <tr>
                        <th class="pb-2">Waktu</th>
                        <th class="pb-2">Aplikasi Pengirim</th>
                        <th class="pb-2">Sesi Pengirim</th>
                        <th class="pb-2">Tujuan</th>
                        <th class="pb-2">Pesan</th>
                        <th class="pb-2 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentLogs as $log)
                        <tr class="hover:bg-slate-50">
                            <td class="py-2.5 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="py-2.5 font-bold text-slate-800">
                                {{ $log->application?->name ?? 'SiPintu Pusat' }}
                            </td>
                            <td class="py-2.5 font-mono text-[11px] text-slate-500">
                                {{ $log->session_id ?? 'default' }}
                            </td>
                            <td class="py-2.5 font-semibold text-slate-700 font-mono">
                                +{{ $log->phone_number }}
                            </td>
                            <td class="py-2.5 max-w-xs truncate text-slate-600">
                                {{ $log->message }}
                            </td>
                            <td class="py-2.5 text-right">
                                @if($log->status === 'sent')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Terkirim</span>
                                @elseif($log->status === 'pending')
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">Antrean</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800" title="{{ $log->error_message }}">Gagal</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 text-center text-slate-400 text-xs">Belum ada aktivitas pengiriman gateway.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL 1: QR CODE SCANNER (DYNAMIC PER SESSION) -->
    <div x-show="qrModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs transition-opacity" @keydown.escape.window="closeQrModal()">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 relative space-y-4" @click.away="closeQrModal()">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h4 class="text-base font-black text-slate-900" x-text="'Tautkan Nomor: ' + currentSessionName"></h4>
                    <p class="text-xs text-slate-400 font-mono mt-0.5" x-text="'ID Sesi: ' + currentSessionId"></p>
                </div>
                <button @click="closeQrModal()" class="p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="flex flex-col items-center justify-center text-center py-2 space-y-4">
                <!-- If Bot Offline -->
                <template x-if="modalBotOffline">
                    <div class="py-8 space-y-3">
                        <div class="w-16 h-16 rounded-full bg-rose-100 text-rose-700 flex items-center justify-center mx-auto">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <h5 class="text-base font-extrabold text-slate-900">Layanan WhatsApp Bot Offline</h5>
                        <p class="text-xs text-rose-600 max-w-xs mx-auto" x-text="modalErrorMessage"></p>
                        @php
                            $configuredBotUrl = config('services.whatsapp.bot_url', 'http://127.0.0.1:3789');
                            $configuredBotPort = parse_url($configuredBotUrl, PHP_URL_PORT) ?: 3789;
                        @endphp
                        <p class="text-[11px] text-slate-400">Pastikan microservice bot Baileys berjalan pada port <strong class="text-slate-600 font-mono">{{ $configuredBotPort }}</strong> (<code class="text-emerald-700 font-mono">{{ $configuredBotUrl }}</code>).</p>
                        <div class="pt-2">
                            <button @click="triggerFreshQr()" class="px-4 py-2 bg-slate-800 text-white text-xs font-bold rounded-xl shadow-md">Coba Hubungkan Ulang</button>
                        </div>
                    </div>
                </template>

                <!-- If Connected -->
                <template x-if="!modalBotOffline && modalSessionConnected">
                    <div class="py-8 space-y-4">
                        <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <div>
                            <h5 class="text-base font-extrabold text-slate-900">Sesi WhatsApp Berhasil Terhubung!</h5>
                            <p class="text-xs text-slate-600 mt-1">Nomor Terhubung: <strong class="text-emerald-700 font-mono text-sm" x-text="'+' + modalSessionPhone"></strong></p>
                        </div>
                        <div class="pt-2 flex items-center justify-center gap-2">
                            <button @click="closeQrModal()" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-colors">
                                Tutup Jendela
                            </button>
                            <button @click="disconnectSessionFromModal()" :disabled="isDisconnecting" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-md transition-colors flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                <span x-show="!isDisconnecting">Putus / Disconnect Nomor</span>
                                <span x-show="isDisconnecting">Memutuskan...</span>
                            </button>
                        </div>
                    </div>
                </template>

                <!-- If Has QR Code -->
                <template x-if="!modalBotOffline && !modalSessionConnected && modalQrImage">
                    <div class="space-y-4 w-full">
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200 inline-block shadow-inner">
                            <img :src="modalQrImage" class="w-64 h-64 object-contain mx-auto rounded-lg" alt="WhatsApp QR Code">
                        </div>
                        <div class="space-y-1 text-xs text-slate-600 max-w-xs mx-auto">
                            <p class="font-extrabold text-slate-900">Cara Menghubungkan:</p>
                            <p>1. Buka aplikasi WhatsApp di HP Anda</p>
                            <p>2. Buka <strong>Perangkat Tertaut (Linked Devices)</strong></p>
                            <p>3. Ketuk <strong>Tautkan Perangkat</strong> & scan QR di atas</p>
                        </div>
                    </div>
                </template>

                <!-- If Loading / Generating QR -->
                <template x-if="!modalBotOffline && !modalSessionConnected && !modalQrImage">
                    <div class="py-12 space-y-3">
                        <div class="w-12 h-12 border-3 border-emerald-600 border-t-transparent rounded-full animate-spin mx-auto"></div>
                        <p class="text-xs font-extrabold text-slate-700">Sedang menyiapkan QR Code sesi...</p>
                        <p class="text-[11px] text-slate-400">Harap tunggu beberapa detik.</p>
                    </div>
                </template>
            </div>

            <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                <span class="flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Auto-refresh polling aktif (2s)
                </span>
                <button @click="triggerFreshQr()" class="text-emerald-700 font-bold hover:underline">
                    Perbarui QR
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL 2: SEND TEST MESSAGE -->
    <div x-show="testModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" @keydown.escape.window="testModalOpen = false">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4" @click.away="testModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h4 class="text-base font-black text-slate-900">Uji Coba Kirim Pesan</h4>
                    <p class="text-xs text-slate-400 mt-0.5" x-text="'Dari Sesi: ' + testSessionName + ' (' + testSessionId + ')'"></p>
                </div>
                <button @click="testModalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form @submit.prevent="submitTestMessage" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nomor WhatsApp Tujuan</label>
                    <input type="text" x-model="testPhone" placeholder="08123456789 atau 628123456789" required class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Isi Pesan Uji Coba</label>
                    <textarea x-model="testContent" rows="3" required class="w-full text-xs px-3.5 py-2 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500"></textarea>
                </div>

                <div x-show="testResultMsg" class="p-3 rounded-xl text-xs font-bold" :class="testResultSuccess ? 'bg-emerald-50 text-emerald-800' : 'bg-rose-50 text-rose-800'" x-text="testResultMsg"></div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="testModalOpen = false" class="px-4 py-2 bg-slate-100 text-slate-700 text-xs font-bold rounded-xl">Batal</button>
                    <button type="submit" :disabled="isSendingTest" class="px-5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl shadow-md flex items-center">
                        <span x-show="!isSendingTest">Kirim Sekarang</span>
                        <span x-show="isSendingTest">Mengirim...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 3: APPLICATION CONFIGURATION -->
    <div x-show="configModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" @keydown.escape.window="configModalOpen = false">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 space-y-4" @click.away="configModalOpen = false">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div>
                    <h4 class="text-base font-black text-slate-900" x-text="'Konfigurasi Gateway: ' + configAppName"></h4>
                    <p class="text-xs text-slate-400 mt-0.5">Atur pengidentifikasi sesi dan preferensi fallback.</p>
                </div>
                <button @click="configModalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <form :action="configFormAction" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Session ID Bot WhatsApp</label>
                    <input type="text" name="wa_session_id" x-model="configSessionId" class="w-full text-xs px-3.5 py-2.5 rounded-xl border border-slate-200 focus:ring-2 focus:ring-emerald-500 font-mono" placeholder="app_slug">
                    <p class="text-[10px] text-slate-500 mt-1">Gunakan huruf kecil, angka, tanda hubung (-) atau garis bawah (_). Default: <code class="font-mono text-emerald-700" x-text="'app_' + configAppSlug"></code></p>
                </div>

                <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 space-y-2">
                    <label class="flex items-start space-x-2.5 cursor-pointer">
                        <input type="checkbox" name="wa_auto_fallback" value="1" x-model="configFallback" class="mt-0.5 text-emerald-600 rounded border-slate-300 focus:ring-emerald-500">
                        <div class="text-xs">
                            <span class="font-bold text-slate-800">Fallback Otomatis ke Nomor Pusat</span>
                            <p class="text-[11px] text-slate-500 mt-0.5">Jika nomor khusus aplikasi ini sedang offline / terputus, pesan akan otomatis dialihkan untuk dikirim via Nomor Pusat SiPintu agar pesan tetap tersampaikan.</p>
                        </div>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="configModalOpen = false" class="px-4 py-2 bg-slate-100 text-slate-700 text-xs font-bold rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl shadow-md">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function whatsappGatewayApp() {
    return {
        isRefreshing: false,
        isDisconnecting: false,
        qrModalOpen: false,
        testModalOpen: false,
        configModalOpen: false,

        // Modal QR state
        currentSessionId: 'default',
        currentSessionName: 'SiPintu Pusat',
        modalSessionConnected: false,
        modalSessionPhone: '',
        modalQrImage: '',
        modalBotOffline: false,
        modalErrorMessage: '',
        qrPollingInterval: null,

        // Test modal state
        testSessionId: 'default',
        testSessionName: 'SiPintu Pusat',
        testPhone: '',
        testContent: 'Halo, ini adalah pesan uji coba dari Multi-Session WhatsApp Gateway SiPintu.',
        isSendingTest: false,
        testResultMsg: '',
        testResultSuccess: false,

        // Config modal state
        configAppId: null,
        configAppName: '',
        configAppSlug: '',
        configSessionId: '',
        configFallback: true,
        configFormAction: '',

        openQrModal(sessionId, sessionName) {
            this.currentSessionId = sessionId;
            this.currentSessionName = sessionName;
            this.modalSessionConnected = false;
            this.modalSessionPhone = '';
            this.modalQrImage = '';
            this.modalBotOffline = false;
            this.modalErrorMessage = '';
            this.qrModalOpen = true;

            // Trigger session start on bot
            fetch(`/admin/whatsapp-gateway/session/${encodeURIComponent(sessionId)}/start`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            }).catch(() => {});

            // Initial fetch
            this.pollQrStatus();

            // Set polling interval
            if (this.qrPollingInterval) clearInterval(this.qrPollingInterval);
            this.qrPollingInterval = setInterval(() => {
                if (this.qrModalOpen && !this.modalSessionConnected && !this.modalBotOffline) {
                    this.pollQrStatus();
                }
            }, 2000);
        },

        closeQrModal() {
            const wasConnected = this.modalSessionConnected;
            this.qrModalOpen = false;
            if (this.qrPollingInterval) {
                clearInterval(this.qrPollingInterval);
                this.qrPollingInterval = null;
            }
            if (wasConnected) {
                window.location.reload();
            }
        },

        pollQrStatus() {
            fetch(`/admin/whatsapp-gateway/session/${encodeURIComponent(this.currentSessionId)}/status`)
                .then(res => res.json())
                .then(res => {
                    if (res.online === false) {
                        this.modalBotOffline = true;
                        this.modalErrorMessage = res.error || 'Layanan WhatsApp Bot sedang offline.';
                        return;
                    }
                    this.modalBotOffline = false;
                    if (res.online && res.data) {
                        const data = res.data;
                        if (data.connection === 'open' && data.bot_phone) {
                            this.modalSessionConnected = true;
                            this.modalSessionPhone = data.bot_phone;
                            this.modalQrImage = '';
                        } else {
                            this.modalSessionConnected = false;
                            this.modalQrImage = data.qr_code || '';
                        }
                    }
                })
                .catch(() => {
                    this.modalBotOffline = true;
                    this.modalErrorMessage = 'Tidak dapat menghubungi service WhatsApp Gateway.';
                });
        },

        triggerFreshQr() {
            this.modalBotOffline = false;
            this.modalQrImage = '';
            fetch(`/admin/whatsapp-gateway/session/${encodeURIComponent(this.currentSessionId)}/start`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            }).then(() => {
                this.pollQrStatus();
            }).catch(() => {
                this.pollQrStatus();
            });
        },

        disconnectSession(sessionId, sessionName = '') {
            const label = sessionName ? `${sessionName} (${sessionId})` : sessionId;
            if (!confirm(`Apakah Anda yakin ingin memutuskan (disconnect) nomor WhatsApp untuk sesi [${label}]?\n\nSetelah diputus, nomor ini tidak akan lagi digunakan untuk mengirim pesan sampai Anda menautkannya kembali via scan QR.`)) {
                return;
            }

            this.isDisconnecting = true;
            fetch(`/admin/whatsapp-gateway/session/${encodeURIComponent(sessionId)}/disconnect`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message || 'Nomor WhatsApp berhasil diputuskan.');
                window.location.reload();
            })
            .catch(() => {
                alert('Gagal memutuskan nomor WhatsApp sesi ini.');
            })
            .finally(() => {
                this.isDisconnecting = false;
            });
        },

        disconnectSessionFromModal() {
            if (!this.currentSessionId) return;
            const label = this.currentSessionName ? `${this.currentSessionName} (${this.currentSessionId})` : this.currentSessionId;
            if (!confirm(`Apakah Anda yakin ingin memutuskan (disconnect) nomor WhatsApp untuk sesi [${label}]?`)) {
                return;
            }

            this.isDisconnecting = true;
            fetch(`/admin/whatsapp-gateway/session/${encodeURIComponent(this.currentSessionId)}/disconnect`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                alert(data.message || 'Nomor WhatsApp berhasil diputuskan.');
                this.modalSessionConnected = false;
                this.modalSessionPhone = '';
                this.triggerFreshQr();
            })
            .catch(() => {
                alert('Gagal memutuskan nomor WhatsApp sesi ini.');
            })
            .finally(() => {
                this.isDisconnecting = false;
            });
        },

        logoutSession(sessionId, sessionName = '') {
            return this.disconnectSession(sessionId, sessionName);
        },

        openTestModal(sessionId, sessionName) {
            this.testSessionId = sessionId;
            this.testSessionName = sessionName;
            this.testResultMsg = '';
            this.testModalOpen = true;
        },

        submitTestMessage() {
            this.isSendingTest = true;
            this.testResultMsg = '';

            fetch('{{ route('admin.whatsapp-gateway.test-message') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    session_id: this.testSessionId,
                    phone: this.testPhone,
                    message: this.testContent
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    this.testResultSuccess = true;
                    this.testResultMsg = data.message || 'Pesan berhasil dikirim!';
                } else {
                    this.testResultSuccess = false;
                    this.testResultMsg = data.message || 'Gagal mengirim pesan.';
                }
            })
            .catch(() => {
                this.testResultSuccess = false;
                this.testResultMsg = 'Koneksi ke server gagal.';
            })
            .finally(() => {
                this.isSendingTest = false;
            });
        },

        openConfigModal(app) {
            this.configAppId = app.id;
            this.configAppName = app.name;
            this.configAppSlug = app.slug;
            this.configSessionId = app.wa_session_id || ('app_' + app.slug);
            this.configFallback = Boolean(app.wa_auto_fallback);
            this.configFormAction = `/admin/whatsapp-gateway/app-config/${app.id}`;
            this.configModalOpen = true;
        },

        refreshAllStatus() {
            this.isRefreshing = true;
            window.location.reload();
        }
    };
}
</script>
@endsection
