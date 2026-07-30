<div class="space-y-6" x-data="{ selectedCategory: 'all', searchQuery: '' }">
    <!-- Search Bar & Category Filter Pills -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl">
        <!-- Category Pills Navigation -->
        <div class="flex items-center space-x-2 overflow-x-auto pb-2 lg:pb-0 scrollbar-none">
            <button @click="selectedCategory = 'all'"
                    :class="selectedCategory === 'all' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'bg-slate-950 text-slate-400 hover:text-white hover:bg-slate-800'"
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center space-x-1.5 border border-slate-800">
                <span>Semua Aplikasi</span>
                <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-slate-900/60 font-mono">{{ $applications->count() }}</span>
            </button>

            <button @click="selectedCategory = 'favorites'"
                    :class="selectedCategory === 'favorites' ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-500/30 font-extrabold' : 'bg-slate-950 text-amber-400 hover:bg-amber-500/10'"
                    class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center space-x-1.5 border border-slate-800">
                <svg class="w-3.5 h-3.5 fill-current" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
                <span>Favorit Saya</span>
                <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-amber-500/20 font-mono">{{ count($favoriteAppIds) }}</span>
            </button>

            @foreach($categories as $category)
                @php
                    $catAppCount = $applications->where('category_id', $category->id)->count();
                @endphp
                @if($catAppCount > 0)
                    <button @click="selectedCategory = 'cat-{{ $category->id }}'"
                            :class="selectedCategory === 'cat-{{ $category->id }}' ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' : 'bg-slate-950 text-slate-400 hover:text-white hover:bg-slate-800'"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap flex items-center space-x-1.5 border border-slate-800">
                        <span>{{ $category->name }}</span>
                        <span class="px-1.5 py-0.5 rounded-md text-[10px] bg-slate-900/60 font-mono">{{ $catAppCount }}</span>
                    </button>
                @endif
            @endforeach
        </div>

        <!-- Search Input -->
        <div class="relative min-w-[200px]">
            <input type="text" x-model="searchQuery" placeholder="Cari nama aplikasi..."
                   class="w-full pl-9 pr-4 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-all">
            <svg class="w-4 h-4 text-slate-500 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                 class="group relative bg-slate-900/90 border border-slate-800 hover:border-indigo-500/40 rounded-2xl p-5 transition-all duration-300 hover:shadow-xl hover:shadow-indigo-500/5 flex flex-col justify-between space-y-4">
                
                <div>
                    <!-- Card Top Header -->
                    <div class="flex items-start justify-between gap-3 mb-2">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500/20 to-purple-500/20 border border-indigo-500/30 flex items-center justify-center text-indigo-300 font-black text-sm group-hover:scale-105 transition-transform">
                                {{ strtoupper(substr($app->name, 0, 2)) }}
                            </div>
                            <div>
                                <h4 class="text-sm font-bold text-white group-hover:text-indigo-300 transition-colors">{{ $app->name }}</h4>
                                @if($app->category)
                                    <span class="inline-flex items-center text-[10px] font-semibold text-indigo-400">
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
                                    class="p-2 rounded-xl transition-all {{ $isFav ? 'bg-amber-500/20 text-amber-400 border border-amber-500/40' : 'bg-slate-950 text-slate-600 hover:text-amber-400 border border-slate-800' }}">
                                <svg class="w-4 h-4 {{ $isFav ? 'fill-amber-400' : 'fill-none' }}" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                </svg>
                            </button>
                        </form>
                    </div>

                    <p class="text-xs text-slate-400 leading-relaxed line-clamp-2">
                        {{ $app->description ?? 'Aplikasi terintegrasi dengan SSO Gateway SiPintu.' }}
                    </p>
                </div>

                <!-- Footer Launch Button -->
                <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between">
                    <span class="inline-flex items-center text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/20">
                        ● SSO Ready
                    </span>

                    <a href="{{ route('oauth.authorize', ['client_id' => $app->client_id, 'redirect_uri' => $app->redirect_uri, 'response_type' => 'code', 'scope' => 'openid profile email']) }}"
                       class="inline-flex items-center space-x-1.5 px-3.5 py-2 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-500 hover:to-purple-500 text-white text-xs font-bold rounded-xl transition-all shadow-md shadow-indigo-600/20 group-hover:shadow-indigo-600/40">
                        <span>Masuk via SSO</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-slate-900 border border-slate-800 rounded-2xl p-8 text-center text-slate-400 text-xs">
                Belum ada aplikasi eksternal yang diizinkan untuk peran akun Anda.
            </div>
        @endforelse
    </div>
</div>
