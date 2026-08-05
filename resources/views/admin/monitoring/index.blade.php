@extends('layouts.app', ['headerTitle' => 'Dashboard Visual Monitoring Gateway'])

@section('content')
<div class="space-y-6" x-data="{
    activeTab: 'all',
    viewMode: 'grid',
    testingClient: false,
    selectedClientId: '',
    customSecret: '',
    clientResult: null,
    searchQuery: '',
    clients: @js($gatewayDiagnostics['downstream_clients']['clients'] ?? []),
    summary: @js($gatewayDiagnostics['summary'] ?? []),
    get filteredClients() {
        return this.clients.filter(c => {
            const matchesTab = this.activeTab === 'all' || c.connection_status === this.activeTab;
            const matchesSearch = c.name.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                                  c.client_id.toLowerCase().includes(this.searchQuery.toLowerCase());
            return matchesTab && matchesSearch;
        });
    },
    async validateClient(clientId = null, secret = null) {
        const idToTest = clientId || this.selectedClientId;
        const secretToTest = secret !== null ? secret : this.customSecret;
        if (!idToTest) return;

        this.testingClient = true;
        this.clientResult = null;
        this.selectedClientId = idToTest;

        try {
            const res = await fetch('{{ route('admin.monitoring.validate-client') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    client_id: idToTest,
                    client_secret: secretToTest
                })
            });
            const data = await res.json();
            this.clientResult = data.data;

            // Refresh client info dynamically from server response
            if (this.clientResult && this.clientResult.application) {
                const updatedApp = this.clientResult.application;
                const idx = this.clients.findIndex(c => c.client_id === updatedApp.client_id);
                if (idx !== -1) {
                    this.clients[idx].connection_status = updatedApp.connection_status;
                    this.clients[idx].last_connected_human = updatedApp.last_connected_human;
                    this.clients[idx].last_connected_ip = updatedApp.last_connected_ip || '-';
                    this.clients[idx].total_api_requests = updatedApp.total_api_requests;
                }
            }
        } catch (e) {
            console.error('Validation error:', e);
        } finally {
            this.testingClient = false;
        }
    }
}">
    <!-- Bright & Vibrant Header Hero Banner (Clean Light Theme) -->
    <div class="relative overflow-hidden bg-white border border-emerald-200 rounded-3xl p-6 sm:p-8 shadow-sm">
        <!-- Background Soft Accents -->
        <div class="absolute top-0 right-0 w-96 h-96 bg-emerald-100/50 rounded-full blur-3xl pointer-events-none -mr-20 -mt-20"></div>
        <div class="absolute bottom-0 left-0 w-80 h-80 bg-teal-100/40 rounded-full blur-3xl pointer-events-none -ml-20 -mb-20"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-emerald-100 border border-emerald-300 text-emerald-800 text-xs font-extrabold tracking-wide uppercase">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 animate-pulse"></span>
                    <span>Visual Telemetry & Connection Analytics</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-emerald-950">Dashboard Monitoring Aplikasi Downstream</h2>
                <p class="text-xs sm:text-sm text-slate-600 max-w-2xl font-medium leading-relaxed">
                    Pemantauan visual real-time aktivitas koneksi, volume request API, latensi infrastruktur, dan otorisasi aplikasi klien di bawah SiPintu.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <form action="{{ route('admin.monitoring.run-health-checks') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-2xl transition-all shadow-md shadow-emerald-700/20 flex items-center space-x-2 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>Refresh Telemetry</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Top Key Metric Cards with Bright Visual Indicators -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Metric 1: Total Apps -->
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-3 relative overflow-hidden group hover:border-emerald-300 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Total Aplikasi Client</span>
                <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-800 group-hover:bg-emerald-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-black text-slate-900 tracking-tight">{{ number_format($gatewayDiagnostics['summary']['total_registered_clients'] ?? $applications->count()) }}</div>
            <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                <div class="bg-emerald-600 h-full rounded-full w-full"></div>
            </div>
            <p class="text-[11px] text-slate-600 font-medium">Aplikasi downstream terdaftar</p>
        </div>

        <!-- Metric 2: Connected Apps -->
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-3 relative overflow-hidden group hover:border-emerald-400 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-extrabold text-emerald-800 uppercase tracking-wider">Terkoneksi (Online)</span>
                <div class="p-2.5 rounded-xl bg-emerald-100 text-emerald-800 animate-pulse">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-black text-emerald-700 tracking-tight flex items-baseline gap-2">
                <span>{{ number_format($gatewayDiagnostics['summary']['connected_clients'] ?? 0) }}</span>
                <span class="text-xs font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-full">Active</span>
            </div>
            @php
                $total = max(1, $gatewayDiagnostics['summary']['total_registered_clients'] ?? 1);
                $connCount = $gatewayDiagnostics['summary']['connected_clients'] ?? 0;
                $connPercent = round(($connCount / $total) * 100);
            @endphp
            <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                <div class="bg-emerald-600 h-full rounded-full transition-all duration-500" style="width: {{ $connPercent }}%"></div>
            </div>
            <p class="text-[11px] text-slate-600 font-medium"><span class="font-bold text-emerald-800 font-mono">{{ $connPercent }}%</span> dari total aplikasi terhubung</p>
        </div>

        <!-- Metric 3: Disconnected / Never -->
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-3 relative overflow-hidden group hover:border-rose-300 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-extrabold text-rose-800 uppercase tracking-wider">Terputus / Inaktif</span>
                <div class="p-2.5 rounded-xl bg-rose-100 text-rose-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-black text-rose-700 tracking-tight flex items-baseline gap-2">
                <span>{{ number_format(($gatewayDiagnostics['summary']['disconnected_clients'] ?? 0) + ($gatewayDiagnostics['summary']['never_connected_clients'] ?? 0)) }}</span>
                <span class="text-xs font-bold text-rose-800 bg-rose-100 px-2 py-0.5 rounded-full">Offline</span>
            </div>
            @php
                $disconnPercent = 100 - $connPercent;
            @endphp
            <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                <div class="bg-rose-500 h-full rounded-full transition-all duration-500" style="width: {{ $disconnPercent }}%"></div>
            </div>
            <p class="text-[11px] text-slate-600 font-medium">Inaktif > 15m atau belum terkoneksi</p>
        </div>

        <!-- Metric 4: DB Latency -->
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-sm space-y-3 relative overflow-hidden group hover:border-teal-300 transition-all">
            <div class="flex items-center justify-between">
                <span class="text-xs font-extrabold text-teal-800 uppercase tracking-wider">Database Gateway Latency</span>
                <div class="p-2.5 rounded-xl bg-teal-100 text-teal-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"></path>
                    </svg>
                </div>
            </div>
            <div class="text-3xl font-black text-teal-900 tracking-tight font-mono">
                {{ $gatewayDiagnostics['database']['latency_ms'] ?? 0.5 }} <span class="text-xs font-sans text-slate-500">ms</span>
            </div>
            <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                <div class="bg-teal-600 h-full rounded-full w-full"></div>
            </div>
            <p class="text-[11px] text-slate-600 font-medium">Respon database primary gateway</p>
        </div>
    </div>

    <!-- Visual Analytics: Connection Distribution & Request Volume Charts -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Visual Donut Chart & Connection Health Breakdown -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-5 flex flex-col justify-between">
            <div class="border-b border-slate-100 pb-3">
                <h3 class="text-sm font-black text-emerald-950 uppercase tracking-wider flex items-center justify-between">
                    <span>Distribusi Status Koneksi</span>
                    <span class="text-[10px] px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-extrabold">LIVE RATIO</span>
                </h3>
            </div>

            <!-- Bright CSS Visual Donut Ring Chart -->
            <div class="flex flex-col items-center justify-center py-4 relative">
                <div class="w-36 h-36 rounded-full border-[14px] border-emerald-500 flex items-center justify-center relative shadow-sm">
                    <div class="text-center">
                        <span class="text-2xl font-black text-emerald-950 block leading-none font-mono" x-text="clients.length"></span>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mt-1 block">Aplikasi</span>
                    </div>
                </div>
            </div>

            <div class="space-y-2.5 pt-2">
                <div class="flex items-center justify-between text-xs font-bold p-2.5 rounded-xl bg-emerald-50 border border-emerald-200">
                    <span class="flex items-center space-x-2 text-emerald-900">
                        <span class="w-3 h-3 rounded-full bg-emerald-600"></span>
                        <span>Terkoneksi (Active Connection)</span>
                    </span>
                    <span class="font-mono text-emerald-950 font-black" x-text="summary.connected_clients || 0"></span>
                </div>

                <div class="flex items-center justify-between text-xs font-bold p-2.5 rounded-xl bg-rose-50 border border-rose-200">
                    <span class="flex items-center space-x-2 text-rose-900">
                        <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                        <span>Terputus (Disconnected)</span>
                    </span>
                    <span class="font-mono text-rose-950 font-black" x-text="summary.disconnected_clients || 0"></span>
                </div>

                <div class="flex items-center justify-between text-xs font-bold p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                    <span class="flex items-center space-x-2 text-slate-700">
                        <span class="w-3 h-3 rounded-full bg-slate-400"></span>
                        <span>Belum Pernah Terkoneksi</span>
                    </span>
                    <span class="font-mono text-slate-900 font-black" x-text="summary.never_connected_clients || 0"></span>
                </div>
            </div>
        </div>

        <!-- Top Connected Client Apps by API Volume (Bright Visual Meter Bars) -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-5 flex flex-col justify-between">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-sm font-black text-emerald-950 uppercase tracking-wider">Aktivitas Request API Aplikasi Downstream</h3>
                <span class="text-xs text-slate-500 font-medium">Volumetri Request Terkini</span>
            </div>

            <div class="space-y-4 flex-1">
                <template x-for="client in clients" :key="client.id">
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-2 hover:bg-emerald-50/50 transition-colors">
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center space-x-2">
                                <span class="w-2.5 h-2.5 rounded-full"
                                    :class="client.connection_status === 'connected' ? 'bg-emerald-600 animate-ping' : (client.connection_status === 'disconnected' ? 'bg-rose-500' : 'bg-slate-300')"></span>
                                <span class="font-bold text-slate-900" x-text="client.name"></span>
                                <span class="text-[10px] font-mono text-slate-500" x-text="'(' + client.client_id + ')'"></span>
                            </div>

                            <div class="flex items-center space-x-3">
                                <span class="text-[11px] font-mono font-bold text-emerald-800" x-text="client.last_connected_human"></span>
                                <span class="px-2 py-0.5 rounded text-[10px] font-black uppercase font-mono"
                                    :class="client.connection_status === 'connected' ? 'bg-emerald-100 text-emerald-800' : (client.connection_status === 'disconnected' ? 'bg-rose-100 text-rose-800' : 'bg-slate-200 text-slate-600')"
                                    x-text="client.connection_status === 'connected' ? 'ONLINE' : (client.connection_status === 'disconnected' ? 'OFFLINE' : 'NEVER')">
                                </span>
                            </div>
                        </div>

                        <!-- Visual Meter Bar -->
                        <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden flex">
                            <div class="h-full rounded-full transition-all duration-500"
                                :class="client.connection_status === 'connected' ? 'bg-emerald-600' : 'bg-slate-400'"
                                :style="'width: ' + Math.min(100, Math.max(15, client.total_api_requests * 20)) + '%'">
                            </div>
                        </div>

                        <div class="flex justify-between items-center text-[10px] text-slate-500 font-medium">
                            <span x-text="'IP Terakhir: ' + (client.last_connected_ip || '-')"></span>
                            <span class="font-mono font-bold text-emerald-950" x-text="client.total_api_requests + ' Request API'"></span>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Client Applications Visual Grid / Table Telemetry Panel -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-6 shadow-sm">
        <!-- Control Bar: Search & View Modes & Filter Pills -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 pb-5">
            <div>
                <h3 class="text-base font-black text-emerald-950 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    Katalog Telemetry Aplikasi Client Downstream
                </h3>
                <p class="text-xs text-slate-600 font-medium mt-0.5">Filter dan pantau status koneksi individual aplikasi downstream yang mengakses REST API</p>
            </div>

            <!-- Controls: Filter Tabs + View Mode Toggle -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Search Input -->
                <div class="relative">
                    <input type="text" x-model="searchQuery" placeholder="Cari nama / client_id..." class="pl-8 pr-3 py-1.5 text-xs rounded-xl border border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 w-48">
                    <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                <!-- Filter Tabs -->
                <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs font-extrabold">
                    <button @click="activeTab = 'all'" :class="activeTab === 'all' ? 'bg-white text-emerald-950 shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="px-3 py-1 rounded-lg transition-all">Semua</button>
                    <button @click="activeTab = 'connected'" :class="activeTab === 'connected' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-emerald-700'" class="px-3 py-1 rounded-lg transition-all">🟢 Online</button>
                    <button @click="activeTab = 'disconnected'" :class="activeTab === 'disconnected' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-600 hover:text-rose-700'" class="px-3 py-1 rounded-lg transition-all">🔴 Offline</button>
                </div>

                <!-- Grid vs Table View Switcher -->
                <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200">
                    <button @click="viewMode = 'grid'" :class="viewMode === 'grid' ? 'bg-white text-emerald-800 shadow-xs' : 'text-slate-400 hover:text-slate-700'" class="p-1 rounded-lg transition-all" title="Tampilan Kartu Visual">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                    </button>
                    <button @click="viewMode = 'table'" :class="viewMode === 'table' ? 'bg-white text-emerald-800 shadow-xs' : 'text-slate-400 hover:text-slate-700'" class="p-1 rounded-lg transition-all" title="Tampilan Tabel">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- 1. VISUAL GRID CARDS VIEW -->
        <template x-if="viewMode === 'grid'">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <template x-for="client in filteredClients" :key="client.id">
                    <div class="p-5 rounded-2xl bg-white border border-slate-200 hover:border-emerald-400 hover:shadow-md transition-all space-y-4 flex flex-col justify-between relative overflow-hidden group">
                        <!-- Top status accent bar -->
                        <div class="h-1.5 w-full absolute top-0 left-0"
                            :class="client.connection_status === 'connected' ? 'bg-emerald-500' : (client.connection_status === 'disconnected' ? 'bg-rose-500' : 'bg-slate-300')">
                        </div>

                        <div class="space-y-3 pt-2">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <h4 class="font-black text-slate-900 text-sm group-hover:text-emerald-950 transition-colors" x-text="client.name"></h4>
                                    <p class="text-[11px] text-slate-500 truncate" x-text="client.base_url"></p>
                                </div>

                                <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider shrink-0 flex items-center space-x-1"
                                    :class="client.connection_status === 'connected' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : (client.connection_status === 'disconnected' ? 'bg-rose-100 text-rose-800 border border-rose-300' : 'bg-slate-100 text-slate-600 border border-slate-300')">
                                    <span class="w-2 h-2 rounded-full" :class="client.connection_status === 'connected' ? 'bg-emerald-600 animate-ping' : (client.connection_status === 'disconnected' ? 'bg-rose-600' : 'bg-slate-400')"></span>
                                    <span x-text="client.connection_status === 'connected' ? 'ONLINE' : (client.connection_status === 'disconnected' ? 'OFFLINE' : 'NEVER')"></span>
                                </span>
                            </div>

                            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200/80 space-y-1.5 text-xs font-mono">
                                <div class="flex justify-between text-slate-600">
                                    <span>Client ID:</span>
                                    <span class="font-bold text-emerald-800" x-text="client.client_id"></span>
                                </div>
                                <div class="flex justify-between text-slate-600">
                                    <span>Terakhir Terkoneksi:</span>
                                    <span class="font-bold text-slate-900" x-text="client.last_connected_human"></span>
                                </div>
                                <div class="flex justify-between text-slate-600">
                                    <span>IP Terakhir:</span>
                                    <span class="font-bold text-slate-900" x-text="client.last_connected_ip || '-'"></span>
                                </div>
                                <div class="flex justify-between text-slate-600 pt-1 border-t border-slate-200">
                                    <span>Total Request API:</span>
                                    <span class="font-bold text-emerald-950 text-sm" x-text="client.total_api_requests"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Button to Trigger Ping Test (Clean Bright Emerald Button) -->
                        <button @click="validateClient(client.client_id, '')" class="w-full py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-xs flex items-center justify-center space-x-2 cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <span>Test Ping Koneksi</span>
                        </button>
                    </div>
                </template>
            </div>
        </template>

        <!-- 2. VISUAL TABLE VIEW -->
        <template x-if="viewMode === 'table'">
            <div class="overflow-x-auto border border-slate-200 rounded-xl">
                <table class="w-full text-left text-xs">
                    <thead class="bg-emerald-50 text-emerald-900 uppercase font-black text-[10px] border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3">Nama Aplikasi</th>
                            <th class="px-4 py-3">Client ID</th>
                            <th class="px-4 py-3">Status Koneksi REST API</th>
                            <th class="px-4 py-3">Terakhir Terkoneksi</th>
                            <th class="px-4 py-3">IP Address</th>
                            <th class="px-4 py-3 text-right">Total Request</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 font-sans text-slate-700 bg-white">
                        <template x-for="client in filteredClients" :key="client.id">
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 font-bold text-slate-900" x-text="client.name"></td>
                                <td class="px-4 py-3 font-mono font-bold text-emerald-800" x-text="client.client_id"></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center space-x-1.5 px-2.5 py-1 rounded-full text-[10px] font-extrabold"
                                        :class="client.connection_status === 'connected' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : (client.connection_status === 'disconnected' ? 'bg-rose-100 text-rose-800 border border-rose-300' : 'bg-slate-100 text-slate-600 border border-slate-300')">
                                        <span class="w-2 h-2 rounded-full" :class="client.connection_status === 'connected' ? 'bg-emerald-600 animate-ping' : (client.connection_status === 'disconnected' ? 'bg-rose-600' : 'bg-slate-400')"></span>
                                        <span x-text="client.connection_status === 'connected' ? 'ONLINE' : (client.connection_status === 'disconnected' ? 'OFFLINE' : 'NEVER')"></span>
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-semibold text-slate-800" x-text="client.last_connected_human"></td>
                                <td class="px-4 py-3 font-mono text-slate-600 font-semibold" x-text="client.last_connected_ip || '-'"></td>
                                <td class="px-4 py-3 text-right font-mono font-bold text-emerald-900" x-text="client.total_api_requests"></td>
                                <td class="px-4 py-3 text-center">
                                    <button @click="validateClient(client.client_id, '')" class="px-3 py-1 bg-emerald-700 hover:bg-emerald-800 text-white text-[11px] font-extrabold rounded-lg transition-colors">
                                        Test Ping
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </template>
    </div>

    <!-- Downstream Client Diagnostic Tester Console -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-5 shadow-sm">
        <div class="border-b border-slate-100 pb-4">
            <h3 class="text-base font-black text-emerald-950 flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path>
                </svg>
                Konsol Uji Otorisasi & Heartbeat Aplikasi Client
            </h3>
            <p class="text-xs text-slate-600 font-medium mt-0.5">Pengujian langsung respon REST API Gateway dan pembaharuan telemetry koneksi aplikasi downstream</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Pilih Aplikasi Klien Downstream</label>
                <select x-model="selectedClientId" class="w-full text-xs rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 font-mono">
                    <option value="">-- Pilih Aplikasi Client --</option>
                    <template x-for="c in clients" :key="c.id">
                        <option :value="c.client_id" x-text="c.name + ' (' + c.client_id + ')'"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Client Secret (Opsional untuk Verifikasi Kredensial)</label>
                <input type="text" x-model="customSecret" placeholder="Masukkan client_secret..." class="w-full text-xs rounded-lg border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 font-mono">
            </div>

            <div class="flex items-end">
                <button @click="validateClient()" :disabled="!selectedClientId || testingClient" class="w-full py-2 bg-emerald-700 hover:bg-emerald-800 disabled:bg-slate-300 disabled:cursor-not-allowed text-white text-xs font-extrabold rounded-lg transition-all shadow-sm flex items-center justify-center space-x-2 cursor-pointer">
                    <svg class="w-4 h-4" :class="testingClient ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    <span x-text="testingClient ? 'Memverifikasi...' : 'Eksekusi Test Ping'">Eksekusi Test Ping</span>
                </button>
            </div>
        </div>

        <!-- Telemetry Bright JSON Response Preview -->
        <template x-if="clientResult">
            <div class="p-4 rounded-xl border space-y-3 transition-all"
                :class="clientResult.valid ? 'bg-emerald-50/90 border-emerald-300 text-emerald-950' : 'bg-rose-50/90 border-rose-300 text-rose-950'">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="w-3 h-3 rounded-full" :class="clientResult.valid ? 'bg-emerald-600 animate-ping' : 'bg-rose-600'"></span>
                        <span class="font-black text-sm" x-text="clientResult.valid ? '✓ RESPONS REST API: KONEKSI & OTORISASI VALID' : '✗ RESPONS REST API: TERJADI KESALAHAN OTORISASI'"></span>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase font-mono"
                        :class="clientResult.valid ? 'bg-emerald-200 text-emerald-900' : 'bg-rose-200 text-rose-900'"
                        x-text="clientResult.status">
                    </span>
                </div>

                <p class="text-xs font-medium" x-text="clientResult.message"></p>

                <!-- Clean Light JSON Telemetry Container -->
                <div class="pt-2 border-t border-emerald-200/80 space-y-1">
                    <span class="text-[10px] font-extrabold uppercase text-emerald-900 block font-mono">Payload Respons Telemetry JSON:</span>
                    <pre class="p-3 bg-white text-emerald-950 font-mono text-[11px] rounded-xl overflow-x-auto max-h-48 border border-emerald-200 shadow-xs leading-snug" x-text="JSON.stringify(clientResult, null, 2)"></pre>
                </div>
            </div>
        </template>
    </div>
</div>
@endsection
