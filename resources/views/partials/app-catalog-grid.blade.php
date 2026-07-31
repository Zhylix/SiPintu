<div class="space-y-6" x-data="{ selectedCategory: 'all', searchQuery: '' }">
    <!-- Search Bar & Category Filter Pills -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white border border-slate-200 rounded-2xl p-4 shadow-sm">
        <!-- Category Pills Navigation -->
        <div class="flex items-center space-x-2 overflow-x-auto pb-2 lg:pb-0 scrollbar-none">
            <button @click="selectedCategory = 'all'"
                    :class="selectedCategory === 'all' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20 font-extrabold' : 'bg-slate-100 text-slate-700 hover:text-emerald-700 hover:bg-emerald-50'"
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center space-x-1.5 border border-slate-200">
                <span>Semua Aplikasi</span>
                <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-white/30 font-mono">{{ $applications->count() }}</span>
            </button>

            <button @click="selectedCategory = 'favorites'"
                    :class="selectedCategory === 'favorites' ? 'bg-amber-500 text-white shadow-md shadow-amber-500/20 font-extrabold' : 'bg-slate-100 text-amber-700 hover:bg-amber-50'"
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center space-x-1.5 border border-slate-200">
                <svg class="w-3.5 h-3.5 fill-current text-amber-500" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                <span>Favorit Saya</span>
                <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-white/40 font-mono">{{ count($favoriteAppIds) }}</span>
            </button>

            @foreach($categories as $category)
                @php
                    $catAppCount = $applications->where('category_id', $category->id)->count();
                @endphp
                @if($catAppCount > 0)
                    <button @click="selectedCategory = 'cat-{{ $category->id }}'"
                            :class="selectedCategory === 'cat-{{ $category->id }}' ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20 font-extrabold' : 'bg-slate-100 text-slate-700 hover:text-emerald-700 hover:bg-emerald-50'"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center space-x-1.5 border border-slate-200">
                        <span>{{ $category->name }}</span>
                        <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-white/40 font-mono">{{ $catAppCount }}</span>
                    </button>
                @endif
            @endforeach
        </div>

        <!-- Search Input -->
        <div class="relative min-w-[200px]">
            <input type="text" x-model="searchQuery" placeholder="Cari nama aplikasi..."
                   class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:border-emerald-600 transition-all font-semibold">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
    </div>

    <!-- Applications Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($applications as $app)
            @php
                $isFav = in_array($app->id, $favoriteAppIds);
                $catId = $app->category_id ? 'cat-' . $app->category_id : 'unassigned';
            @endphp
            <div x-show="(selectedCategory === 'all' || (selectedCategory === 'favorites' && {{ $isFav ? 'true' : 'false' }}) || selectedCategory === '{{ $catId }}') && ('{{ strtolower($app->name) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower($app->description) }}'.includes(searchQuery.toLowerCase()))"
                 x-transition
                 class="group relative bg-white border border-slate-200 hover:border-emerald-500 rounded-2xl p-5 transition-all duration-300 hover:shadow-xl hover:shadow-emerald-900/5 flex flex-col justify-between space-y-4">
                
                <div>
                    <!-- Card Top Header -->
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-100 border border-emerald-300 flex items-center justify-center text-emerald-800 font-black text-sm group-hover:scale-105 transition-transform">
                                {{ strtoupper(substr($app->name, 0, 2)) }}
                            </div>
                            <div>
                                <h4 class="text-sm font-black text-emerald-950 group-hover:text-emerald-700 transition-colors">{{ $app->name }}</h4>
                                @if($app->category)
                                    <span class="inline-flex items-center text-[10px] font-extrabold text-emerald-700">
                                        {{ $app->category->name }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center text-[10px] font-semibold text-slate-500">
                                        Umum
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Favorite ⭐ Button -->
                        <form action="{{ route('applications.favorite.toggle', $app) }}" method="POST">
                            @csrf
                            <button type="submit"
                                    title="{{ $isFav ? 'Hapus dari favorit' : 'Tambah ke favorit' }}"
                                    class="p-2 rounded-xl transition-all {{ $isFav ? 'bg-amber-100 text-amber-600 border border-amber-300' : 'bg-slate-50 text-slate-400 hover:text-amber-500 border border-slate-200' }}">
                                <svg class="w-4 h-4 {{ $isFav ? 'fill-amber-500' : 'fill-none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                            </button>
                        </form>
                    </div>

                    <p class="text-xs text-slate-600 leading-relaxed line-clamp-2 font-medium">
                        {{ $app->description ?? 'Aplikasi terintegrasi dengan SSO Gateway SiPintu.' }}
                    </p>
                </div>

                <!-- Footer Launch Button -->
                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="inline-flex items-center text-[10px] font-extrabold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded-full border border-emerald-300">
                        ● Terintegrasi (SSO)
                    </span>

                    <a href="{{ route('oauth.authorize', ['client_id' => $app->client_id, 'redirect_uri' => $app->redirect_uri, 'response_type' => 'code', 'scope' => 'openid profile email']) }}"
                       class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-600/20">
                        <span>Masuk Akses Terpadu (SSO)</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white border border-slate-200 rounded-2xl p-8 text-center text-slate-500 text-xs font-semibold">
                Belum ada aplikasi eksternal yang diizinkan untuk peran akun Anda.
            </div>
        @endforelse
    </div>
</div>
