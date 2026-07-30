@extends('layouts.app', ['headerTitle' => 'Kelola Kategori Aplikasi'])

@section('content')
<div class="space-y-6">
    <!-- Header Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-slate-900/60 border border-slate-800 rounded-2xl p-5 backdrop-blur-xl">
        <div>
            <h2 class="text-xl font-bold text-white tracking-tight">Kategori Aplikasi</h2>
            <p class="text-xs text-slate-400 mt-1">Pengelompokan aplikasi untuk kerapian dan navigasi di katalog aplikasi SiPintu.</p>
        </div>
        <button onclick="document.getElementById('modal-create-category').classList.remove('hidden')"
                class="inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition-all shadow-lg shadow-indigo-600/30">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Tambah Kategori Baru
        </button>
    </div>

    <!-- Alert Success / Info -->
    @if(session('success'))
        <div class="p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-xl text-emerald-400 text-xs font-medium flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <!-- Category Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($categories as $category)
            <div class="bg-slate-900/90 border border-slate-800 rounded-2xl p-5 hover:border-indigo-500/40 transition-all flex flex-col justify-between space-y-4 shadow-xl">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center text-indigo-400 font-bold">
                                {{ strtoupper(substr($category->name, 0, 2)) }}
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-white">{{ $category->name }}</h3>
                                <p class="text-[10px] text-slate-400 font-mono mt-0.5">/{{ $category->slug }}</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 bg-slate-800 rounded-lg text-[10px] font-semibold text-slate-300">
                            Urutan: {{ $category->display_order }}
                        </span>
                    </div>

                    <p class="text-xs text-slate-400 line-clamp-2 min-h-[32px]">
                        {{ $category->description ?? 'Tidak ada deskripsi.' }}
                    </p>
                </div>

                <div class="pt-4 border-t border-slate-800/80 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-indigo-500/10 text-indigo-400 border border-indigo-500/20">
                            {{ $category->applications_count }} Aplikasi
                        </span>
                        @if($category->is_active)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                Aktif
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400">
                                Nonaktif
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center space-x-2">
                        <button onclick="openEditModal({{ json_encode($category) }})"
                                class="p-2 text-slate-400 hover:text-indigo-400 bg-slate-800/50 hover:bg-slate-800 rounded-lg transition-all"
                                title="Edit Kategori">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </button>

                        <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 text-slate-400 hover:text-rose-400 bg-slate-800/50 hover:bg-slate-800 rounded-lg transition-all" title="Hapus Kategori">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-slate-900 border border-slate-800 rounded-2xl p-12 text-center">
                <div class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center mx-auto text-slate-500 mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-white">Belum Ada Kategori</h3>
                <p class="text-xs text-slate-400 mt-1">Klik tombol di atas untuk membuat kategori aplikasi pertama.</p>
            </div>
        @endforelse
    </div>
</div>

<!-- Modal Create Category -->
<div id="modal-create-category" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-base font-bold text-white">Tambah Kategori Baru</h3>
            <button onclick="document.getElementById('modal-create-category').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form action="{{ route('admin.categories.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Nama Kategori</label>
                <input type="text" name="name" required placeholder="Contoh: Ujian & Evaluasi"
                       class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Deskripsi</label>
                <textarea name="description" rows="3" placeholder="Keterangan kategori..."
                          class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-indigo-500"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Urutan Tampilan</label>
                    <input type="number" name="display_order" value="0"
                           class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Status</label>
                    <div class="pt-2 flex items-center space-x-2">
                        <input type="checkbox" name="is_active" value="1" checked id="create_is_active" class="w-4 h-4 rounded border-slate-800 bg-slate-950 text-indigo-600">
                        <label for="create_is_active" class="text-xs text-slate-300">Aktif</label>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-800 flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('modal-create-category').classList.add('hidden')"
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold rounded-xl">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Category -->
<div id="modal-edit-category" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-md flex items-center justify-center p-4">
    <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <h3 class="text-base font-bold text-white">Edit Kategori</h3>
            <button onclick="document.getElementById('modal-edit-category').classList.add('hidden')" class="text-slate-400 hover:text-white">&times;</button>
        </div>

        <form id="form-edit-category" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Nama Kategori</label>
                <input type="text" name="name" id="edit_name" required
                       class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Deskripsi</label>
                <textarea name="description" id="edit_description" rows="3"
                          class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-indigo-500"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Urutan Tampilan</label>
                    <input type="number" name="display_order" id="edit_display_order"
                           class="w-full px-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">Status</label>
                    <div class="pt-2 flex items-center space-x-2">
                        <input type="checkbox" name="is_active" value="1" id="edit_is_active" class="w-4 h-4 rounded border-slate-800 bg-slate-950 text-indigo-600">
                        <label for="edit_is_active" class="text-xs text-slate-300">Aktif</label>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-800 flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('modal-edit-category').classList.add('hidden')"
                        class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold rounded-xl">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl shadow-lg shadow-indigo-600/30">
                    Update Kategori
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(category) {
        document.getElementById('form-edit-category').action = `/admin/categories/${category.id}`;
        document.getElementById('edit_name').value = category.name;
        document.getElementById('edit_description').value = category.description || '';
        document.getElementById('edit_display_order').value = category.display_order;
        document.getElementById('edit_is_active').checked = !!category.is_active;

        document.getElementById('modal-edit-category').classList.remove('hidden');
    }
</script>
@endsection
