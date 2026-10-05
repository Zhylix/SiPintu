@php
    $modalApps = $accessibleApps ?? collect();
    $modalFavoriteIds = $favoriteAppIds ?? [];
    $dbCategories = \App\Models\ApplicationCategory::where('is_active', true)->orderBy('display_order')->get();
    $modalCategories = $dbCategories->concat($modalApps->pluck('category')->filter())->unique('id')->values();

    $getModalAppVisuals = function ($app) {
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

    $appsJson = $modalApps->map(function ($a) use ($getModalAppVisuals) {
        $visuals = $getModalAppVisuals($a);
        return [
            'id' => $a->id,
            'name' => $a->name,
            'description' => $a->description ?? '',
            'category_id' => $a->category_id ? (string)$a->category_id : '',
            'category_name' => $a->category?->name ?? 'Umum',
            'logo_url' => $a->logo_url,
            'visuals' => $visuals,
            'launch_url' => route('oauth.authorize', [
                'client_id' => $a->client_id,
                'redirect_uri' => $a->redirect_uri,
                'response_type' => 'code',
                'scope' => 'openid profile email',
            ]),
            'favorite_toggle_url' => route('applications.favorite.toggle', $a),
        ];
    })->values();
@endphp

<div x-data="{
    isOpen: false,
    query: '',
    selectedCategory: 'all',
    selectedIndex: 0,
    categoryDropdownOpen: false,
    favoriteIds: @js($modalFavoriteIds),
    categories: @js(collect([
        ['id' => 'all', 'name' => 'Semua Kategori', 'short_name' => 'Semua', 'count' => count($modalApps)],
        ['id' => 'favorites', 'name' => 'Favorit Saya', 'short_name' => 'Favorit', 'count' => count($modalFavoriteIds)],
    ])->concat($modalCategories->map(fn($c) => [
        'id' => 'cat_' . $c->id,
        'name' => $c->name,
        'short_name' => $c->name,
        'count' => $modalApps->where('category_id', $c->id)->count(),
    ]))->values()),
    apps: @js($appsJson),
    get currentCategoryLabel() {
        const found = this.categories.find(c => c.id === this.selectedCategory);
        return found ? (found.short_name || found.name) : 'Semua';
    },
    getModalCategoryCount(cat) {
        if (cat.id === 'favorites') {
            return this.favoriteIds.length;
        }
        return cat.count;
    },
    setCategory(catId) {
        this.selectedCategory = catId;
        this.categoryDropdownOpen = false;
        this.selectedIndex = 0;
    },
    get filteredApps() {
        const q = this.query.trim().toLowerCase();
        return this.apps.filter(app => {
            const matchesQuery = !q || (
                app.name.toLowerCase().includes(q) ||
                app.description.toLowerCase().includes(q) ||
                app.category_name.toLowerCase().includes(q)
            );
            const matchesCategory = (
                this.selectedCategory === 'all' ||
                (this.selectedCategory === 'favorites' && this.favoriteIds.includes(app.id)) ||
                (this.selectedCategory === 'cat_' + app.category_id)
            );
            return matchesQuery && matchesCategory;
        });
    },
    open() {
        this.isOpen = true;
        this.selectedIndex = 0;
        this.categoryDropdownOpen = false;
        this.$nextTick(() => {
            this.$refs.globalSearchInput?.focus();
            this.$refs.globalSearchInput?.select();
        });
    },
    close() {
        this.isOpen = false;
        this.query = '';
        this.selectedCategory = 'all';
        this.categoryDropdownOpen = false;
    },
    navigateDown() {
        const count = this.filteredApps.length;
        if (count > 0) {
            this.selectedIndex = (this.selectedIndex + 1) % count;
            this.scrollToSelected();
        }
    },
    navigateUp() {
        const count = this.filteredApps.length;
        if (count > 0) {
            this.selectedIndex = (this.selectedIndex - 1 + count) % count;
            this.scrollToSelected();
        }
    },
    selectCurrent() {
        const list = this.filteredApps;
        if (list.length > 0 && list[this.selectedIndex]) {
            window.location.href = list[this.selectedIndex].launch_url;
        }
    },
    scrollToSelected() {
        this.$nextTick(() => {
            const activeElem = document.getElementById('global-search-item-' + this.selectedIndex);
            if (activeElem) {
                activeElem.scrollIntoView({ block: 'nearest' });
            }
        });
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
                window.dispatchEvent(new CustomEvent('favorite-updated', { 
                    detail: { appId, is_favorited: data.is_favorited, message: data.message, application: data.application } 
                }));
            }
        } catch (e) {
            console.error('Gagal memperbarui status favorit:', e);
        }
    },
    init() {
        this.$watch('query', () => { this.selectedIndex = 0; });
        this.$watch('selectedCategory', () => { this.selectedIndex = 0; });
    }
}"
x-on:open-global-search.window="open()"
x-on:favorite-updated.window="
    if ($event.detail.is_favorited) {
        if (!favoriteIds.includes($event.detail.appId)) favoriteIds.push($event.detail.appId);
    } else {
        favoriteIds = favoriteIds.filter(id => id !== $event.detail.appId);
    }
"
@keydown.window.ctrl.k.prevent="open()"
@keydown.window.meta.k.prevent="open()">

    <!-- Backdrop & Modal Container -->
    <div x-show="isOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-start justify-center pt-8 sm:pt-20 px-3 sm:px-4"
         @keydown.escape.window="close()">

        <!-- Dialog Box -->
        <div @click.away="close()"
             x-show="isOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 scale-95"
             class="w-full max-w-2xl bg-white rounded-3xl shadow-2xl border border-slate-200/90 overflow-hidden flex flex-col max-h-[82vh] divide-y divide-slate-100">

            <!-- Search Header Bar -->
            <div class="p-3.5 sm:p-4 bg-white flex items-center gap-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center shrink-0 border border-emerald-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>

                <div class="flex-1 min-w-0">
                    <input type="text"
                           x-ref="globalSearchInput"
                           x-model="query"
                           @keydown.down.prevent="navigateDown()"
                           @keydown.up.prevent="navigateUp()"
                           @keydown.enter.prevent="selectCurrent()"
                           placeholder="Cari nama aplikasi, layanan, atau kategori..."
                           class="w-full bg-transparent text-sm sm:text-base font-extrabold text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-0 border-0 p-0">
                </div>

                <div class="flex items-center gap-1.5 shrink-0">
                    <button x-show="query.length > 0"
                            @click="query = ''; $refs.globalSearchInput.focus()"
                            type="button"
                            class="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg hover:bg-slate-100 transition-colors"
                            title="Hapus pencarian">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <button type="button" 
                            @click="close()" 
                            class="px-2 py-1 rounded-lg text-[10px] font-mono font-bold bg-slate-100 hover:bg-slate-200 text-slate-600 border border-slate-200 transition-colors">
                        ESC
                    </button>
                </div>
            </div>

            <!-- Category Filter Bar with Category Dropdown & Chips -->
            <div class="px-3.5 sm:px-4 py-2 bg-slate-50/70 border-b border-slate-100 flex items-center space-x-2 overflow-x-auto no-scrollbar">
                <!-- Category Dropdown Button & Popover -->
                <div class="relative shrink-0" @click.away="categoryDropdownOpen = false">
                    <button type="button"
                            @click="categoryDropdownOpen = !categoryDropdownOpen"
                            class="flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold border transition-all shrink-0 select-none shadow-2xs"
                            :class="categoryDropdownOpen ? 'bg-emerald-700 text-white border-emerald-700' : 'bg-white text-slate-700 hover:bg-slate-100 border-slate-200/90'">
                        <svg class="w-3.5 h-3.5" :class="categoryDropdownOpen ? 'text-white' : 'text-emerald-700'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        <span class="max-w-[120px] truncate" x-text="'Kategori: ' + currentCategoryLabel">Kategori</span>
                        <svg class="w-3 h-3 text-slate-400 transition-transform" :class="categoryDropdownOpen ? 'rotate-180 text-white' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>

                    <!-- Dropdown Popover -->
                    <div x-show="categoryDropdownOpen"
                         x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-1 scale-98"
                         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                         x-transition:leave-end="opacity-0 translate-y-1 scale-98"
                         class="absolute left-0 mt-2 w-64 bg-white rounded-2xl shadow-xl border border-slate-200/90 z-50 p-2 space-y-1 max-h-56 overflow-y-auto no-scrollbar">
                        <template x-for="cat in categories" :key="cat.id">
                            <button type="button"
                                    @click="setCategory(cat.id)"
                                    :class="selectedCategory === cat.id 
                                        ? 'bg-emerald-50 text-emerald-900 font-black border-emerald-300 ring-1 ring-emerald-500/20' 
                                        : 'bg-white hover:bg-slate-50 text-slate-700 border-transparent hover:border-slate-200 font-bold'"
                                    class="w-full flex items-center justify-between px-2.5 py-1.5 rounded-xl text-xs border transition-all text-left group">
                                <span class="truncate" x-text="cat.name"></span>
                                <span class="px-1.5 py-0.5 rounded-md text-[10px] font-mono shrink-0 ml-1.5"
                                      :class="selectedCategory === cat.id ? 'bg-emerald-200/80 text-emerald-900 font-bold' : 'bg-slate-100 text-slate-600'"
                                      x-text="getModalCategoryCount(cat)"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div class="h-4 w-px bg-slate-200 shrink-0"></div>

                <!-- Chips -->
                <button type="button"
                        @click="setCategory('all')"
                        :class="selectedCategory === 'all' 
                            ? 'bg-emerald-700 text-white font-extrabold shadow-2xs' 
                            : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80 font-bold'"
                        class="px-3 py-1 rounded-xl text-xs transition-all shrink-0">
                    Semua ({{ count($modalApps) }})
                </button>

                <button type="button"
                        @click="setCategory('favorites')"
                        :class="selectedCategory === 'favorites' 
                            ? 'bg-amber-500 text-white font-extrabold shadow-2xs' 
                            : 'bg-white text-slate-600 hover:bg-amber-50 hover:text-amber-700 border border-slate-200/80 font-bold'"
                        class="px-3 py-1 rounded-xl text-xs transition-all shrink-0 flex items-center gap-1">
                    <svg class="w-3 h-3 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    <span>Favorit</span>
                    <span class="px-1 py-0.2 text-[9px] rounded bg-black/10 font-mono" x-text="favoriteIds.length"></span>
                </button>

                @foreach($modalCategories as $cat)
                    <button type="button"
                            @click="setCategory('cat_{{ $cat->id }}')"
                            :class="selectedCategory === 'cat_{{ $cat->id }}' 
                                ? 'bg-emerald-700 text-white font-extrabold shadow-2xs' 
                                : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200/80 font-bold'"
                            class="px-3 py-1 rounded-xl text-xs transition-all shrink-0">
                        {{ $cat->name }}
                    </button>
                @endforeach
            </div>

            <!-- Results List / Scroll Area -->
            <div class="flex-1 overflow-y-auto divide-y divide-slate-100 p-2 sm:p-3 min-h-[220px] max-h-[50vh]">
                <template x-for="(app, index) in filteredApps" :key="app.id">
                    <div :id="'global-search-item-' + index"
                         @click="window.location.href = app.launch_url"
                         @mouseenter="selectedIndex = index"
                         :class="selectedIndex === index ? 'bg-emerald-50/80 border-emerald-300 ring-1 ring-emerald-500/20 shadow-xs' : 'bg-white hover:bg-slate-50/70 border-transparent'"
                         class="group flex items-center justify-between p-3 sm:p-3.5 rounded-2xl border transition-all cursor-pointer select-none gap-3 my-1">
                        
                        <!-- Left: App Icon + Information -->
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <!-- Squircle App Icon Container -->
                            <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-2xl shrink-0 flex items-center justify-center overflow-hidden border border-slate-200/80 shadow-xs"
                                 :class="app.logo_url ? 'bg-white p-1.5' : ('bg-gradient-to-br ' + app.visuals.gradient + ' text-white font-mono font-black text-sm')">
                                <template x-if="app.logo_url">
                                    <img :src="app.logo_url" :alt="app.name" class="w-full h-full object-contain rounded-lg">
                                </template>
                                <template x-if="!app.logo_url">
                                    <span x-text="app.visuals.initials"></span>
                                </template>
                            </div>

                            <!-- Text info -->
                            <div class="min-w-0 flex-1 space-y-0.5">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="text-xs sm:text-sm font-black text-slate-900 group-hover:text-emerald-800 transition-colors truncate"
                                        x-text="app.name"></h4>
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200 shrink-0"
                                          x-text="app.category_name"></span>
                                    <span x-show="favoriteIds.includes(app.id)" class="text-amber-500 text-xs shrink-0" title="Aplikasi Favorit">&#9733;</span>
                                </div>
                                <p class="text-[11px] text-slate-500 truncate"
                                   x-text="app.description || 'Aplikasi portal resmi SMKN 1 Bangsri via Single Sign-On.'"></p>
                            </div>
                        </div>

                        <!-- Right: Actions (Favorite Toggle & Open Link) -->
                        <div class="flex items-center gap-1.5 shrink-0" @click.stop>
                            <!-- Favorite Star Button -->
                            <button type="button"
                                    @click="toggleFavorite(app.id, app.favorite_toggle_url)"
                                    :title="favoriteIds.includes(app.id) ? 'Hapus dari favorit' : 'Tambah ke favorit'"
                                    class="p-2 rounded-xl transition-all"
                                    :class="favoriteIds.includes(app.id) 
                                        ? 'text-amber-500 bg-amber-50 hover:bg-amber-100 border border-amber-200 shadow-2xs' 
                                        : 'text-slate-300 hover:text-amber-500 hover:bg-slate-100'">
                                <svg class="w-4 h-4" 
                                     :class="favoriteIds.includes(app.id) ? 'fill-amber-400' : 'fill-none'" 
                                     stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                            </button>

                            <!-- Open App Link Button -->
                            <a :href="app.launch_url"
                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-700 hover:bg-emerald-800 text-white shadow-xs transition-colors">
                                <span>Buka</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </template>

                <!-- Empty State (No Matches) -->
                <div x-show="filteredApps.length === 0"
                     class="py-12 px-4 text-center flex flex-col items-center justify-center space-y-2">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-700 flex items-center justify-center mb-1">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <p class="text-xs sm:text-sm font-extrabold text-slate-800">Aplikasi Tidak Ditemukan</p>
                    <p class="text-xs text-slate-500 font-medium max-w-sm">
                        Tidak ada aplikasi yang cocok dengan kata kunci <span class="font-bold text-emerald-800" x-text="'&ldquo;' + query + '&rdquo;'"></span>.
                    </p>
                    <button type="button" 
                            @click="query = ''; selectedCategory = 'all'; $refs.globalSearchInput.focus()"
                            class="mt-2 px-3.5 py-1.5 rounded-xl text-xs font-bold bg-emerald-700 hover:bg-emerald-800 text-white transition-colors shadow-2xs">
                        Lihat Semua Aplikasi
                    </button>
                </div>
            </div>

            <!-- Footer Keyboard Navigation Hints -->
            <div class="px-4 py-2.5 bg-slate-50 flex items-center justify-between text-[11px] text-slate-500 font-medium">
                <div class="flex items-center gap-3">
                    <span class="flex items-center gap-1">
                        <kbd class="px-1.5 py-0.5 rounded bg-white border border-slate-200 font-mono text-[10px] font-bold">↑↓</kbd> Navigasi
                    </span>
                    <span class="flex items-center gap-1">
                        <kbd class="px-1.5 py-0.5 rounded bg-white border border-slate-200 font-mono text-[10px] font-bold">↵</kbd> Buka Aplikasi
                    </span>
                    <span class="hidden sm:flex items-center gap-1">
                        <kbd class="px-1.5 py-0.5 rounded bg-white border border-slate-200 font-mono text-[10px] font-bold">ESC</kbd> Tutup
                    </span>
                </div>
                <div class="text-[10px] text-emerald-800 font-extrabold flex items-center gap-1">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                    <span>SiPintu SSO Gateway</span>
                </div>
            </div>
        </div>
    </div>
</div>
