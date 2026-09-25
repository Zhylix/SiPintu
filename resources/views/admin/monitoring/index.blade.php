@extends('layouts.app', ['headerTitle' => 'Monitoring API'])

@section('content')
<div class="space-y-6 min-w-0 max-w-full" x-data="{
    activeTab: 'all',
    viewMode: 'grid',
    testingClient: false,
    selectedClientId: '',
    customSecret: '',
    clientResult: null,
    searchQuery: '',
    diagnosingSso: false,
    showSsoModal: false,
    ssoDiagnosis: null,
    diagModalTab: 'summary',
    copiedReport: false,
    diagnosingAll: false,
    showBatchModal: false,
    batchResults: null,
    openRestoreModal: false,
    selectedRestoreFile: '',
    openBlockIpModal: false,
    clients: @js($gatewayDiagnostics['downstream_clients']['clients'] ?? []),
    summary: @js($gatewayDiagnostics['summary'] ?? []),
    init() {
        const validTabs = ['all', 'connected', 'disconnected'];
        const hash = window.location.hash.replace('#', '');
        const storedTab = localStorage.getItem('sipintu_monitoring_active_tab');

        if (validTabs.includes(hash)) {
            this.activeTab = hash;
        } else if (storedTab && validTabs.includes(storedTab)) {
            this.activeTab = storedTab;
        }

        const storedViewMode = localStorage.getItem('sipintu_monitoring_view_mode');
        if (storedViewMode && ['grid', 'table'].includes(storedViewMode)) {
            this.viewMode = storedViewMode;
        }

        this.$watch('activeTab', (val) => {
            if (validTabs.includes(val)) {
                localStorage.setItem('sipintu_monitoring_active_tab', val);
                if (window.history.replaceState) {
                    window.history.replaceState(null, null, '#' + val);
                }
            }
        });

        this.$watch('viewMode', (val) => {
            if (['grid', 'table'].includes(val)) {
                localStorage.setItem('sipintu_monitoring_view_mode', val);
            }
        });

        window.addEventListener('hashchange', () => {
            const currentHash = window.location.hash.replace('#', '');
            if (validTabs.includes(currentHash)) {
                this.activeTab = currentHash;
            }
        });

        // Cek jika ada parameter ?diagnose=xxx dari halaman lain
        const urlParams = new URLSearchParams(window.location.search);
        const autoDiagnoseId = urlParams.get('diagnose');
        if (autoDiagnoseId) {
            this.runSsoDiagnosis(autoDiagnoseId);
        }
    },
    get filteredClients() {
        return this.clients.filter(c => {
            const matchesTab = this.activeTab === 'all' || c.connection_status === this.activeTab;
            const matchesSearch = c.name.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                                  c.client_id.toLowerCase().includes(this.searchQuery.toLowerCase());
            return matchesTab && matchesSearch;
        });
    },
    async runSsoDiagnosis(clientId, secret = null) {
        if (!clientId) return;
        this.diagnosingSso = true;
        this.ssoDiagnosis = null;
        this.diagModalTab = 'summary';
        this.showSsoModal = true;
        this.selectedClientId = clientId;

        try {
            const res = await fetch('{{ route('admin.monitoring.diagnose-sso') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    client_id: clientId,
                    client_secret: secret
                })
            });
            const json = await res.json();
            if (json.status === 'success') {
                this.ssoDiagnosis = json.data;
            } else {
                alert(json.message || 'Gagal menjalankan diagnosa SSO.');
                this.showSsoModal = false;
            }
        } catch (e) {
            console.error('SSO Diagnosis error:', e);
            alert('Terjadi kesalahan jaringan saat menjalankan diagnosa.');
            this.showSsoModal = false;
        } finally {
            this.diagnosingSso = false;
        }
    },
    copyDiagnosisReport() {
        if (!this.ssoDiagnosis) return;
        const d = this.ssoDiagnosis;
        let text = `=== LAPORAN DIAGNOSA SSO SIPINTU ===\n`;
        text += `Aplikasi     : ${d.application.name} (${d.application.client_id})\n`;
        text += `Waktu Uji    : ${d.diagnosed_at_human || d.timestamp}\n`;
        text += `Kondisi      : ${d.overall_status} (Skor WHI: ${d.health_score}%)\n`;
        text += `Base URL     : ${d.application.base_url}\n`;
        text += `Redirect URI : ${d.application.redirect_uri}\n`;
        if (d.telemetry && d.telemetry.resolved_ip) {
            text += `Resolusi IP  : ${d.telemetry.resolved_ip} (${d.telemetry.ip_classification})\n`;
            text += `Bench Latensi: ${d.telemetry.latency_ms} ms (${d.telemetry.latency_grade})\n`;
        }
        text += `\n--- 8 Titik Uji Presisi ---\n`;
        d.checks.forEach((c, idx) => {
            text += `${idx + 1}. [${c.status}] ${c.name} (${c.latency_ms > 0 ? c.latency_ms + ' ms' : '0 ms'})\n    Hasil: ${c.message}\n`;
        });
        if (d.issues && d.issues.length > 0) {
            text += `\n--- Temuan Akar Masalah (${d.issues.length}) ---\n`;
            d.issues.forEach((issue, idx) => {
                text += `#${idx + 1} [${issue.severity}] ${issue.title} (Lokasi: ${issue.location_label})\n`;
                text += `Penyebab: ${issue.cause}\n`;
                text += `Solusi  : ${issue.solution_title}\n`;
                if (issue.solution_code) {
                    text += `Kode    : ${issue.solution_code}\n`;
                }
                text += `\n`;
            });
        }
        navigator.clipboard.writeText(text);
        this.copiedReport = true;
        setTimeout(() => { this.copiedReport = false; }, 2500);
    },
    async runDiagnoseAll() {
        this.diagnosingAll = true;
        this.batchResults = null;
        this.showBatchModal = true;

        try {
            const res = await fetch('{{ route('admin.monitoring.diagnose-all-sso') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            const json = await res.json();
            if (json.status === 'success') {
                this.batchResults = json.data;
            } else {
                alert('Gagal menjalankan diagnosa massal.');
                this.showBatchModal = false;
            }
        } catch (e) {
            console.error('Batch diagnosis error:', e);
            alert('Terjadi kesalahan jaringan.');
            this.showBatchModal = false;
        } finally {
            this.diagnosingAll = false;
        }
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
    @if(session('success'))

        <div class="p-4 bg-emerald-50 border border-emerald-300 text-emerald-950 rounded-2xl text-xs font-bold flex items-center space-x-3 shadow-xs">
            <svg class="w-5 h-5 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 bg-rose-50 border border-rose-300 text-rose-950 rounded-2xl text-xs font-bold flex items-center space-x-3 shadow-xs">
            <svg class="w-5 h-5 text-rose-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Bright & Vibrant Header Hero Banner-->
    <div class="relative overflow-hidden bg-white border border-emerald-200 rounded-3xl p-4 sm:p-8 shadow-sm min-w-0 max-w-full">

        <!-- Background Soft Accents -->
        <div class="absolute top-0 right-0 w-96 h-96 bg-emerald-100/50 rounded-full blur-3xl pointer-events-none -mr-20 -mt-20"></div>
        <div class="absolute bottom-0 left-0 w-80 h-80 bg-teal-100/40 rounded-full blur-3xl pointer-events-none -ml-20 -mb-20"></div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2">
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-emerald-100 border border-emerald-300 text-emerald-800 text-xs font-extrabold tracking-wide uppercase">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-600 animate-pulse"></span>
                    <span>Connection Check</span>
                </div>
                <h2 class="text-2xl sm:text-3xl font-black tracking-tight text-emerald-950">Dashboard Monitoring Aplikasi Downstream</h2>
                <p class="text-xs sm:text-sm text-slate-600 max-w-2xl font-medium leading-relaxed">
                    Pemantauan visual real-time aktivitas koneksi, volume request API, latensi infrastruktur, dan otorisasi aplikasi klien di bawah SiPintu.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="button" @click="runDiagnoseAll()" :disabled="diagnosingAll" class="px-5 py-2.5 bg-indigo-700 hover:bg-indigo-800 disabled:bg-slate-300 text-white text-xs font-extrabold rounded-2xl transition-all shadow-md shadow-indigo-700/20 flex items-center space-x-2 cursor-pointer">
                    <svg class="w-4 h-4" :class="diagnosingAll ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span x-text="diagnosingAll ? 'Mendiagnosa...' : 'Diagnosa Semua SSO'">Diagnosa Semua SSO</span>
                </button>

                <form action="{{ route('admin.monitoring.run-health-checks') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-2xl transition-all shadow-md shadow-emerald-700/20 flex items-center space-x-2 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>Refresh</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Top Key Metric -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5 min-w-0 max-w-full">
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

    <!-- Database Daily Backup & Disaster Recovery Section -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 space-y-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 pb-5">
            <div class="space-y-1">
                <div class="inline-flex items-center space-x-2 px-2.5 py-0.5 rounded-full bg-emerald-100 border border-emerald-300 text-emerald-800 text-[10px] font-black uppercase tracking-wider">
                    <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
                    <span>Automated Daily Backup Engine</span>
                </div>
                <h3 class="text-base font-black text-emerald-950 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21 3.582 4 8 4s8-1.79 8-4"></path>
                    </svg>
                    Cadangan Database & Arsip Recovery SiPintu
                </h3>
                <p class="text-xs text-slate-600 font-medium">
                    Pencadangan database MySQL/MariaDB otomatis berjalan setiap hari pukul <strong class="text-slate-800 font-bold">02:00 AM WIB</strong> dengan kompresi gzip. Retensi file otomatis dipertahankan selama 7 hari.
                </p>
            </div>

            <div class="flex items-center gap-3 shrink-0">
                <form action="{{ route('admin.monitoring.backup.create') }}" method="POST" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button span').innerText = 'Membuat Cadangan...';">
                    @csrf
                    <button type="submit" class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 active:scale-95 text-white text-xs font-black rounded-2xl transition-all shadow-md shadow-emerald-700/20 flex items-center space-x-2 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                        </svg>
                        <span>Backup Database Sekarang</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- 4 Backup Quick Info Badges -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-2xl">
                <span class="text-[10px] font-extrabold uppercase text-slate-500 block">Jadwal Harian</span>
                <span class="text-sm font-black text-slate-900 flex items-center gap-1.5 mt-0.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    <span>02:00 WIB</span>
                </span>
                <span class="text-[10px] text-slate-500 font-medium">Otomatis via Scheduler</span>
            </div>

            <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl">
                <span class="text-[10px] font-extrabold uppercase text-emerald-800 block">Backup Terakhir</span>
                <span class="text-sm font-black text-emerald-950 mt-0.5 block truncate">
                    {{ !empty($backups) ? $backups[0]['created_at_human'] : 'Belum pernah' }}
                </span>
                <span class="text-[10px] text-emerald-700 font-medium truncate block">
                    {{ !empty($backups) ? $backups[0]['created_at_formatted'] : '-' }}
                </span>
            </div>

            <div class="p-3.5 bg-indigo-50 border border-indigo-200 rounded-2xl">
                <span class="text-[10px] font-extrabold uppercase text-indigo-800 block">Total Arsip Tersedia</span>
                <span class="text-sm font-black text-indigo-950 mt-0.5 block font-mono">
                    {{ count($backups) }} File Cadangan
                </span>
                <span class="text-[10px] text-indigo-700 font-medium">Batas Retensi 7 Hari</span>
            </div>

            <div class="p-3.5 bg-teal-50 border border-teal-200 rounded-2xl">
                <span class="text-[10px] font-extrabold uppercase text-teal-800 block">Ukuran Terkini</span>
                <span class="text-sm font-black text-teal-950 mt-0.5 block font-mono">
                    {{ !empty($backups) ? $backups[0]['size_human'] : '0 B' }}
                </span>
                <span class="text-[10px] text-teal-700 font-medium">Kompresi Gzip (.sql.gz)</span>
            </div>
        </div>

        <!-- Backups List Table -->
        <div class="overflow-x-auto border border-slate-200 rounded-2xl w-full max-w-full">
            <table class="w-full text-left text-xs min-w-[650px]">
                <thead class="bg-slate-50 text-slate-700 uppercase font-black text-[10px] border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Nama Arsip Backup</th>
                        <th class="px-4 py-3">Waktu Pembuatan</th>
                        <th class="px-4 py-3 text-center">Ukuran Terkompresi</th>
                        <th class="px-4 py-3 text-center">Format</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($backups as $b)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-4 py-3 font-mono font-bold text-slate-900 flex items-center gap-2">
                                <div class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-800 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                                <span class="truncate">{{ $b['filename'] }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-700">
                                <div class="font-bold text-slate-900">{{ $b['created_at_formatted'] }}</div>
                                <div class="text-[10px] text-slate-500 font-medium">{{ $b['created_at_human'] }}</div>
                            </td>
                            <td class="px-4 py-3 text-center font-mono font-black text-emerald-800">
                                {{ $b['size_human'] }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 border border-emerald-200 uppercase">
                                    GZIP SQL
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="button" @click="selectedRestoreFile = '{{ $b['filename'] }}'; openRestoreModal = true;" class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 border border-amber-300 text-amber-900 text-xs font-bold rounded-xl transition-all inline-flex items-center gap-1.5 shadow-2xs cursor-pointer" title="Pulihkan (Restore) database dari arsip ini">
                                        <svg class="w-3.5 h-3.5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                        <span>Pulihkan</span>
                                    </button>
                                    <a href="{{ route('admin.monitoring.backup.download', $b['filename']) }}" class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 border border-emerald-300 text-emerald-900 text-xs font-bold rounded-xl transition-all inline-flex items-center gap-1.5 shadow-2xs" title="Unduh Arsip Cadangan">
                                        <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                        <span>Unduh</span>
                                    </a>
                                    <form action="{{ route('admin.monitoring.backup.delete', $b['filename']) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus file backup {{ $b['filename'] }}?');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-xl transition-colors cursor-pointer" title="Hapus Backup">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500 text-xs">
                                <div class="max-w-xs mx-auto space-y-2">
                                    <div class="w-10 h-10 mx-auto rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                                    </div>
                                    <p class="font-bold text-slate-700">Belum ada file cadangan database</p>
                                    <p class="text-[11px] text-slate-500">Klik tombol "Backup Database Sekarang" di atas atau tunggu jadwal otomatis pukul 02:00 WIB.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Security Center & Brute Force Prevention Section -->
    <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-7 space-y-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-5">
            <div>
                <div class="inline-flex items-center space-x-2 px-3 py-1 rounded-full bg-rose-100 border border-rose-200 text-rose-800 text-[11px] font-extrabold tracking-wide uppercase mb-1.5">
                    <svg class="w-3.5 h-3.5 text-rose-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <span>Brute Force Protection & Security Center</span>
                </div>
                <h3 class="text-xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                    Proteksi Serangan & Manajemen IP Terblokir
                </h3>
                <p class="text-xs text-slate-500 font-medium mt-1">
                    Sistem otomatis mengunci IP yang gagal login 5x berturut-turut selama 15 menit. Anda dapat membuka blokir atau memblokir IP secara manual.
                </p>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap shrink-0">
                <button type="button" @click="openBlockIpModal = true" class="px-4 py-2.5 bg-rose-50 hover:bg-rose-100 border border-rose-300 text-rose-900 text-xs font-black rounded-xl transition-all shadow-2xs inline-flex items-center gap-1.5 cursor-pointer">
                    <svg class="w-4 h-4 text-rose-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    <span>Tambah Blokir IP Manual</span>
                </button>
            </div>
        </div>

        <!-- 3 Quick Security Badges -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <span class="text-[10px] font-extrabold uppercase text-slate-500 block">IP Terblokir Aktif Saat Ini</span>
                <div class="text-xl font-black {{ $activeBlockedCount > 0 ? 'text-rose-700' : 'text-emerald-700' }} mt-1">
                    {{ $activeBlockedCount }} Alamat IP
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5 font-medium">
                    {{ $activeBlockedCount > 0 ? 'Sedang dibatasi aksesnya oleh sistem gateway' : 'Semua IP normal & tidak ada blokir aktif' }}
                </p>
            </div>

            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <span class="text-[10px] font-extrabold uppercase text-slate-500 block">Deteksi Brute Force (24 Jam)</span>
                <div class="text-xl font-black text-amber-700 mt-1">
                    {{ $bruteForceCount24h }} Kali Terdeteksi
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5 font-medium">Serangan login beruntun yang berhasil ditangkal</p>
            </div>

            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <span class="text-[10px] font-extrabold uppercase text-slate-500 block">Batas Percobaan Gagal</span>
                <div class="text-xl font-black text-slate-800 mt-1 font-mono">
                    5 Percobaan / 15 Menit
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5 font-medium">Otomatis reset setelah login berhasil</p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Table IP Terblokir (Cols 7) -->
            <div class="lg:col-span-7 space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        <span>Daftar IP Address Terblokir</span>
                    </h4>
                    <span class="text-[11px] text-slate-500 font-semibold">{{ count($blockedIps) }} entri</span>
                </div>

                <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-2xs">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600 font-black uppercase text-[10px]">
                                <tr>
                                    <th class="px-3.5 py-2.5">IP Address</th>
                                    <th class="px-3.5 py-2.5">Alasan & Durasi</th>
                                    <th class="px-3.5 py-2.5 text-center">Status</th>
                                    <th class="px-3.5 py-2.5 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($blockedIps as $bIp)
                                    @php
                                        $isCurrentlyBlocked = $bIp->is_active && (empty($bIp->expires_at) || \Carbon\Carbon::parse($bIp->expires_at)->isFuture());
                                    @endphp
                                    <tr class="hover:bg-slate-50/70 transition-colors {{ $isCurrentlyBlocked ? 'bg-rose-50/20' : '' }}">
                                        <td class="px-3.5 py-2.5 font-mono font-bold text-slate-900">
                                            {{ $bIp->ip_address }}
                                        </td>
                                        <td class="px-3.5 py-2.5 text-slate-600">
                                            <div class="text-[11px] font-medium leading-tight max-w-xs truncate" title="{{ $bIp->reason }}">
                                                {{ $bIp->reason }}
                                            </div>
                                            <div class="text-[10px] text-slate-400 mt-0.5">
                                                Kedaluwarsa: {{ $bIp->expires_at ? \Carbon\Carbon::parse($bIp->expires_at)->diffForHumans() : 'Permanen' }}
                                            </div>
                                        </td>
                                        <td class="px-3.5 py-2.5 text-center">
                                            @if($isCurrentlyBlocked)
                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-black uppercase bg-rose-100 text-rose-800 border border-rose-300">
                                                    Diblokir
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase bg-slate-100 text-slate-600 border border-slate-200">
                                                    Selesai
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-3.5 py-2.5 text-right">
                                            @if($isCurrentlyBlocked)
                                                <form action="{{ route('admin.monitoring.blocked-ips.destroy', $bIp->id) }}" method="POST" onsubmit="return confirm('Buka blokir IP {{ $bIp->ip_address }}?');" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-lg text-[10px] font-extrabold transition-all cursor-pointer" title="Buka blokir IP ini">
                                                        Buka Blokir
                                                    </button>
                                                </form>
                                            @else
                                                <span class="text-[10px] text-slate-400">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-slate-400 text-xs">
                                            <svg class="w-8 h-8 mx-auto text-emerald-500 mb-2 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                            <p class="font-bold text-slate-700">Tidak ada IP yang sedang diblokir</p>
                                            <p class="text-[11px] text-slate-500">Semua aktivitas autentikasi berada dalam kondisi normal.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Table Riwayat Security Logs (Cols 5) -->
            <div class="lg:col-span-5 space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-black uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        <span>Riwayat Log Keamanan Terbaru</span>
                    </h4>
                    <a href="{{ route('admin.audit-logs.index') }}" class="text-[11px] text-emerald-700 hover:text-emerald-800 font-bold">
                        Audit Log Lengkap &rarr;
                    </a>
                </div>

                <div class="border border-slate-200 rounded-2xl overflow-hidden bg-white shadow-2xs divide-y divide-slate-100 max-h-[380px] overflow-y-auto">
                    @forelse($securityLogs as $sLog)
                        <div class="p-3 text-xs flex items-start justify-between gap-3 hover:bg-slate-50/80 transition-colors">
                            <div class="space-y-0.5 min-w-0">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="font-bold text-slate-900 truncate max-w-[150px]" title="{{ $sLog->target_identifier }}">
                                        {{ $sLog->target_identifier ?? 'Anonim' }}
                                    </span>
                                    @if($sLog->event_type === 'brute_force_detected')
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-rose-100 text-rose-800 border border-rose-300">Brute Force</span>
                                    @elseif($sLog->event_type === 'login_failed')
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-amber-100 text-amber-800 border border-amber-300">Gagal</span>
                                    @else
                                        <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-emerald-100 text-emerald-800 border border-emerald-300">Sukses</span>
                                    @endif
                                </div>
                                <div class="text-[10px] text-slate-500 font-mono">
                                    IP: {{ $sLog->ip_address }}
                                </div>
                            </div>
                            <div class="text-[10px] text-slate-400 font-medium shrink-0 text-right">
                                {{ $sLog->created_at->diffForHumans() }}
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-slate-400 text-xs">
                            Belum ada catatan log ancaman keamanan.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-100 pb-5">

            <div>
                <h3 class="text-base font-black text-emerald-950 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    Katalog Telemetry Aplikasi Client
                </h3>
                <p class="text-xs text-slate-600 font-medium mt-0.5">Filter dan pantau status koneksi individual aplikasi downstream yang mengakses REST API</p>
            </div>

            <!-- Controls: Filter Tabs + View Mode Toggle -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Search Input -->
                <div class="relative">
                    <input type="text" x-model="searchQuery" placeholder="Cari nama..." class="pl-8 pr-3 py-1.5 text-xs rounded-xl border border-slate-300 focus:border-emerald-500 focus:ring-emerald-500 w-48">
                    <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>

                <!-- Filter Tabs -->
                <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200 text-xs font-extrabold">
                    <button @click="activeTab = 'all'" :class="activeTab === 'all' ? 'bg-white text-emerald-950 shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="px-3 py-1 rounded-lg transition-all">Semua</button>
                    <button @click="activeTab = 'connected'" :class="activeTab === 'connected' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-emerald-700'" class="px-3 py-1 rounded-lg transition-all inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-400 inline-block"></span> Online</button>
                    <button @click="activeTab = 'disconnected'" :class="activeTab === 'disconnected' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-600 hover:text-rose-700'" class="px-3 py-1 rounded-lg transition-all inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-rose-400 inline-block"></span> Offline</button>
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

                        <!-- Action Buttons: Ping & SSO Diagnose -->
                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <button @click="validateClient(client.client_id, '')" class="py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-extrabold rounded-xl transition-all border border-slate-200 flex items-center justify-center space-x-1.5 cursor-pointer">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <span>Test Ping</span>
                            </button>
                            <button @click="runSsoDiagnosis(client.client_id)" class="py-2 bg-indigo-700 hover:bg-indigo-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-xs flex items-center justify-center space-x-1.5 cursor-pointer">
                                <svg class="w-3.5 h-3.5 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Diagnosa SSO</span>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <!-- 2. VISUAL TABLE VIEW -->
        <template x-if="viewMode === 'table'">
            <div class="overflow-x-auto border border-slate-200 rounded-xl w-full max-w-full">
                <table class="w-full text-left text-xs min-w-[650px]">
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
                                    <div class="inline-flex items-center gap-1.5 justify-center">
                                        <button @click="validateClient(client.client_id, '')" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-bold rounded-lg transition-colors border border-slate-200">
                                            Ping
                                        </button>
                                        <button @click="runSsoDiagnosis(client.client_id)" class="px-2.5 py-1 bg-indigo-700 hover:bg-indigo-800 text-white text-[11px] font-extrabold rounded-lg transition-colors shadow-2xs flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <span>Diagnosa</span>
                                        </button>
                                    </div>
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
                        <span class="font-black text-sm" x-text="clientResult.valid ? 'RESPONS REST API: KONEKSI & OTORISASI VALID' : 'RESPONS REST API: TERJADI KESALAHAN OTORISASI'"></span>
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

    <!-- 1. MODAL INTERAKTIF: DIAGNOSA KONEKSI SSO APLIKASI SPESIFIK -->
    <!-- ========================================================================= -->
    <div x-show="showSsoModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/70 backdrop-blur-xs overflow-y-auto"
         style="display: none;">
        
        <div @click.outside="showSsoModal = false" 
             class="bg-white rounded-3xl border border-slate-200 shadow-2xl w-full max-w-5xl overflow-hidden my-4 sm:my-8 flex flex-col max-h-[92vh]">
            
            <!-- Modal Header -->
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/80 flex flex-col gap-3 shrink-0">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3 min-w-0">
                        <div class="p-2.5 rounded-2xl bg-indigo-600 text-white shadow-md shadow-indigo-600/20 shrink-0">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base sm:text-lg font-black text-slate-900 truncate" x-text="ssoDiagnosis ? ssoDiagnosis.application.name : 'Diagnosa Koneksi SSO Presisi'"></h3>
                                <template x-if="ssoDiagnosis">
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-slate-200 text-slate-800" x-text="ssoDiagnosis.application.client_id"></span>
                                </template>
                            </div>
                            <p class="text-[11px] sm:text-xs text-slate-500 font-medium truncate">
                                Mesin Diagnostik 8 Titik Uji Presisi • Benchmark Latensi • Analisis Akar Masalah (RCA)
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 shrink-0">
                        <template x-if="ssoDiagnosis">
                            <button type="button" 
                                    @click="copyDiagnosisReport()" 
                                    class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl text-xs font-extrabold transition-all shadow-2xs flex items-center space-x-1 cursor-pointer">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                </svg>
                                <span x-text="copiedReport ? 'Tersalin!' : 'Salin Laporan'">Salin Laporan</span>
                            </button>
                        </template>

                        <button @click="showSsoModal = false" class="p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                </div>

                <!-- Navigation Tabs Bar -->
                <template x-if="!diagnosingSso && ssoDiagnosis">
                    <div class="flex items-center space-x-1.5 border-t border-slate-200/60 pt-2.5 overflow-x-auto text-xs font-bold scrollbar-none">
                        <button type="button" 
                                @click="diagModalTab = 'summary'"
                                :class="diagModalTab === 'summary' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-200/70'"
                                class="px-3 py-1.5 rounded-xl transition-all flex items-center space-x-1.5 shrink-0 cursor-pointer">
                            <span>📊 Ringkasan & Skor Presisi</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono"
                                  :class="diagModalTab === 'summary' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'"
                                  x-text="ssoDiagnosis.health_score + '%'">
                            </span>
                        </button>

                        <button type="button" 
                                @click="diagModalTab = 'matrix'"
                                :class="diagModalTab === 'matrix' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-200/70'"
                                class="px-3 py-1.5 rounded-xl transition-all flex items-center space-x-1.5 shrink-0 cursor-pointer">
                            <span>🔍 Matriks 8 Titik Uji</span>
                            <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono"
                                  :class="diagModalTab === 'matrix' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'"
                                  x-text="ssoDiagnosis.checks.length">
                            </span>
                        </button>

                        <button type="button" 
                                @click="diagModalTab = 'rca'"
                                :class="diagModalTab === 'rca' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-200/70'"
                                class="px-3 py-1.5 rounded-xl transition-all flex items-center space-x-1.5 shrink-0 cursor-pointer">
                            <span>🛠️ Akar Masalah & Solusi (RCA)</span>
                            <template x-if="ssoDiagnosis.issues && ssoDiagnosis.issues.length > 0">
                                <span class="px-1.5 py-0.2 rounded-full text-[10px] font-mono bg-rose-500 text-white"
                                      x-text="ssoDiagnosis.issues.length">
                                </span>
                            </template>
                        </button>

                        <button type="button" 
                                @click="diagModalTab = 'raw'"
                                :class="diagModalTab === 'raw' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-200/70'"
                                class="px-3 py-1.5 rounded-xl transition-all flex items-center space-x-1.5 shrink-0 cursor-pointer">
                            <span>⚙️ Telemetri JSON</span>
                        </button>
                    </div>
                </template>
            </div>

            <!-- Modal Body (Scrollable) -->
            <div class="p-4 sm:p-6 space-y-6 overflow-y-auto flex-1">
                <!-- Loading State -->
                <template x-if="diagnosingSso">
                    <div class="py-16 text-center space-y-4">
                        <div class="inline-flex p-4 rounded-full bg-indigo-50 text-indigo-600 animate-spin">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </div>
                        <h4 class="text-base font-black text-slate-900">Mendiagnosa Koneksi SSO Presisi...</h4>
                        <p class="text-xs text-slate-500 max-w-md mx-auto leading-relaxed">
                            Menguji 8 titik integrasi: integritas konfigurasi gateway, origin matching, DNS & IP resolution, route token OAuth, endpoint /health, route /oauth/callback, signed webhook, dan lifecycle token.
                        </p>
                    </div>
                </template>

                <!-- Result State -->
                <template x-if="!diagnosingSso && ssoDiagnosis">
                    <div class="space-y-6">

                        <!-- ================= TAB 1: RINGKASAN & SKOR WHI ================= -->
                        <div x-show="diagModalTab === 'summary'" class="space-y-5">
                            <!-- Overall Status Banner -->
                            <div class="p-5 rounded-2xl border flex flex-col sm:flex-row sm:items-center justify-between gap-4 transition-all"
                                 :class="{
                                     'bg-emerald-50 border-emerald-300 text-emerald-950': ssoDiagnosis.overall_status === 'HEALTHY',
                                     'bg-amber-50 border-amber-300 text-amber-950': ssoDiagnosis.overall_status === 'WARNING',
                                     'bg-rose-50 border-rose-300 text-rose-950': ssoDiagnosis.overall_status === 'CRITICAL'
                                 }">
                                <div class="flex items-start space-x-3.5">
                                    <div class="p-3.5 rounded-2xl shrink-0 text-white font-black text-xl shadow-xs"
                                         :class="{
                                             'bg-emerald-600': ssoDiagnosis.overall_status === 'HEALTHY',
                                             'bg-amber-600': ssoDiagnosis.overall_status === 'WARNING',
                                             'bg-rose-600': ssoDiagnosis.overall_status === 'CRITICAL'
                                         }">
                                        <span x-text="ssoDiagnosis.health_score + '%'"></span>
                                    </div>
                                    <div class="space-y-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-xs font-black uppercase tracking-wider px-2 py-0.5 rounded-md"
                                                  :class="{
                                                      'bg-emerald-200 text-emerald-900': ssoDiagnosis.overall_status === 'HEALTHY',
                                                      'bg-amber-200 text-amber-900': ssoDiagnosis.overall_status === 'WARNING',
                                                      'bg-rose-200 text-rose-900': ssoDiagnosis.overall_status === 'CRITICAL'
                                                  }"
                                                  x-text="ssoDiagnosis.overall_status">
                                            </span>
                                            <h4 class="text-sm sm:text-base font-black" x-text="ssoDiagnosis.status_text"></h4>
                                        </div>
                                        <p class="text-xs opacity-90 font-medium">
                                            Diuji: <span class="font-mono" x-text="ssoDiagnosis.diagnosed_at_human || new Date(ssoDiagnosis.timestamp).toLocaleString()"></span> • 
                                            Base: <code class="font-mono font-bold" x-text="ssoDiagnosis.application.base_url"></code>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 text-[11px] font-mono font-bold shrink-0 self-end sm:self-auto">
                                    <span class="px-2.5 py-1 bg-white/90 rounded-lg border shadow-2xs text-emerald-800 border-emerald-200" x-text="ssoDiagnosis.summary.passed + ' Lulus'"></span>
                                    <span class="px-2.5 py-1 bg-white/90 rounded-lg border shadow-2xs text-amber-800 border-amber-200" x-text="ssoDiagnosis.summary.warnings + ' Peringatan'"></span>
                                    <span class="px-2.5 py-1 bg-white/90 rounded-lg border shadow-2xs text-rose-800 border-rose-200" x-text="ssoDiagnosis.summary.failed + ' Gagal'"></span>
                                </div>
                            </div>

                            <!-- 3 Quick Telemetry Cards -->
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                                <!-- Card 1: DNS & Jaringan -->
                                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-1.5">
                                    <div class="flex items-center justify-between text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">
                                        <span>Resolusi Host & DNS</span>
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
                                    </div>
                                    <div class="font-mono text-xs font-bold text-slate-900" x-text="ssoDiagnosis.telemetry.resolved_ip || 'Tidak Terdeteksi'"></div>
                                    <div class="text-[11px] text-slate-500 font-medium" x-text="ssoDiagnosis.telemetry.ip_classification"></div>
                                </div>

                                <!-- Card 2: Benchmarking Latensi -->
                                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-1.5">
                                    <div class="flex items-center justify-between text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">
                                        <span>Benchmarking Latensi</span>
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                    </div>
                                    <div class="flex items-baseline space-x-2">
                                        <span class="text-base font-black text-slate-900 font-mono" x-text="(ssoDiagnosis.telemetry.latency_ms || 0) + ' ms'"></span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase font-mono"
                                              :class="{
                                                  'bg-emerald-100 text-emerald-800': ssoDiagnosis.telemetry.latency_grade === 'ultra_fast' || ssoDiagnosis.telemetry.latency_grade === 'optimal',
                                                  'bg-amber-100 text-amber-800': ssoDiagnosis.telemetry.latency_grade === 'warning',
                                                  'bg-rose-100 text-rose-800': ssoDiagnosis.telemetry.latency_grade === 'critical'
                                              }"
                                              x-text="ssoDiagnosis.telemetry.latency_grade === 'ultra_fast' ? 'Sangat Cepat' : (ssoDiagnosis.telemetry.latency_grade === 'optimal' ? 'Optimal' : (ssoDiagnosis.telemetry.latency_grade === 'warning' ? 'Tolerable' : 'Tinggi / Timeout'))">
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-slate-500 font-medium">Batas toleransi SiPintu: 3000 ms</div>
                                </div>

                                <!-- Card 3: Sesi & Token Gateway -->
                                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-1.5">
                                    <div class="flex items-center justify-between text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">
                                        <span>Sesi Token & Aktivitas</span>
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                                    </div>
                                    <div class="text-xs font-bold text-slate-900">
                                        <span class="font-mono text-indigo-700" x-text="ssoDiagnosis.telemetry.active_tokens_count"></span> Token Aktif
                                        <span class="text-slate-400 font-normal">|</span>
                                        <span class="font-mono text-slate-700" x-text="ssoDiagnosis.telemetry.total_api_requests"></span> Panggilan API
                                    </div>
                                    <div class="text-[11px] text-slate-500 font-medium truncate" x-text="'Aktif: ' + ssoDiagnosis.telemetry.last_connected_human"></div>
                                </div>
                            </div>

                            <!-- Shortcut to RCA if issues exist -->
                            <template x-if="ssoDiagnosis.issues && ssoDiagnosis.issues.length > 0">
                                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 flex items-center justify-between gap-3">
                                    <div class="flex items-center space-x-2.5">
                                        <span class="w-3 h-3 rounded-full bg-rose-600 animate-pulse shrink-0"></span>
                                        <span class="text-xs font-bold text-rose-900">
                                            Ditemukan <span class="font-black" x-text="ssoDiagnosis.issues.length"></span> akar masalah yang memerlukan perbaikan.
                                        </span>
                                    </div>
                                    <button type="button" 
                                            @click="diagModalTab = 'rca'" 
                                            class="px-3 py-1.5 bg-rose-700 hover:bg-rose-800 text-white rounded-xl text-xs font-extrabold transition-all shrink-0 cursor-pointer shadow-xs">
                                        Buka Panduan Solusi &rarr;
                                    </button>
                                </div>
                            </template>
                        </div>


                        <!-- ================= TAB 2: MATRIKS 8 TITIK UJI ================= -->
                        <div x-show="diagModalTab === 'matrix'" class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                    Matriks 8 Titik Pemeriksaan Presisi
                                </h4>
                                <span class="text-[11px] text-slate-500 font-medium">Evaluasi menyeluruh jaringan, routing, token & webhook</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <template x-for="(check, idx) in ssoDiagnosis.checks" :key="check.id">
                                    <div class="p-3.5 rounded-2xl border transition-all space-y-2 flex flex-col justify-between"
                                         :class="{
                                             'bg-emerald-50/40 border-emerald-200 text-emerald-950': check.status === 'PASS',
                                             'bg-amber-50/40 border-amber-200 text-amber-950': check.status === 'WARN',
                                             'bg-rose-50/40 border-rose-200 text-rose-950': check.status === 'FAIL'
                                         }">
                                        <div class="space-y-1.5">
                                            <div class="flex items-start justify-between gap-2">
                                                <div class="flex items-center space-x-2">
                                                    <span class="w-2.5 h-2.5 rounded-full shrink-0"
                                                          :class="{
                                                              'bg-emerald-600': check.status === 'PASS',
                                                              'bg-amber-500': check.status === 'WARN',
                                                              'bg-rose-600 animate-ping': check.status === 'FAIL'
                                                          }">
                                                    </span>
                                                    <span class="font-extrabold text-xs text-slate-900" x-text="(idx + 1) + '. ' + check.name"></span>
                                                </div>
                                                <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase font-mono tracking-wider shrink-0"
                                                      :class="{
                                                          'bg-emerald-200 text-emerald-900': check.status === 'PASS',
                                                          'bg-amber-200 text-amber-900': check.status === 'WARN',
                                                          'bg-rose-200 text-rose-900': check.status === 'FAIL'
                                                      }"
                                                      x-text="check.status">
                                                </span>
                                            </div>

                                            <p class="text-[11px] text-slate-600 leading-relaxed font-medium" x-text="check.message"></p>
                                        </div>

                                        <div class="flex items-center justify-between text-[10px] font-mono text-slate-500 pt-2 border-t border-slate-200/60">
                                            <span class="truncate max-w-[200px]" x-text="check.target"></span>
                                            <div class="flex items-center space-x-1.5 shrink-0">
                                                <template x-if="check.http_code">
                                                    <span class="px-1.5 py-0.2 rounded bg-slate-200/70 text-slate-800 font-bold" x-text="'HTTP ' + check.http_code"></span>
                                                </template>
                                                <template x-if="check.latency_ms > 0">
                                                    <span class="font-bold text-slate-700" x-text="check.latency_ms + ' ms'"></span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>


                        <!-- ================= TAB 3: AKAR MASALAH & SOLUSI (RCA) ================= -->
                        <div x-show="diagModalTab === 'rca'" class="space-y-4">
                            <!-- Clean state -->
                            <template x-if="!ssoDiagnosis.issues || ssoDiagnosis.issues.length === 0">
                                <div class="p-8 rounded-2xl bg-emerald-50 border border-emerald-300 text-center space-y-2.5 my-4">
                                    <div class="w-12 h-12 rounded-full bg-emerald-600 text-white mx-auto flex items-center justify-center font-bold text-2xl shadow-md shadow-emerald-600/20">✓</div>
                                    <h4 class="text-base font-black text-emerald-950">Seluruh Titik Pemeriksaan Lulus 100%</h4>
                                    <p class="text-xs text-emerald-800 max-w-md mx-auto font-medium leading-relaxed">
                                        Tidak ditemukan kendala integrasi apapun. Aplikasi downstream siap menerima pertukaran authorization code, penerbitan token, dan sinkronisasi real-time.
                                    </p>
                                </div>
                            </template>

                            <!-- Issues list -->
                            <template x-if="ssoDiagnosis.issues && ssoDiagnosis.issues.length > 0">
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-xs font-black text-rose-900 uppercase tracking-wider flex items-center gap-2">
                                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                            Akar Masalah Terdeteksi & Panduan Solusi (<span x-text="ssoDiagnosis.issues.length"></span>)
                                        </h4>
                                        <span class="text-[10px] text-slate-500 font-medium">Ikuti langkah preskriptif di bawah ini</span>
                                    </div>

                                    <div class="space-y-4">
                                        <template x-for="(issue, idx) in ssoDiagnosis.issues" :key="issue.id">
                                            <div class="p-4 rounded-2xl border space-y-3 shadow-xs"
                                                 :class="issue.severity === 'CRITICAL' ? 'bg-rose-50/70 border-rose-300' : 'bg-amber-50/70 border-amber-300'">
                                                
                                                <!-- Issue Header -->
                                                <div class="flex flex-wrap items-center justify-between gap-2 border-b pb-2.5"
                                                     :class="issue.severity === 'CRITICAL' ? 'border-rose-200' : 'border-amber-200'">
                                                    <div class="flex items-center space-x-2">
                                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase text-white shadow-2xs"
                                                              :class="issue.severity === 'CRITICAL' ? 'bg-rose-600' : 'bg-amber-600'"
                                                              x-text="issue.severity">
                                                        </span>
                                                        <h5 class="text-xs sm:text-sm font-black text-slate-900" x-text="issue.title"></h5>
                                                    </div>

                                                    <!-- Lokasi Error Badge -->
                                                    <div class="flex items-center space-x-1.5">
                                                        <span class="text-[10px] text-slate-500 font-bold">Terjadi Di:</span>
                                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider shadow-2xs"
                                                              :class="{
                                                                  'bg-indigo-100 text-indigo-800 border border-indigo-300': issue.location === 'GATEWAY_SIPINTU',
                                                                  'bg-blue-100 text-blue-800 border border-blue-300': issue.location === 'APLIKASI_DOWNSTREAM',
                                                                  'bg-orange-100 text-orange-800 border border-orange-300': issue.location === 'JARINGAN_NETWORK'
                                                              }"
                                                              x-text="issue.location_label">
                                                        </span>
                                                    </div>
                                                </div>

                                                <!-- Mengapa Terjadi -->
                                                <div class="space-y-1">
                                                    <span class="text-[10px] font-extrabold text-slate-500 uppercase flex items-center gap-1">
                                                        <span>❓ Akar Masalah (Root Cause):</span>
                                                    </span>
                                                    <p class="text-xs text-slate-700 font-medium leading-relaxed bg-white/80 p-2.5 rounded-xl border border-slate-200/60" x-text="issue.cause"></p>
                                                </div>

                                                <!-- Solusi Perbaikan -->
                                                <div class="space-y-1.5 pt-1">
                                                    <span class="text-[10px] font-extrabold text-slate-900 uppercase flex items-center gap-1">
                                                        <span>🛠️ Langkah Perbaikan:</span>
                                                        <span class="text-emerald-700 font-black" x-text="issue.solution_title"></span>
                                                    </span>

                                                    <ul class="space-y-1 text-xs text-slate-700 pl-4 list-disc font-medium">
                                                        <template x-for="step in issue.solution_steps" :key="step">
                                                            <li x-text="step"></li>
                                                        </template>
                                                    </ul>

                                                    <template x-if="issue.solution_code">
                                                        <div class="mt-2 bg-slate-900 text-emerald-400 p-3 rounded-xl font-mono text-[11px] flex items-center justify-between gap-2 shadow-inner">
                                                            <span class="break-all select-all" x-text="issue.solution_code"></span>
                                                            <button type="button" 
                                                                    @click="navigator.clipboard.writeText(issue.solution_code); alert('Perintah/kode solusi disalin!');"
                                                                    class="px-2.5 py-1 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-[10px] font-bold shrink-0 transition-colors cursor-pointer">
                                                                Salin Kode
                                                            </button>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>


                        <!-- ================= TAB 4: TELEMETRI RAW JSON ================= -->
                        <div x-show="diagModalTab === 'raw'" class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
                                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>
                                    Data Telemetri Lengkap (Format JSON)
                                </h4>
                                <button type="button" 
                                        @click="navigator.clipboard.writeText(JSON.stringify(ssoDiagnosis, null, 2)); alert('JSON telemetri disalin ke clipboard!');"
                                        class="px-3 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg transition-colors cursor-pointer">
                                    Salin JSON
                                </button>
                            </div>
                            <div class="bg-slate-950 text-emerald-400 p-4 rounded-2xl font-mono text-[11px] max-h-96 overflow-y-auto shadow-inner leading-relaxed select-all">
                                <pre x-text="JSON.stringify(ssoDiagnosis, null, 2)"></pre>
                            </div>
                        </div>

                    </div>
                </template>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between shrink-0">
                <template x-if="ssoDiagnosis">
                    <button type="button" 
                            @click="runSsoDiagnosis(ssoDiagnosis.application.client_id)" 
                            :disabled="diagnosingSso"
                            class="px-4 py-2 bg-indigo-700 hover:bg-indigo-800 disabled:bg-slate-300 text-white text-xs font-extrabold rounded-xl transition-all shadow-xs flex items-center space-x-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" :class="diagnosingSso ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <span>Uji Ulang Diagnosa</span>
                    </button>
                </template>

                <div class="ml-auto">
                    <button type="button" @click="showSsoModal = false" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-extrabold rounded-xl transition-all cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>


    <div x-show="showBatchModal" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs overflow-y-auto"
         style="display: none;">
        
        <div @click.outside="showBatchModal = false" 
             class="bg-white rounded-3xl border border-slate-200 shadow-2xl w-full max-w-4xl overflow-hidden my-8 flex flex-col max-h-[90vh]">
            
            <div class="px-6 py-5 border-b border-slate-100 bg-slate-50 flex items-center justify-between shrink-0">
                <div class="flex items-center space-x-3">
                    <div class="p-2.5 rounded-2xl bg-indigo-100 text-indigo-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-900">Hasil Diagnosa Massal Seluruh Aplikasi SSO</h3>
                        <p class="text-xs text-slate-500 font-medium">Ringkasan status kesiapan koneksi SSO seluruh downstream terdaftar</p>
                    </div>
                </div>

                <button @click="showBatchModal = false" class="p-2 text-slate-400 hover:text-slate-600 rounded-xl hover:bg-slate-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            <div class="p-6 space-y-6 overflow-y-auto flex-1">
                <template x-if="diagnosingAll">
                    <div class="py-16 text-center space-y-4">
                        <div class="inline-flex p-4 rounded-full bg-indigo-50 text-indigo-600 animate-spin">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        </div>
                        <h4 class="text-base font-black text-slate-900">Mendiagnosa Seluruh Aplikasi Downstream...</h4>
                    </div>
                </template>

                <template x-if="!diagnosingAll && batchResults">
                    <div class="space-y-5">
                        <!-- Summary metrics -->
                        <div class="grid grid-cols-4 gap-3 text-center">
                            <div class="p-3.5 bg-slate-50 rounded-2xl border border-slate-200">
                                <span class="text-slate-500 text-[10px] font-extrabold uppercase block">Total Aplikasi</span>
                                <span class="text-xl font-black text-slate-900" x-text="batchResults.total_applications"></span>
                            </div>
                            <div class="p-3.5 bg-emerald-50 rounded-2xl border border-emerald-200">
                                <span class="text-emerald-700 text-[10px] font-extrabold uppercase block">🟢 Sehat</span>
                                <span class="text-xl font-black text-emerald-800" x-text="batchResults.healthy_count"></span>
                            </div>
                            <div class="p-3.5 bg-amber-50 rounded-2xl border border-amber-200">
                                <span class="text-amber-700 text-[10px] font-extrabold uppercase block">🟡 Peringatan</span>
                                <span class="text-xl font-black text-amber-800" x-text="batchResults.warning_count"></span>
                            </div>
                            <div class="p-3.5 bg-rose-50 rounded-2xl border border-rose-200">
                                <span class="text-rose-700 text-[10px] font-extrabold uppercase block">🔴 Masalah Kritis</span>
                                <span class="text-xl font-black text-rose-800" x-text="batchResults.critical_count"></span>
                            </div>
                        </div>

                        <!-- Table Results -->
                        <div class="overflow-x-auto border border-slate-200 rounded-2xl w-full max-w-full">
                            <table class="w-full text-left text-xs min-w-[600px]">
                                <thead class="bg-slate-50 text-slate-700 uppercase font-black text-[10px] border-b border-slate-200">
                                    <tr>
                                        <th class="px-4 py-3">Nama Aplikasi</th>
                                        <th class="px-4 py-3">Client ID</th>
                                        <th class="px-4 py-3">Kondisi SSO</th>
                                        <th class="px-4 py-3 text-center">Skor</th>
                                        <th class="px-4 py-3 text-center">Temuan Masalah</th>
                                        <th class="px-4 py-3 text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="res in batchResults.results" :key="res.application.id">
                                        <tr class="hover:bg-slate-50/80">
                                            <td class="px-4 py-3 font-black text-slate-900" x-text="res.application.name"></td>
                                            <td class="px-4 py-3 font-mono font-bold text-emerald-800" x-text="res.application.client_id"></td>
                                            <td class="px-4 py-3">
                                                <span class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase"
                                                      :class="{
                                                          'bg-emerald-100 text-emerald-900 border border-emerald-300': res.overall_status === 'HEALTHY',
                                                          'bg-amber-100 text-amber-900 border border-amber-300': res.overall_status === 'WARNING',
                                                          'bg-rose-100 text-rose-900 border border-rose-300': res.overall_status === 'CRITICAL'
                                                      }"
                                                      x-text="res.status_text">
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center font-black" x-text="res.health_score + '%'"></td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="px-2 py-0.5 rounded-md font-mono text-[10px] font-bold"
                                                      :class="res.summary.issues_count > 0 ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-600'"
                                                      x-text="res.summary.issues_count + ' Masalah'">
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <button type="button" 
                                                        @click="showBatchModal = false; runSsoDiagnosis(res.application.client_id)" 
                                                        class="px-2.5 py-1 bg-indigo-700 hover:bg-indigo-800 text-white rounded-lg text-[10px] font-bold transition-colors">
                                                    Lihat Solusi
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>
            </div>

            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50 flex justify-end">
                <button type="button" @click="showBatchModal = false" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-extrabold rounded-xl transition-all cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Pulihkan / Restore Database -->
    <div x-show="openRestoreModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
         x-cloak>
        <div @click.away="openRestoreModal = false" 
             x-show="openRestoreModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
             class="bg-white rounded-3xl shadow-2xl max-w-lg w-full border border-slate-200 overflow-hidden relative">
            
            <div class="px-6 py-5 bg-gradient-to-r from-amber-700 to-rose-800 text-white flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-white/10 rounded-xl">
                        <svg class="w-5 h-5 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-black text-base">Konfirmasi Restore Database</h3>
                        <p class="text-[11px] text-amber-200 font-medium">Pemulihan snapshot database SiPintu</p>
                    </div>
                </div>
                <button type="button" @click="openRestoreModal = false" class="p-1 rounded-xl text-white/70 hover:text-white hover:bg-white/10 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form :action="'{{ url('/admin/monitoring/backup/restore') }}/' + encodeURIComponent(selectedRestoreFile)" method="POST" class="p-6 space-y-4" onsubmit="this.querySelector('button[type=submit]').disabled = true; this.querySelector('button[type=submit] span').innerText = 'Memulihkan Database...';">
                @csrf

                <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 text-xs space-y-2">
                    <div class="font-black flex items-center gap-1.5 text-rose-800">
                        <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>PERINGATAN KRITIKAL:</span>
                    </div>
                    <p class="leading-relaxed">
                        Tindakan ini akan menimpa seluruh tabel dan data pada database aktif saat ini dengan data dari file cadangan:
                    </p>
                    <div class="p-2.5 bg-white border border-rose-300 rounded-xl font-mono font-black text-rose-950 text-xs truncate" x-text="selectedRestoreFile"></div>
                    <p class="text-[11px] text-rose-700">
                        Semua perubahan data yang terjadi setelah tanggal backup tersebut akan digantikan. Pastikan tidak ada transaksi penting yang sedang berjalan.
                    </p>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-black text-slate-800">
                        Kata Sandi Administrator untuk Verifikasi <span class="text-rose-600">*</span>
                    </label>
                    <input type="password" name="admin_password" required placeholder="Masukkan kata sandi akun Admin Anda saat ini..."
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-semibold focus:outline-none focus:border-rose-600 focus:bg-white transition-all">
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button type="button" @click="openRestoreModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-rose-700 hover:bg-rose-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-rose-700/20 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-rose-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Pulihkan Database Sekarang</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Tambah Blokir IP Manual -->
    <div x-show="openBlockIpModal" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 sm:p-6"
         x-cloak>
        <div @click.away="openBlockIpModal = false" 
             x-show="openBlockIpModal"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
             class="bg-white rounded-3xl shadow-2xl max-w-lg w-full border border-slate-200 overflow-hidden relative">
            
            <div class="px-6 py-5 bg-gradient-to-r from-rose-800 to-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-white/10 rounded-xl">
                        <svg class="w-5 h-5 text-rose-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    </div>
                    <div>
                        <h3 class="font-black text-base">Tambah Blokir IP Address Manual</h3>
                        <p class="text-[11px] text-rose-200 font-medium">Batasi akses IP yang terindikasi melakukan penyalahgunaan</p>
                    </div>
                </div>
                <button type="button" @click="openBlockIpModal = false" class="p-1 rounded-xl text-white/70 hover:text-white hover:bg-white/10 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('admin.monitoring.blocked-ips.store') }}" method="POST" class="p-6 space-y-4">
                @csrf

                <div class="space-y-1.5">
                    <label class="block text-xs font-black text-slate-800">
                        Alamat IP (IPv4 / IPv6) <span class="text-rose-600">*</span>
                    </label>
                    <input type="text" name="ip_address" required placeholder="Contoh: 192.168.1.50 atau 103.45.67.89"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono text-slate-900 font-bold focus:outline-none focus:border-rose-600 focus:bg-white transition-all">
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-black text-slate-800">
                        Alasan Pemblokiran <span class="text-rose-600">*</span>
                    </label>
                    <textarea name="reason" rows="2" required placeholder="Contoh: Percobaan flooding request, bot scanning, atau request mencurigakan"
                              class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-medium focus:outline-none focus:border-rose-600 focus:bg-white transition-all"></textarea>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-black text-slate-800">
                        Durasi Pemblokiran
                    </label>
                    <select name="duration_hours" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-900 font-semibold focus:outline-none focus:border-rose-600">
                        <option value="1">1 Jam</option>
                        <option value="6">6 Jam</option>
                        <option value="24" selected>24 Jam (1 Hari)</option>
                        <option value="168">7 Hari (1 Minggu)</option>
                        <option value="720">30 Hari (1 Bulan)</option>
                        <option value="0">Permanen (Tanpa batas waktu)</option>
                    </select>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2.5">
                    <button type="button" @click="openBlockIpModal = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition-all">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-rose-700 hover:bg-rose-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-rose-700/20 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-rose-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        <span>Simpan & Blokir IP</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
