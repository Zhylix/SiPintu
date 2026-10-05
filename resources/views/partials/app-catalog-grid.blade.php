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
    $dbCategories = \App\Models\ApplicationCategory::where('is_active', true)->orderBy('display_order')->get();
    $appCategories = $dbCategories->concat($applications->pluck('category')->filter())->unique('id')->values();

    $categoryOptions = collect([
        [
            'id' => 'all',
            'name' => 'Semua Kategori',
            'short_name' => 'Semua',
            'count' => $applications->count(),
            'icon' => 'grid',
        ],
        [
            'id' => 'favorites',
            'name' => 'Favorit Saya',
            'short_name' => 'Favorit',
            'count' => count($favoriteAppIds),
            'icon' => 'star',
        ],
    ])->concat($appCategories->map(function ($cat) use ($applications) {
        return [
            'id' => 'cat_' . $cat->id,
            'category_id' => (string)$cat->id,
            'name' => $cat->name,
            'short_name' => $cat->name,
            'count' => $applications->where('category_id', $cat->id)->count(),
            'icon' => 'tag',
        ];
    }))->values();
@endphp

<div class="space-y-6" x-data="{ 
    selectedFilter: 'all', 
    searchQuery: @js(request('search', '')),
    searchDropdownOpen: false,
    favoriteIds: @js($favoriteAppIds),
    categories: @js($categoryOptions),
    apps: @js($applications->map(fn($a) => [
        'id' => $a->id,
        'name' => $a->name,
        'name_lower' => strtolower($a->name ?? ''),
        'description' => $a->description ?? '',
        'description_lower' => strtolower($a->description ?? ''),
        'category_id' => $a->category_id ? (string)$a->category_id : '',
        'category_name' => $a->category?->name ?? 'Umum',
        'category_slug' => $a->category?->slug ?? '',
        'logo_url' => $a->logo_url,
        'visuals' => $getAppVisuals($a),
        'launch_url' => route('oauth.authorize', [
            'client_id' => $a->client_id,
            'redirect_uri' => $a->redirect_uri,
            'response_type' => 'code',
            'scope' => 'openid profile email',
        ]),
        'favorite_toggle_url' => route('applications.favorite.toggle', $a),
    ])->values()),
    appsMap: {},
    get currentCategory() {
        return this.categories.find(c => c.id === this.selectedFilter) || this.categories[0];
    },
    get currentCategoryShortName() {
        const cat = this.currentCategory;
        return cat ? (cat.short_name || cat.name) : 'Semua';
    },
    getCategoryCount(cat) {
        if (cat.id === 'favorites') {
            return this.favoriteIds.length;
        }
        return cat.count;
    },
    get matchingApps() {
        const q = this.searchQuery.trim().toLowerCase();
        return this.apps.filter(app => {
            const matchesQuery = !q || (
                app.name_lower.includes(q) || 
                app.description_lower.includes(q) || 
                app.category_name.toLowerCase().includes(q) ||
                app.category_slug.includes(q)
            );
            const matchesCategory = (
                this.selectedFilter === 'all' || 
                (this.selectedFilter === 'favorites' && this.favoriteIds.includes(app.id)) ||
                (this.selectedFilter === 'cat_' + app.category_id)
            );
            return matchesCategory && matchesQuery;
        });
    },
    get filteredAppsCount() {
        return this.matchingApps.length;
    },
    isAppVisible(appId) {
        const app = this.appsMap[appId];
        if (!app) return false;
        const q = this.searchQuery.trim().toLowerCase();
        const matchesQuery = !q || (
            app.name_lower.includes(q) || 
            app.description_lower.includes(q) || 
            app.category_name.toLowerCase().includes(q) ||
            app.category_slug.includes(q)
        );
        const matchesCategory = (
            this.selectedFilter === 'all' || 
            (this.selectedFilter === 'favorites' && this.favoriteIds.includes(app.id)) ||
            (this.selectedFilter === 'cat_' + app.category_id)
        );
        return matchesCategory && matchesQuery;
    },
    setCategory(categoryId) {
        this.selectedFilter = categoryId;
        this.searchDropdownOpen = false;
    },
    resetFilters() {
        this.selectedFilter = 'all';
        this.searchQuery = '';
        this.searchDropdownOpen = false;
    },
    init() {
        this.apps.forEach(a => { this.appsMap[a.id] = a; });
        const storedFilter = localStorage.getItem('sipintu_catalog_filter');
        if (storedFilter && (storedFilter === 'all' || storedFilter === 'favorites' || storedFilter.startsWith('cat_'))) {
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
"
@keydown.window="if ($event.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) { $event.preventDefault(); $refs.catalogSearchInput?.focus(); searchDropdownOpen = true; }">
    <!-- Android Material You Style Top Bar (Filter Chips & Search with Category Dropdown) -->
    <div class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3 bg-white border border-slate-200/90 rounded-2xl p-3 sm:p-3.5 shadow-xs">
        <!-- Quick Filter Buttons  -->
        <div class="flex items-center space-x-2 shrink-0 py-0.5 overflow-x-auto no-scrollbar">
            <button @click="setCategory('all')"
                    :class="selectedFilter === 'all' 
                        ? 'bg-emerald-700 text-white shadow-sm shadow-emerald-700/25 font-black ring-1 ring-emerald-700' 
                        : 'bg-slate-100/90 text-slate-700 hover:text-emerald-800 hover:bg-slate-200/80 font-bold border border-slate-200/70'"
                    class="px-4 py-2 rounded-xl text-xs transition-all whitespace-nowrap flex items-center space-x-2 shrink-0 active:scale-95">
                <span>Semua Aplikasi</span>
                <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-black/15 font-mono">{{ $applications->count() }}</span>
            </button>

            <button @click="setCategory('favorites')"
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

            <!-- Active Category Pill if filtered from Search Bar Dropdown -->
            <template x-if="selectedFilter.startsWith('cat_')">
                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-black bg-emerald-50 text-emerald-900 border border-emerald-300 shadow-2xs shrink-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                    <span class="max-w-[130px] truncate" x-text="currentCategoryLabel"></span>
                    <button type="button" @click="setCategory('all')" class="text-slate-400 hover:text-rose-600 p-0.5 rounded transition-colors" title="Hapus filter kategori">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </template>
        </div>

        <!-- Integrated Search Bar with Category Dropdown -->
        <div class="relative w-full lg:w-96 shrink-0" @click.away="searchDropdownOpen = false">
            <div class="flex items-center bg-slate-50 hover:bg-slate-100/80 focus-within:bg-white border border-slate-200/90 focus-within:border-emerald-600 focus-within:ring-2 focus-within:ring-emerald-600/15 rounded-xl transition-all shadow-2xs">
                
                <!-- Category Dropdown Trigger Button inside Search Bar -->
                <button type="button"
                        @click="searchDropdownOpen = !searchDropdownOpen"
                        class="flex items-center gap-1.5 px-3 py-2 text-xs font-bold text-slate-700 hover:text-emerald-800 border-r border-slate-200/80 hover:bg-slate-100/90 transition-colors shrink-0 rounded-l-xl select-none"
                        title="Pilih Kategori Aplikasi">
                    <svg class="w-3.5 h-3.5 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    <span class="max-w-[70px] sm:max-w-[100px] truncate" x-text="currentCategoryShortName">Semua</span>
                    <svg class="w-3 h-3 text-slate-400 transition-transform duration-200 shrink-0" :class="searchDropdownOpen ? 'rotate-180 text-emerald-600' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <!-- Search Input Field -->
                <div class="relative flex-1 min-w-0">
                    <input type="text" 
                           x-ref="catalogSearchInput"
                           x-model="searchQuery" 
                           @focus="searchDropdownOpen = true"
                           @click="searchDropdownOpen = true"
                           @input="searchDropdownOpen = true"
                           @keydown.escape="if (searchDropdownOpen) { searchDropdownOpen = false; } else if (searchQuery.length > 0) { searchQuery = ''; } else { $refs.catalogSearchInput.blur(); }"
                           placeholder="Cari aplikasi (/)"
                           class="w-full pl-2.5 pr-14 py-2 bg-transparent text-xs text-slate-800 placeholder-slate-400 focus:outline-none font-semibold">
                    
                    <div class="absolute right-2 top-1.5 flex items-center space-x-1">
                        <span x-show="searchQuery.trim().length > 0" 
                              x-cloak
                              class="px-1.5 py-0.5 text-[9px] font-black bg-emerald-100 text-emerald-800 rounded-md border border-emerald-200" 
                              x-text="filteredAppsCount"></span>
                        <button x-show="searchQuery.length > 0" 
                                @click="searchQuery = ''; $refs.catalogSearchInput.focus()"
                                type="button"
                                class="text-slate-400 hover:text-slate-700 p-0.5 rounded-md"
                                title="Hapus pencarian">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Floating Category Dropdown Menu -->
            <div x-show="searchDropdownOpen"
                 x-cloak
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 translate-y-1.5 scale-98"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-1.5 scale-98"
                 class="absolute left-0 right-0 sm:left-auto sm:right-0 sm:w-[380px] mt-2 bg-white rounded-2xl shadow-2xl border border-slate-200/90 z-40 overflow-hidden divide-y divide-slate-100">

                <!-- Dropdown Header -->
                <div class="px-3.5 py-2.5 bg-slate-50/90 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
                        <span class="text-xs font-black text-slate-800">Kategori Aplikasi</span>
                    </div>
                    <span class="text-[10px] font-bold text-slate-500" x-text="(categories.length - 2) + ' Kategori Tersedia'"></span>
                </div>

                <!-- Category List -->
                <div class="p-2 max-h-56 overflow-y-auto space-y-1 no-scrollbar">
                    <template x-for="cat in categories" :key="cat.id">
                        <button type="button"
                                @click="setCategory(cat.id)"
                                :class="selectedFilter === cat.id 
                                    ? 'bg-emerald-50 text-emerald-900 font-black border-emerald-300 ring-1 ring-emerald-500/20' 
                                    : 'bg-white hover:bg-slate-50 text-slate-700 border-transparent hover:border-slate-200 font-bold'"
                                class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs border transition-all text-left group active:scale-98">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <template x-if="cat.id === 'all'">
                                    <svg class="w-3.5 h-3.5 text-slate-500 group-hover:text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                                </template>
                                <template x-if="cat.id === 'favorites'">
                                    <svg class="w-3.5 h-3.5 text-amber-500 fill-current shrink-0" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                </template>
                                <template x-if="cat.id !== 'all' && cat.id !== 'favorites'">
                                    <span class="w-2 h-2 rounded-full shrink-0"
                                          :class="selectedFilter === cat.id ? 'bg-emerald-600' : 'bg-slate-300 group-hover:bg-emerald-500'"></span>
                                </template>
                                <span class="truncate" x-text="cat.name"></span>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0 ml-2">
                                <span class="px-1.5 py-0.5 rounded-md text-[10px] font-mono"
                                      :class="selectedFilter === cat.id ? 'bg-emerald-200/80 text-emerald-900 font-bold' : 'bg-slate-100 text-slate-600'"
                                      x-text="getCategoryCount(cat)"></span>
                                <template x-if="selectedFilter === cat.id">
                                    <svg class="w-3.5 h-3.5 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </template>
                            </div>
                        </button>
                    </template>
                </div>

                <!-- Live Matching Apps Preview when user is typing in search query -->
                <div x-show="searchQuery.trim().length > 0" class="p-2.5 bg-slate-50/70 border-t border-slate-100">
                    <div class="flex items-center justify-between px-1 mb-1.5">
                        <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Pratinjau Hasil Pencarian</span>
                        <span class="text-[10px] font-bold text-emerald-800" x-text="filteredAppsCount + ' hasil'"></span>
                    </div>
                    
                    <div class="max-h-40 overflow-y-auto space-y-1 no-scrollbar">
                        <template x-for="app in matchingApps.slice(0, 4)" :key="app.id">
                            <a :href="app.launch_url"
                               class="flex items-center justify-between p-2 rounded-xl bg-white hover:bg-emerald-50/80 border border-slate-200/80 hover:border-emerald-300 transition-all group">
                                <div class="flex items-center gap-2 min-w-0">
                                    <div class="w-6 h-6 rounded-lg shrink-0 flex items-center justify-center overflow-hidden border border-slate-200 text-white font-mono font-bold text-[9px]"
                                         :class="app.logo_url ? 'bg-white' : ('bg-gradient-to-br ' + app.visuals.gradient)">
                                        <template x-if="app.logo_url">
                                            <img :src="app.logo_url" :alt="app.name" class="w-full h-full object-contain p-0.5">
                                        </template>
                                        <template x-if="!app.logo_url">
                                            <span x-text="app.visuals.initials"></span>
                                        </template>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold text-slate-800 group-hover:text-emerald-800 truncate" x-text="app.name"></p>
                                        <p class="text-[10px] text-slate-400 truncate" x-text="app.category_name"></p>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold text-emerald-700 flex items-center gap-0.5 shrink-0 ml-2">
                                    Buka
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </span>
                            </a>
                        </template>
                        <template x-if="filteredAppsCount === 0">
                            <div class="p-2.5 text-center text-xs text-slate-500">
                                Tidak ada aplikasi yang cocok.
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Footer Bar in Dropdown -->
                <div class="px-3.5 py-2 bg-slate-50 flex items-center justify-between text-[11px]">
                    <button type="button"
                            x-show="selectedFilter !== 'all' || searchQuery.trim().length > 0"
                            @click="resetFilters()"
                            class="text-rose-600 hover:text-rose-800 font-bold hover:underline transition-colors">
                        Reset Semua Filter
                    </button>
                    <span class="text-slate-400 font-medium ml-auto">Tekan ESC untuk menutup</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Android App Drawer / Homescreen Collection Grid -->
    <div class="grid grid-cols-2 min-[420px]:grid-cols-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 2xl:grid-cols-7 gap-3 sm:gap-4 md:gap-5">
        @forelse($applications as $app)
            @php
                $visuals = $getAppVisuals($app);
                $hasCustomLogo = !empty($app->logo_url);
            @endphp
            <div x-show="isAppVisible({{ $app->id }})"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 class="relative flex flex-col items-center">
                 
                <!-- Clickable Android App Tile -->
                <a href="{{ route('oauth.authorize', ['client_id' => $app->client_id, 'redirect_uri' => $app->redirect_uri, 'response_type' => 'code', 'scope' => 'openid profile email']) }}"
                   title="{{ $app->name }}{{ $app->category ? ' (' . $app->category->name . ')' : '' }}{{ $app->description ? ' &bull; ' . $app->description : '' }}"
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
                        <span class="text-xs sm:text-sm font-extrabold text-slate-800 group-hover:text-emerald-700 transition-colors line-clamp-2 leading-snug break-words text-center px-1">
                            {{ $app->name }}
                        </span>
                        @if($app->category)
                            <span class="text-[10px] text-slate-400 group-hover:text-emerald-600 font-semibold truncate mt-0.5">
                                {{ $app->category->name }}
                            </span>
                        @endif
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
        <div x-show="filteredAppsCount === 0 && searchQuery.trim() !== ''"
             x-cloak
             class="col-span-full bg-white border border-slate-200/90 rounded-3xl p-8 sm:p-10 text-center text-slate-500 flex flex-col items-center justify-center space-y-3 shadow-xs">
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center shadow-xs">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <div class="space-y-1">
                <p class="text-sm font-black text-slate-800">Aplikasi Tidak Ditemukan</p>
                <p class="text-xs text-slate-500 font-medium max-w-md">Tidak ada aplikasi yang cocok dengan kata kunci &ldquo;<span class="font-bold text-emerald-800 break-all" x-text="searchQuery"></span>&rdquo;</p>
            </div>
            <div class="flex items-center gap-2 pt-1">
                <button type="button" @click="searchQuery = ''; $refs.catalogSearchInput?.focus()" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-bold transition-all shadow-xs">
                    Bersihkan Pencarian
                </button>
                <button type="button" x-show="selectedFilter !== 'all'" @click="selectedFilter = 'all'; searchQuery = ''" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all border border-slate-200">
                    Tampilkan Semua
                </button>
            </div>
        </div>

        <!-- Favorites Empty State -->
        <div x-show="filteredAppsCount === 0 && selectedFilter === 'favorites' && searchQuery.trim() === ''"
             x-cloak
             class="col-span-full bg-white border border-slate-200/90 rounded-3xl p-8 sm:p-10 text-center text-slate-500 flex flex-col items-center justify-center space-y-3 shadow-xs">
            <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-500 flex items-center justify-center shadow-xs">
                <svg class="w-7 h-7 fill-current" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
            </div>
            <div class="space-y-1">
                <p class="text-sm font-black text-slate-800">Belum Ada Aplikasi Favorit</p>
                <p class="text-xs text-slate-500 font-medium">Klik ikon bintang <span class="text-amber-500 font-bold">&#9733;</span> pada kartu aplikasi untuk menyematkannya di sini.</p>
            </div>
            <button type="button" @click="selectedFilter = 'all'" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all border border-slate-200">
                Lihat Semua Aplikasi
            </button>
        </div>

        <!-- Category Empty State -->
        <div x-show="filteredAppsCount === 0 && selectedFilter !== 'all' && selectedFilter !== 'favorites' && searchQuery.trim() === ''"
             x-cloak
             class="col-span-full bg-white border border-slate-200/90 rounded-3xl p-8 sm:p-10 text-center text-slate-500 flex flex-col items-center justify-center space-y-3 shadow-xs">
            <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center shadow-xs">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
            </div>
            <div class="space-y-1">
                <p class="text-sm font-black text-slate-800">Belum Ada Aplikasi di Kategori Ini</p>
                <p class="text-xs text-slate-500 font-medium">Tidak ada aplikasi yang tersedia dalam kategori yang Anda pilih.</p>
            </div>
            <button type="button" @click="selectedFilter = 'all'" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all border border-slate-200">
                Lihat Semua Aplikasi
            </button>
        </div>
    </div>
</div>

