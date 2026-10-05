@extends('layouts.app', ['headerTitle' => 'Kategori Aplikasi'])

@section('content')
<div class="space-y-6 min-w-0 max-w-full" x-data="{
    createModalOpen: false,
    editModalOpen: false,
    deleteModalOpen: false,
    selectedCategory: null,
    openEdit(cat) {
        this.selectedCategory = cat;
        this.editModalOpen = true;
    },
    openDelete(cat) {
        this.selectedCategory = cat;
        this.deleteModalOpen = true;
    }
}">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <span class="p-2 rounded-xl bg-emerald-100 text-emerald-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                </span>
                <h2 class="text-xl font-black text-emerald-950 tracking-tight">Kategori Aplikasi</h2>
            </div>
            <p class="text-xs text-slate-500 font-medium mt-1">Kelola pengelompokan aplikasi SSO SiPintu agar katalog dan pencarian terorganisir.</p>
        </div>
        <button type="button" 
                @click="createModalOpen = true"
                class="inline-flex items-center justify-center px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-black rounded-xl transition-all shadow-md shadow-emerald-700/20 active:scale-95 shrink-0">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Tambah Kategori
        </button>
    </div>

    <!-- Alert Notifications -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs font-bold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-700 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-xs font-bold space-y-1 shadow-xs">
            @foreach($errors->all() as $err)
                <div>• {{ $err }}</div>
            @endforeach
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-xs">
            <p class="text-[11px] font-black uppercase tracking-wider text-slate-400">Total Kategori</p>
            <p class="text-2xl font-black text-slate-800 mt-1">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-xs">
            <p class="text-[11px] font-black uppercase tracking-wider text-emerald-600">Kategori Aktif</p>
            <p class="text-2xl font-black text-emerald-800 mt-1">{{ $stats['active'] }}</p>
        </div>
        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-xs">
            <p class="text-[11px] font-black uppercase tracking-wider text-sky-600">Aplikasi Terkategori</p>
            <p class="text-2xl font-black text-sky-800 mt-1">{{ $stats['total_apps'] }}</p>
        </div>
        <div class="bg-white border border-slate-200/90 rounded-2xl p-4 shadow-xs">
            <p class="text-[11px] font-black uppercase tracking-wider text-amber-600">Aplikasi Umum</p>
            <p class="text-2xl font-black text-amber-800 mt-1">{{ $stats['uncategorized_apps'] }}</p>
        </div>
    </div>

    <!-- Category List Table / Cards -->
    <div class="bg-white border border-slate-200/90 rounded-2xl shadow-xs overflow-hidden">
        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <h3 class="text-xs font-black uppercase tracking-wider text-slate-600">Daftar Kategori Aplikasi</h3>
            <span class="text-xs text-slate-400 font-medium">{{ $categories->count() }} Kategori terdaftar</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/80 text-[11px] font-black uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-4 w-12 text-center">Urutan</th>
                        <th class="py-3 px-4">Nama & Slug Kategori</th>
                        <th class="py-3 px-4 hidden md:table-cell">Deskripsi</th>
                        <th class="py-3 px-4 text-center">Jumlah Aplikasi</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse($categories as $cat)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-500">
                                {{ $cat->display_order }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-800 flex items-center justify-center font-bold text-xs shrink-0 border border-emerald-100">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-black text-slate-900">{{ $cat->name }}</p>
                                        <p class="font-mono text-[10px] text-slate-400">{{ $cat->slug }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-slate-500 hidden md:table-cell max-w-xs truncate">
                                {{ $cat->description ?: '-' }}
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-mono font-bold {{ $cat->applications_count > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-500' }}">
                                    {{ $cat->applications_count }} aplikasi
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                @if($cat->is_active)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <button type="button" 
                                            @click="openEdit(@js($cat))"
                                            class="px-2.5 py-1.5 bg-slate-100 hover:bg-emerald-100 text-slate-700 hover:text-emerald-800 rounded-lg text-xs font-bold transition-colors">
                                        Edit
                                    </button>
                                    <button type="button" 
                                            @click="openDelete(@js($cat))"
                                            class="px-2.5 py-1.5 bg-slate-100 hover:bg-rose-100 text-slate-700 hover:text-rose-700 rounded-lg text-xs font-bold transition-colors">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 px-4 text-center text-slate-500">
                                <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-bold text-slate-800">Belum Ada Kategori</p>
                                <p class="text-xs text-slate-400 mt-1">Klik tombol &ldquo;Tambah Kategori&rdquo; untuk membuat kategori aplikasi baru.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Tambah Kategori -->
    <div x-show="createModalOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
        
        <div @click.away="createModalOpen = false"
             class="w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden divide-y divide-slate-100">
            
            <div class="px-5 py-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                    <h3 class="text-sm font-black text-slate-900">Tambah Kategori Baru</h3>
                </div>
                <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('admin.categories.store') }}" method="POST" class="p-5 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Kategori <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required placeholder="Contoh: Pembelajaran Online"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15 transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Slug URL <span class="text-slate-400 font-normal">(opsional, dibuat otomatis jika kosong)</span></label>
                    <input type="text" name="slug" placeholder="pembelajaran-online"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono text-slate-800 focus:bg-white focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15 transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Deskripsi Singkat</label>
                    <textarea name="description" rows="2" placeholder="Keterangan singkat fungsi kategori ini..."
                              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:bg-white focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15 transition-all"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Urutan Tampilan</label>
                        <input type="number" name="display_order" value="0" min="0"
                               class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold font-mono text-slate-800 focus:bg-white focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15 transition-all">
                    </div>

                    <div class="flex flex-col justify-end">
                        <label class="inline-flex items-center gap-2 cursor-pointer pb-2">
                            <input type="checkbox" name="is_active" value="1" checked class="w-4 h-4 rounded text-emerald-600 border-slate-300 focus:ring-emerald-500">
                            <span class="text-xs font-bold text-slate-700">Status Aktif</span>
                        </label>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="createModalOpen = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-black transition-colors shadow-xs">
                        Simpan Kategori
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Kategori -->
    <div x-show="editModalOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
        
        <div @click.away="editModalOpen = false"
             class="w-full max-w-lg bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden divide-y divide-slate-100">
            
            <div class="px-5 py-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                    <h3 class="text-sm font-black text-slate-900">Edit Kategori</h3>
                </div>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form :action="'/admin/categories/' + (selectedCategory ? selectedCategory.id : '')" method="POST" class="p-5 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Nama Kategori <span class="text-rose-500">*</span></label>
                    <input type="text" name="name" required :value="selectedCategory?.name"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15 transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Slug URL</label>
                    <input type="text" name="slug" :value="selectedCategory?.slug"
                           class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono text-slate-800 focus:bg-white focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15 transition-all">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Deskripsi Singkat</label>
                    <textarea name="description" rows="2" x-text="selectedCategory?.description || ''"
                              class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium text-slate-800 focus:bg-white focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15 transition-all"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Urutan Tampilan</label>
                        <input type="number" name="display_order" min="0" :value="selectedCategory?.display_order"
                               class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold font-mono text-slate-800 focus:bg-white focus:outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-600/15 transition-all">
                    </div>

                    <div class="flex flex-col justify-end">
                        <label class="inline-flex items-center gap-2 cursor-pointer pb-2">
                            <input type="checkbox" name="is_active" value="1" :checked="selectedCategory?.is_active" class="w-4 h-4 rounded text-emerald-600 border-slate-300 focus:ring-emerald-500">
                            <span class="text-xs font-bold text-slate-700">Status Aktif</span>
                        </label>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="editModalOpen = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-xl text-xs font-black transition-colors shadow-xs">
                        Perbarui Kategori
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Konfirmasi Hapus -->
    <div x-show="deleteModalOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
        
        <div @click.away="deleteModalOpen = false"
             class="w-full max-w-sm bg-white rounded-3xl shadow-2xl border border-slate-200 p-5 space-y-4 text-center">
            
            <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </div>

            <div>
                <h3 class="text-sm font-black text-slate-900">Hapus Kategori Ini?</h3>
                <p class="text-xs text-slate-500 mt-1">
                    Kategori <span class="font-bold text-slate-800" x-text="selectedCategory?.name"></span> akan dihapus. Aplikasi yang ada di kategori ini tidak akan terhapus dan otomatis beralih ke kategori Umum.
                </p>
            </div>

            <form :action="'/admin/categories/' + (selectedCategory ? selectedCategory.id : '')" method="POST" class="flex items-center justify-center gap-2 pt-2">
                @csrf
                @method('DELETE')
                <button type="button" @click="deleteModalOpen = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-colors">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-black transition-colors shadow-xs">
                    Ya, Hapus
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
