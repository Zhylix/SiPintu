@extends('layouts.app', ['headerTitle' => 'Kelola Alumni Jurusan'])

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-3xl border border-slate-200/80 shadow-xs">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-300">
                    5 Jurusan   
                </span>
                <span class="text-xs font-bold text-slate-500">SMKN 1 Bangsri</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Kelola Alumni Jurusan</h1>
            <p class="text-xs sm:text-sm text-slate-500 font-medium mt-1">
                Pengelompokan data alumni secara otomatis berdasarkan kelas SIJUNA ke 5 konsentrasi keahlian resmi: <strong>PPLG, TO, AKL, PM, dan MPLB</strong>.
            </p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            <form action="{{ route('admin.jurusan.resync') }}" method="POST" onsubmit="return confirm('Jalankan sinkronisasi untuk mengelompokkan ulang seluruh alumni ke 5 jurusan resmi?')">
                @csrf
                <button type="submit" class="px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 active:scale-95 text-white text-xs font-black rounded-xl shadow-md shadow-emerald-700/20 transition-all flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                    <span>Sinkronisasi</span>
                </button>
            </form>
            <a href="{{ route('admin.users.index', ['role' => 'alumni']) }}" class="px-3.5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-200 transition-all flex items-center gap-1.5">
                <span>Kelola Pengguna</span>
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </a>
        </div>
    </div>

    <!-- Alert Sukses -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-start gap-3 text-xs text-emerald-900 font-semibold shadow-xs">
            <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div class="flex-1">
                <span class="font-black text-emerald-950 block">Sinkronisasi Pengelompokan Berhasil!</span>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <!-- 5 Jurusan Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
        @php
            $colorClasses = [
                'PPLG' => [
                    'bg' => 'bg-orange-50/80',
                    'border' => 'border-orange-200',
                    'ring' => 'ring-orange-500',
                    'badge' => 'bg-orange-100 text-orange-950 border-orange-300',
                    'num' => 'text-orange-950',
                    'accent' => 'text-orange-600',
                    'icon_bg' => 'bg-orange-500 text-white',
                    'icon_svg' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>',
                ],
                'AKL' => [
                    'bg' => 'bg-yellow-50/80',
                    'border' => 'border-yellow-200',
                    'ring' => 'ring-yellow-400',
                    'badge' => 'bg-yellow-100 text-yellow-950 border-yellow-300',
                    'num' => 'text-yellow-950',
                    'accent' => 'text-yellow-700',
                    'icon_bg' => 'bg-yellow-400 text-yellow-950',
                    'icon_svg' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>',
                ],
                'PM' => [
                    'bg' => 'bg-rose-50/80',
                    'border' => 'border-rose-200',
                    'ring' => 'ring-rose-500',
                    'badge' => 'bg-rose-100 text-rose-950 border-rose-300',
                    'num' => 'text-rose-950',
                    'accent' => 'text-rose-600',
                    'icon_bg' => 'bg-rose-600 text-white',
                    'icon_svg' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>',
                ],
                'MPLB' => [
                    'bg' => 'bg-sky-50/80',
                    'border' => 'border-sky-200',
                    'ring' => 'ring-sky-400',
                    'badge' => 'bg-sky-100 text-sky-950 border-sky-300',
                    'num' => 'text-sky-950',
                    'accent' => 'text-sky-600',
                    'icon_bg' => 'bg-sky-400 text-white',
                    'icon_svg' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>',
                ],
                'TO' => [
                    'bg' => 'bg-slate-50/90',
                    'border' => 'border-slate-300',
                    'ring' => 'ring-slate-700',
                    'badge' => 'bg-white text-slate-900 border-slate-300 shadow-2xs',
                    'num' => 'text-slate-900',
                    'accent' => 'text-slate-700',
                    'icon_bg' => 'bg-slate-800 text-white border border-slate-700',
                    'icon_svg' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
                ],
            ];
        @endphp

        @foreach($jurusans as $j)
            @php
                $cfg = $colorClasses[$j->kode_jurusan] ?? [
                    'bg' => 'bg-slate-50',
                    'border' => 'border-slate-200',
                    'ring' => 'ring-slate-400',
                    'badge' => 'bg-slate-100 text-slate-800 border-slate-300',
                    'num' => 'text-slate-900',
                    'accent' => 'text-slate-700',
                    'icon_bg' => 'bg-slate-600 text-white',
                    'icon_svg' => '',
                ];
                $percent = $totalAlumni > 0 ? round(($j->alumni_count / $totalAlumni) * 100, 1) : 0;
                $isSelected = ($selectedJurusanKode === $j->kode_jurusan);
            @endphp
            <a href="{{ route('admin.jurusan.index', ['jurusan' => $isSelected ? 'all' : $j->kode_jurusan]) }}" 
               class="p-4 rounded-2xl border transition-all duration-200 relative overflow-hidden group flex flex-col justify-between 
                      {{ $cfg['bg'] }} {{ $cfg['border'] }} {{ $isSelected ? 'ring-2 ' . $cfg['ring'] . ' shadow-md scale-[1.02]' : 'hover:shadow-md hover:scale-[1.01]' }}">
                <div>
                    <div class="flex items-center justify-between gap-1.5 mb-2">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0 shadow-2xs {{ $cfg['icon_bg'] }}">
                                {!! $cfg['icon_svg'] !!}
                            </div>
                            <span class="px-2 py-0.5 rounded-lg text-xs font-black uppercase tracking-wider border {{ $cfg['badge'] }}">
                                {{ $j->kode_jurusan }}
                            </span>
                        </div>
                        <span class="text-[11px] font-black {{ $cfg['accent'] }}">{{ $percent }}%</span>
                    </div>
                    <div class="text-xs font-bold text-slate-700 line-clamp-1 leading-tight" title="{{ $j->nama_jurusan }}">
                        {{ $j->nama_jurusan }}
                    </div>
                </div>

                <div class="mt-3 pt-2.5 border-t border-slate-200/60 flex items-baseline justify-between">
                    <div>
                        <div class="text-2xl font-black {{ $cfg['num'] }} tracking-tight">
                            {{ number_format($j->alumni_count) }}
                        </div>
                        <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500">Alumni Terdata</div>
                    </div>
                    <span class="text-[10px] font-extrabold {{ $cfg['accent'] }} opacity-0 group-hover:opacity-100 transition-opacity flex items-center gap-1">
                        <span>{{ $isSelected ? 'Reset' : 'Filter' }}</span>
                        <span>&rarr;</span>
                    </span>
                </div>
            </a>
        @endforeach
    </div>

    <!-- TABEL 1: Ringkasan Pengelompokan 5 Jurusan Resmi -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="text-base font-black text-slate-900">Tabel Rangkuman Pengelompokan Jurusan</h2>
                <p class="text-xs text-slate-500 font-medium">Distribusi total alumni dan sebaran kelas SIJUNA untuk setiap jurusan</p>
            </div>
            <div class="flex items-center gap-2 text-xs font-bold text-slate-600 bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-200">
                <span>Total Alumni Terkelompokkan:</span>
                <strong class="text-emerald-700 font-black">{{ number_format($totalAlumniWithJurusan) }} / {{ number_format($totalAlumni) }}</strong>
                @if($totalAlumniWithJurusan === $totalAlumni && $totalAlumni > 0)
                    <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-extrabold">100%</span>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/80 text-[11px] font-extrabold text-slate-600 uppercase tracking-wider">
                        <th class="py-3.5 px-5">Kode</th>
                        <th class="py-3.5 px-4">Nama Konsentrasi Keahlian</th>
                        <th class="py-3.5 px-4 text-center">Total Alumni</th>
                        <th class="py-3.5 px-4">Sebaran Rombel Kelas Asal</th>
                        <th class="py-3.5 px-5 text-right">Aksi Filter</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @foreach($jurusans as $jurusan)
                        @php
                            $rooms = $classroomDistribution->get($jurusan->id, collect());
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors {{ $selectedJurusanKode === $jurusan->kode_jurusan ? 'bg-emerald-50/40' : '' }}">
                            <td class="py-4 px-5 whitespace-nowrap">
                                @php
                                    $t1Cfg = $colorClasses[$jurusan->kode_jurusan] ?? [
                                        'badge' => 'bg-slate-100 text-slate-800 border-slate-300',
                                        'icon_bg' => 'bg-slate-600 text-white',
                                        'icon_svg' => '',
                                    ];
                                @endphp
                                <span class="px-2.5 py-1 rounded-lg text-xs font-black uppercase tracking-wider inline-flex items-center gap-2 border {{ $t1Cfg['badge'] }}">
                                    <span class="w-5 h-5 rounded-md flex items-center justify-center shrink-0 {{ $t1Cfg['icon_bg'] }}">
                                        {!! $t1Cfg['icon_svg'] !!}
                                    </span>
                                    <span>{{ $jurusan->kode_jurusan }}</span>
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                <div class="font-bold text-slate-900 text-sm">{{ $jurusan->nama_jurusan }}</div>
                                <div class="text-[11px] text-slate-500 mt-0.5 line-clamp-1">{{ $jurusan->deskripsi }}</div>
                            </td>
                            <td class="py-4 px-4 text-center whitespace-nowrap">
                                <span class="text-base font-black text-slate-900 font-mono">{{ number_format($jurusan->alumni_count) }}</span>
                                <span class="block text-[10px] text-slate-500 font-bold">
                                    {{ $totalAlumni > 0 ? round(($jurusan->alumni_count / $totalAlumni) * 100, 1) : 0 }}% dari total
                                </span>
                            </td>
                            <td class="py-4 px-4">
                                @if($rooms->isNotEmpty())
                                    <div class="flex flex-wrap gap-1.5 max-w-xl">
                                        @foreach($rooms as $r)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-slate-100 hover:bg-slate-200 border border-slate-200 text-slate-800 text-[11px] font-bold rounded-lg transition-colors">
                                                <span>{{ $r->classroom }}</span>
                                                <span class="px-1 py-0.2 rounded bg-white text-emerald-800 text-[9px] font-black border border-slate-200">
                                                    {{ $r->count }}
                                                </span>
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-slate-400 italic text-[11px]">Belum ada kelas terdata</span>
                                @endif
                            </td>
                            <td class="py-4 px-5 text-right whitespace-nowrap">
                                <a href="{{ route('admin.jurusan.index', ['jurusan' => $jurusan->kode_jurusan]) }}" 
                                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold transition-all
                                          {{ $selectedJurusanKode === $jurusan->kode_jurusan ? 'bg-emerald-700 text-white shadow-sm' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200' }}">
                                    <span>{{ $selectedJurusanKode === $jurusan->kode_jurusan ? 'Aktif Difilter' : 'Filter Alumni' }}</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- TABEL 2: Data Rinci Alumni per Jurusan -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden" id="daftar-alumni">
        <div class="p-5 sm:p-6 border-b border-slate-100 space-y-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
                        <span>Daftar Alumni Sesuai Jurusan</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-slate-100 text-slate-700 border border-slate-200">
                            {{ number_format($alumniList->total()) }} Alumni
                        </span>
                    </h2>
                    <p class="text-xs text-slate-500 font-medium mt-0.5">
                        Menampilkan seluruh alumni terdaftar yang telah dikelompokkan sesuai jurusannya
                    </p>
                </div>

                <!-- Search & Year Filter Form -->
                <form method="GET" action="{{ route('admin.jurusan.index') }}" class="flex flex-wrap items-center gap-2 w-full md:w-auto">
                    <input type="hidden" name="jurusan" value="{{ $selectedJurusanKode }}">

                    @if(isset($availableYearsMasuk) && $availableYearsMasuk->isNotEmpty())
                        <select name="tahun_masuk" onchange="this.form.submit()" class="px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all">
                            <option value="">Thn Masuk (Semua)</option>
                            @foreach($availableYearsMasuk as $ym)
                                <option value="{{ $ym }}" {{ (string)$selectedTahunMasuk === (string)$ym ? 'selected' : '' }}>Masuk: {{ $ym }}</option>
                            @endforeach
                        </select>
                    @endif

                    @if(isset($availableYearsLulus) && $availableYearsLulus->isNotEmpty())
                        <select name="tahun_lulus" onchange="this.form.submit()" class="px-2.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-700 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all">
                            <option value="">Thn Lulus (Semua)</option>
                            @foreach($availableYearsLulus as $yl)
                                <option value="{{ $yl }}" {{ (string)$selectedTahunLulus === (string)$yl ? 'selected' : '' }}>Lulus: {{ $yl }}</option>
                            @endforeach
                        </select>
                    @endif

                    <div class="relative flex-1 md:w-60">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, NIS, kelas, HP..." 
                               class="w-full pl-9 pr-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-semibold text-slate-800 placeholder-slate-400 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <button type="submit" class="px-3.5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-xs">
                        Cari
                    </button>
                    @if(!empty($search) || $selectedJurusanKode !== 'all' || !empty($selectedTahunMasuk) || !empty($selectedTahunLulus))
                        <a href="{{ route('admin.jurusan.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-200">
                            Reset
                        </a>
                    @endif
                </form>
            </div>

            <!-- Tab Filter Jurusan Cepat -->
            <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs">
                <span class="text-[11px] font-extrabold uppercase text-slate-500 shrink-0">Filter Jurusan:</span>
                <a href="{{ route('admin.jurusan.index', array_filter(['search' => $search, 'jurusan' => 'all', 'tahun_masuk' => $selectedTahunMasuk, 'tahun_lulus' => $selectedTahunLulus])) }}"
                   class="px-3 py-1.5 rounded-xl font-extrabold transition-all shrink-0
                          {{ $selectedJurusanKode === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200' }}">
                    Semua ({{ number_format($totalAlumni) }})
                </a>
                @foreach($jurusans as $j)
                    @php
                        $tabClasses = match($j->kode_jurusan) {
                            'PPLG' => $selectedJurusanKode === $j->kode_jurusan ? 'bg-orange-600 text-white shadow-xs' : 'bg-orange-50/80 text-orange-950 hover:bg-orange-100 border border-orange-200',
                            'AKL' => $selectedJurusanKode === $j->kode_jurusan ? 'bg-yellow-500 text-slate-950 shadow-xs' : 'bg-yellow-50/80 text-yellow-950 hover:bg-yellow-100 border border-yellow-300',
                            'PM' => $selectedJurusanKode === $j->kode_jurusan ? 'bg-rose-600 text-white shadow-xs' : 'bg-rose-50/80 text-rose-950 hover:bg-rose-100 border border-rose-200',
                            'MPLB' => $selectedJurusanKode === $j->kode_jurusan ? 'bg-sky-500 text-white shadow-xs' : 'bg-sky-50/80 text-sky-950 hover:bg-sky-100 border border-sky-200',
                            'TO' => $selectedJurusanKode === $j->kode_jurusan ? 'bg-slate-800 text-white shadow-xs' : 'bg-slate-100 text-slate-800 hover:bg-slate-200 border border-slate-300',
                            default => $selectedJurusanKode === $j->kode_jurusan ? 'bg-emerald-700 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 border border-slate-200',
                        };
                        $tabIconCfg = $colorClasses[$j->kode_jurusan] ?? null;
                    @endphp
                    <a href="{{ route('admin.jurusan.index', array_filter(['search' => $search, 'jurusan' => $j->kode_jurusan, 'tahun_masuk' => $selectedTahunMasuk, 'tahun_lulus' => $selectedTahunLulus])) }}"
                       class="px-3 py-1.5 rounded-xl font-extrabold transition-all shrink-0 flex items-center gap-1.5 border {{ $tabClasses }}">
                        @if($tabIconCfg)
                            <span class="w-4 h-4 rounded flex items-center justify-center shrink-0 {{ $selectedJurusanKode === $j->kode_jurusan ? 'text-white' : $tabIconCfg['icon_bg'] }}">
                                {!! $tabIconCfg['icon_svg'] !!}
                            </span>
                        @endif
                        <span>{{ $j->kode_jurusan }}</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-black {{ $selectedJurusanKode === $j->kode_jurusan ? 'bg-black/20 text-white' : 'bg-white text-slate-800 border border-slate-200' }}">
                            {{ number_format($j->alumni_count) }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-slate-200/80 bg-slate-50/80 text-[11px] font-extrabold text-slate-600 uppercase tracking-wider">
                        <th class="py-3.5 px-5">Data Alumni</th>
                        <th class="py-3.5 px-4">Jurusan</th>
                        <th class="py-3.5 px-4">Kelas Asal</th>
                        <th class="py-3.5 px-4">NIS</th>
                        <th class="py-3.5 px-4 text-center">Tahun Masuk</th>
                        <th class="py-3.5 px-4 text-center">Tahun Lulus</th>
                        <th class="py-3.5 px-4">Nomor WhatsApp</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700 bg-white">
                    @forelse($alumniList as $user)
                        <tr class="hover:bg-emerald-50/30 transition-colors">
                            <!-- Profil Alumni -->
                            <td class="py-3.5 px-5 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    @if($user->avatar_url)
                                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-9 h-9 rounded-full object-cover border border-emerald-300 shrink-0">
                                    @else
                                        <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 flex items-center justify-center font-extrabold text-xs shrink-0">
                                            {{ $user->initials() }}
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-bold text-slate-900 text-sm">{{ $user->name }}</div>
                                        <div class="text-slate-500 text-[11px] font-medium">{{ $user->email }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Jurusan Badge -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($user->jurusan)
                                    @php
                                        $t2Cfg = $colorClasses[$user->jurusan->kode_jurusan] ?? [
                                            'badge' => 'bg-slate-100 text-slate-800 border-slate-300',
                                            'icon_bg' => 'bg-slate-600 text-white',
                                            'icon_svg' => '',
                                        ];
                                    @endphp
                                    <span class="px-2.5 py-1 rounded-lg text-xs font-black uppercase tracking-wider inline-flex items-center gap-1.5 border {{ $t2Cfg['badge'] }}">
                                        <span class="w-4 h-4 rounded flex items-center justify-center shrink-0 {{ $t2Cfg['icon_bg'] }}">
                                            {!! $t2Cfg['icon_svg'] !!}
                                        </span>
                                        <span>{{ $user->jurusan->kode_jurusan }}</span>
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded text-[10px] bg-slate-100 text-slate-500 border border-slate-200">
                                        Belum Ditentukan
                                    </span>
                                @endif
                            </td>

                            <!-- Kelas Asal -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                @if($user->classroom)
                                    <span class="px-2 py-0.5 bg-slate-100 text-slate-800 border border-slate-200 rounded text-[11px] font-bold font-mono">
                                        {{ $user->classroom }}
                                    </span>
                                @else
                                    <span class="text-slate-300 italic">-</span>
                                @endif
                            </td>

                            <!-- NIS -->
                            <td class="py-3.5 px-4 whitespace-nowrap font-mono text-xs">
                                @if($user->nis)
                                    <span class="text-slate-900 font-bold">{{ $user->nis }}</span>
                                @else
                                    <span class="text-slate-300 italic">-</span>
                                @endif
                            </td>

                            <!-- Tahun Masuk (dibuat siswa / created_at) -->
                            <td class="py-3.5 px-4 whitespace-nowrap text-center">
                                @if($user->tahun_masuk)
                                    <div class="inline-flex flex-col items-center" title="Terdaftar Masuk: {{ $user->created_at?->translatedFormat('d F Y H:i') }}">
                                        <span class="px-2.5 py-0.5 rounded-md text-xs font-black bg-emerald-50 text-emerald-800 border border-emerald-200 font-mono">
                                            {{ $user->tahun_masuk }}
                                        </span>
                                        <span class="text-[9px] text-slate-400 font-medium mt-0.5">{{ $user->created_at?->format('d/m/Y') }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-300 italic">-</span>
                                @endif
                            </td>

                            <!-- Tahun Lulus (diperbarui status lulus / updated_at) -->
                            <td class="py-3.5 px-4 whitespace-nowrap text-center">
                                @if($user->tahun_lulus)
                                    <div class="inline-flex flex-col items-center" title="Pembaruan Lulus: {{ $user->updated_at?->translatedFormat('d F Y H:i') }}">
                                        <span class="px-2.5 py-0.5 rounded-md text-xs font-black bg-teal-50 text-teal-800 border border-teal-200 font-mono">
                                            {{ $user->tahun_lulus }}
                                        </span>
                                        <span class="text-[9px] text-slate-400 font-medium mt-0.5">{{ $user->updated_at?->format('d/m/Y') }}</span>
                                    </div>
                                @else
                                    <span class="text-slate-300 italic">-</span>
                                @endif
                            </td>

                            <!-- WhatsApp -->
                            <td class="py-3.5 px-4 whitespace-nowrap font-mono text-xs">
                                @if($user->phone)
                                    <span class="text-slate-700 font-semibold">{{ $user->phone }}</span>
                                @else
                                    <span class="text-slate-300 italic">-</span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase
                                    {{ $user->status === 'active' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                    {{ $user->status === 'active' ? 'Aktif' : $user->status }}
                                </span>
                            </td>

                            <!-- Aksi -->
                            <td class="py-3.5 px-5 text-right whitespace-nowrap">
                                <a href="{{ route('admin.users.edit', $user) }}" 
                                   class="px-2.5 py-1 text-[11px] font-bold text-slate-600 hover:text-emerald-700 hover:bg-emerald-50 rounded-lg border border-slate-200 transition-colors">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400">
                                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                </div>
                                <div class="font-bold text-slate-700 text-sm">Tidak Ada Data Alumni</div>
                                <p class="text-xs text-slate-400 mt-1">Tidak ada alumni yang sesuai dengan filter atau kata kunci pencarian.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($alumniList->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $alumniList->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
