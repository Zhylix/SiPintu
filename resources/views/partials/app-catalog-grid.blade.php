@php
    $getAppVisuals = function ($app) {
        $name = strtolower($app->name ?? '');
        
        if (str_contains($name, 'cbt') || str_contains($name, 'ujian') || str_contains($name, 'test')) {
            return [
                'gradient' => 'from-emerald-500 via-teal-600 to-emerald-700',
                'shadow' => 'shadow-emerald-600/30',
                'icon' => 'academic',
                'initials' => 'CBT',
            ];
        }
        if (str_contains($name, 'pkl') || str_contains($name, 'magang') || str_contains($name, 'dudi')) {
            return [
                'gradient' => 'from-sky-500 via-blue-600 to-indigo-700',
                'shadow' => 'shadow-blue-600/30',
                'icon' => 'briefcase',
                'initials' => 'PKL',
            ];
        }
        if (str_contains($name, 'market') || str_contains($name, 'toko') || str_contains($name, 'eska')) {
            return [
                'gradient' => 'from-indigo-500 via-purple-600 to-violet-800',
                'shadow' => 'shadow-indigo-600/30',
                'icon' => 'shopping-bag',
                'initials' => 'ESK',
            ];
        }
        if (str_contains($name, 'inven') || str_contains($name, 'aset') || str_contains($name, 'barang')) {
            return [
                'gradient' => 'from-amber-500 via-orange-600 to-amber-700',
                'shadow' => 'shadow-amber-600/30',
                'icon' => 'archive',
                'initials' => 'INV',
            ];
        }
        if (str_contains($name, 'sehat') || str_contains($name, 'sikes') || str_contains($name, 'uks')) {
            return [
                'gradient' => 'from-rose-500 via-pink-600 to-red-600',
                'shadow' => 'shadow-rose-600/30',
                'icon' => 'heart',
                'initials' => 'UKS',
            ];
        }
        if (str_contains($name, 'api') || str_contains($name, 'dev') || str_contains($name, 'gateway')) {
            return [
                'gradient' => 'from-teal-500 via-cyan-600 to-blue-700',
                'shadow' => 'shadow-teal-600/30',
                'icon' => 'code',
                'initials' => 'API',
            ];
        }

        $palettes = [
            ['gradient' => 'from-emerald-600 via-teal-600 to-teal-700', 'shadow' => 'shadow-emerald-600/30', 'icon' => 'app'],
            ['gradient' => 'from-blue-600 via-indigo-600 to-indigo-700', 'shadow' => 'shadow-blue-600/30', 'icon' => 'app'],
            ['gradient' => 'from-purple-600 via-violet-600 to-violet-800', 'shadow' => 'shadow-purple-600/30', 'icon' => 'app'],
            ['gradient' => 'from-amber-600 via-orange-600 to-rose-600', 'shadow' => 'shadow-amber-600/30', 'icon' => 'app'],
            ['gradient' => 'from-rose-600 via-pink-600 to-pink-700', 'shadow' => 'shadow-rose-600/30', 'icon' => 'app'],
            ['gradient' => 'from-cyan-600 via-teal-600 to-emerald-700', 'shadow' => 'shadow-cyan-600/30', 'icon' => 'app'],
        ];

        $res = $palettes[($app->id ?? 0) % count($palettes)];
        $words = preg_split('/\s+/', trim($app->name ?? 'AP'));
        $initials = '';
        foreach (array_slice($words, 0, 2) as $w) {
            $initials .= strtoupper(substr($w, 0, 1));
        }
        $res['initials'] = $initials ?: 'AP';
        return $res;
    };
@endphp

<div class="space-y-6" x-data="{ 
    selectedFilter: 'all', 
    searchQuery: '',
    favoriteIds: @js($favoriteAppIds),
    apps: @js($applications->map(fn($a) => ['id' => $a->id, 'name' => strtolower($a->name)])),
    get filteredAppsCount() {
        const q = this.searchQuery.trim().toLowerCase();
        return this.apps.filter(app => {
            const matchesFilter = (this.selectedFilter === 'all' || this.favoriteIds.includes(app.id));
            const matchesQuery = (!q || app.name.includes(q));
            return matchesFilter && matchesQuery;
        }).length;
    },
    init() {
        const storedFilter = localStorage.getItem('sipintu_catalog_filter');
        if (storedFilter) {
            this.selectedFilter = storedFilter;
        }
        this.$watch('selectedFilter', val => localStorage.setItem('sipintu_catalog_filter', val));
    },
    async toggleFavorite(appId, url) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            const data = await response.json();
            if (data.success) {
                if (data.is_favorited) {
                    if (!this.favoriteIds.includes(appId)) {
                        this.favoriteIds.push(appId);
                    }
                } else {
                    this.favoriteIds = this.favoriteIds.filter(id => id !== appId);
                }
                window.dispatchEvent(new CustomEvent('favorite-updated', { detail: { appId, is_favorited: data.is_favorited, message: data.message, application: data.application } }));
            }
        } catch (e) {
            console.error('Gagal memperbarui status favorit:', e);
        }
    }
}"
x-on:favorite-updated.window="
    if ($event.detail.is_favorited) {
        if (!favoriteIds.includes($event.detail.appId)) favoriteIds.push($event.detail.appId);
    } else {
        favoriteIds = favoriteIds.filter(id => id !== $event.detail.appId);
    }
">
    <!-- Android Material You Style Top Bar (Filter Chips & Search) -->
    <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3 bg-white border border-slate-200/90 rounded-2xl p-3 sm:p-3.5 shadow-xs">
        <!-- Material Filter Chips -->
        <div class="flex items-center space-x-2 overflow-x-auto no-scrollbar py-0.5">
            <button @click="selectedFilter = 'all'"
                    :class="selectedFilter === 'all' 
                        ? 'bg-emerald-700 text-white shadow-sm shadow-emerald-700/25 font-black ring-1 ring-emerald-700' 
                        : 'bg-slate-100/90 text-slate-700 hover:text-emerald-800 hover:bg-slate-200/80 font-bold border border-slate-200/70'"
                    class="px-4 py-2 rounded-xl text-xs transition-all whitespace-nowrap flex items-center space-x-2 shrink-0 active:scale-95">
                <span>Semua Aplikasi</span>
                <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-black/15 font-mono">{{ $applications->count() }}</span>
            </button>

            <button @click="selectedFilter = 'favorites'"
                    :class="selectedFilter === 'favorites' 
                        ? 'bg-amber-500 text-white shadow-sm shadow-amber-500/25 font-black ring-1 ring-amber-500' 
                        : 'bg-slate-100/90 text-slate-700 hover:text-amber-700 hover:bg-amber-50 font-bold border border-slate-200/70'"
                    class="px-4 py-2 rounded-xl text-xs transition-all whitespace-nowrap flex items-center space-x-2 shrink-0 active:scale-95">
                <svg class="w-3.5 h-3.5 fill-current shrink-0" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                <span>Favorit Saya</span>
                <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-black/15 font-mono" x-text="favoriteIds.length"></span>
            </button>
        </div>

        <!-- Material Search Pill -->
        <div class="relative w-full md:w-72 shrink-0">
            <input type="text" x-model="searchQuery" placeholder="Cari aplikasi..."
                   class="w-full pl-9 pr-8 py-2 bg-slate-50 border border-slate-200/90 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15 transition-all font-semibold">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <button x-show="searchQuery.length > 0" 
                    @click="searchQuery = ''"
                    type="button"
                    class="absolute right-2.5 top-2 text-slate-400 hover:text-slate-600 p-0.5 rounded-md">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
    </div>

    <!-- Android App Drawer / Homescreen Collection Grid -->
    <div class="grid grid-cols-2 min-[420px]:grid-cols-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-7 gap-3 sm:gap-4 md:gap-5">
        @forelse($applications as $app)
            @php
                $visuals = $getAppVisuals($app);
                $hasCustomLogo = !empty($app->logo_url);
            @endphp
            <div x-show="(selectedFilter === 'all' || (selectedFilter === 'favorites' && favoriteIds.includes({{ $app->id }}))) && (searchQuery.trim() === '' || @js(strtolower($app->name)).includes(searchQuery.trim().toLowerCase()))"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="relative flex flex-col items-center">
                 
                <!-- Clickable Android App Tile -->
                <a href="{{ route('oauth.authorize', ['client_id' => $app->client_id, 'redirect_uri' => $app->redirect_uri, 'response_type' => 'code', 'scope' => 'openid profile email']) }}"
                   title="{{ $app->name }}{{ $app->description ? ' &bull; ' . $app->description : '' }}"
                   class="group w-full h-full flex flex-col items-center justify-start p-3.5 sm:p-5 rounded-2xl sm:rounded-3xl bg-white border border-slate-200/90 hover:border-emerald-500 shadow-2xs hover:shadow-xl hover:shadow-emerald-900/10 transition-all duration-200 hover:-translate-y-1.5 active:scale-95 text-center cursor-pointer select-none">
                    
                    <!-- Android Squircle App Icon Container -->
                    <div class="relative w-16 h-16 sm:w-18 sm:h-18 lg:w-20 lg:h-20 shrink-0">
                        @if($hasCustomLogo)
                            <div class="w-full h-full rounded-[22px] sm:rounded-[26px] lg:rounded-[28px] bg-white border border-slate-200 shadow-xs p-2.5 flex items-center justify-center group-hover:scale-105 group-hover:shadow-md transition-all duration-200">
                                <img src="{{ $app->logo_url }}" alt="{{ $app->name }}" class="w-full h-full object-contain rounded-xl">
                            </div>
                        @else
                            <div class="w-full h-full rounded-[22px] sm:rounded-[26px] lg:rounded-[28px] bg-gradient-to-br {{ $visuals['gradient'] }} text-white flex flex-col items-center justify-center {{ $visuals['shadow'] }} shadow-md group-hover:scale-105 group-hover:shadow-lg transition-all duration-200 ring-2 ring-white/40">
                                @if($visuals['icon'] === 'academic')
                                    <svg class="w-7 h-7 sm:w-8 sm:h-8 drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
                                    </svg>
                                @elseif($visuals['icon'] === 'briefcase')
                                    <svg class="w-7 h-7 sm:w-8 sm:h-8 drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                @elseif($visuals['icon'] === 'shopping-bag')
                                    <svg class="w-7 h-7 sm:w-8 sm:h-8 drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                                    </svg>
                                @elseif($visuals['icon'] === 'archive')
                                    <svg class="w-7 h-7 sm:w-8 sm:h-8 drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                @elseif($visuals['icon'] === 'heart')
                                    <svg class="w-7 h-7 sm:w-8 sm:h-8 drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                    </svg>
                                @elseif($visuals['icon'] === 'code')
                                    <svg class="w-7 h-7 sm:w-8 sm:h-8 drop-shadow-sm" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                                    </svg>
                                @else
                                    <span class="font-black text-lg sm:text-xl drop-shadow-sm tracking-wider font-mono">
                                        {{ $visuals['initials'] }}
                                    </span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Android App Label (Centered Below Icon) -->
                    <div class="mt-3 sm:mt-3.5 w-full flex-1 flex flex-col justify-start min-h-[2.5rem]">
                        <span class="text-xs sm:text-sm font-extrabold text-slate-800 group-hover:text-emerald-700 transition-colors line-clamp-3 leading-snug break-words text-center px-1">
                            {{ $app->name }}
                        </span>
                    </div>
                </a>

                <!-- Android Style Favorite Star Button (Pinned in Tile Corner) -->
                <button type="button"
                        @click.stop.prevent="toggleFavorite({{ $app->id }}, '{{ route('applications.favorite.toggle', $app) }}')"
                        :title="favoriteIds.includes({{ $app->id }}) ? 'Hapus dari favorit' : 'Tambah ke favorit'"
                        class="absolute top-2 right-2 sm:top-2.5 sm:right-2.5 p-1.5 rounded-full transition-all duration-200 z-10"
                        :class="favoriteIds.includes({{ $app->id }}) 
                            ? 'text-amber-500 bg-amber-50/90 border border-amber-200 shadow-2xs scale-105' 
                            : 'text-slate-300 hover:text-amber-500 hover:bg-slate-100/90 opacity-70 sm:opacity-0 group-hover:opacity-100 hover:opacity-100'">
                    <svg class="w-4 h-4 transition-all duration-150" 
                         :class="favoriteIds.includes({{ $app->id }}) ? 'fill-amber-400 text-amber-500' : 'fill-none'" 
                         stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                </button>
            </div>
        @empty
            <div class="col-span-full bg-white border border-slate-200/90 rounded-3xl p-8 text-center text-slate-500 flex flex-col items-center justify-center space-y-2">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                </div>
                <p class="text-xs font-bold text-slate-700">Belum Ada Aplikasi Terdaftar</p>
                <p class="text-[11px] text-slate-400 font-medium">Tidak ada aplikasi downstream yang diizinkan untuk peran akun Anda saat ini.</p>
            </div>
        @endforelse

        <!-- Search Empty State (No Matches) -->
        <div x-show="filteredAppsCount === 0 && selectedFilter !== 'favorites' && searchQuery.trim() !== ''"
             x-cloak
             class="col-span-full bg-white border border-slate-200/90 rounded-3xl p-8 sm:p-10 text-center text-slate-500 flex flex-col items-center justify-center space-y-2 shadow-xs">
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center mb-1">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <p class="text-sm font-black text-slate-800">Aplikasi Tidak Ditemukan</p>
            <p class="text-xs text-slate-500 font-medium">Tidak ada aplikasi yang cocok dengan kata kunci &ldquo;<span class="font-bold text-emerald-800" x-text="searchQuery"></span>&rdquo;</p>
            <button type="button" @click="searchQuery = ''" class="mt-2 px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-bold transition-all shadow-xs">
                Bersihkan Pencarian
            </button>
        </div>

        <!-- Favorites Empty State -->
        <div x-show="selectedFilter === 'favorites' && filteredAppsCount === 0"
             x-cloak
             class="col-span-full bg-white border border-slate-200/90 rounded-3xl p-8 sm:p-10 text-center text-slate-500 flex flex-col items-center justify-center space-y-2 shadow-xs">
            <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center mb-1">
                <svg class="w-7 h-7 fill-current" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
            </div>
            <p class="text-sm font-black text-slate-800">Belum Ada Aplikasi Favorit</p>
            <p class="text-xs text-slate-500 font-medium">Klik ikon bintang <span class="text-amber-500 font-bold">&#9733;</span> pada kartu aplikasi untuk menyematkannya di sini.</p>
            <button type="button" @click="selectedFilter = 'all'" class="mt-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all border border-slate-200">
                Lihat Semua Aplikasi
            </button>
        </div>
    </div>
</div>

