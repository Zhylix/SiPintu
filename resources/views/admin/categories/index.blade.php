@extends('layouts.app', ['headerTitle' => 'Kelola Kategori Aplikasi'])

@section('content')
<div class="space-y-6">
    <!-- Header Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
        <div>
            <h2 class="text-xl font-black text-emerald-950 tracking-tight">Kategori Aplikasi</h2>
            <p class="text-xs text-slate-600 font-medium mt-1">Pengelompokan aplikasi untuk kerapian dan navigasi di katalog aplikasi SiPintu.</p>
        </div>
        <button onclick="document.getElementById('modal-create-category').classList.remove('hidden')"
                class="inline-flex items-center justify-center px-4 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl transition-all shadow-md shadow-emerald-700/20">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Tambah Kategori Baru
        </button>
    </div>

    <!-- Alert Success / Info -->
    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-xs font-bold flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 flex-shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <!-- Category Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        @forelse($categories as $category)
            <div class="bg-white border border-slate-200 rounded-2xl p-5 hover:border-emerald-500 transition-all flex flex-col justify-between space-y-4 shadow-sm">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-100 border border-emerald-300 flex items-center justify-center text-emerald-800 font-extrabold">
                                {{ strtoupper(substr($category->name, 0, 2)) }}
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">{{ $category->name }}</h3>
                                <p class="text-[10px] text-emerald-800 font-mono font-bold mt-0.5">/{{ $category->slug }}</p>
                            </div>
                        </div>
                        <span class="px-2.5 py-1 bg-slate-100 rounded-lg text-[10px] font-bold text-slate-600 border border-slate-200">
                            Urutan: {{ $category->display_order }}
                        </span>
                    </div>

                    <p class="text-xs text-slate-600 font-medium line-clamp-2 min-h-[32px]">
                        {{ $category->description ?? 'Tidak ada deskripsi.' }}
                    </p>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                            {{ $category->applications_count }} Aplikasi
                        </span>
                        @if($category->is_active)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                Aktif
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                Nonaktif
                            </span>
                        @endif
                    </div>

                    <div class="flex items-center space-x-2">
                        <button onclick="openEditModal({{ json_encode($category) }})"
                                class="p-2 text-emerald-800 hover:bg-emerald-50 rounded-lg transition-all border border-slate-200"
                                title="Edit Kategori">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </button>

                        <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 text-rose-600 hover:bg-rose-50 rounded-lg transition-all border border-rose-200" title="Hapus Kategori">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white border border-slate-200 rounded-2xl p-12 text-center shadow-sm">
                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto text-slate-500 mb-3 border border-slate-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <h3 class="text-sm font-black text-slate-900">Belum Ada Kategori</h3>
                <p class="text-xs text-slate-600 font-medium mt-1">Klik tombol di atas untuk membuat kategori aplikasi pertama.</p>
            </div>
        @endforelse
    </div>
</div>

<!-- Modal Create Category -->
<div id="modal-create-category" class="hidden fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white border border-slate-200 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-black text-emerald-950">Tambah Kategori Baru</h3>
            <button onclick="document.getElementById('modal-create-category').classList.add('hidden')" class="text-slate-400 hover:text-slate-700 font-bold">&times;</button>
        </div>

        <form action="{{ route('admin.categories.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Nama Kategori</label>
                <input type="text" name="name" required placeholder="Contoh: Ujian & Evaluasi"
                       class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 font-semibold focus:outline-none focus:border-emerald-600 focus:bg-white transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Deskripsi</label>
                <textarea name="description" rows="3" placeholder="Keterangan kategori..."
                          class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 font-semibold focus:outline-none focus:border-emerald-600 focus:bg-white transition-all"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Urutan Tampilan</label>
                    <input type="number" name="display_order" value="0"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 font-semibold focus:outline-none focus:border-emerald-600 focus:bg-white transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Status</label>
                    <div class="pt-2 flex items-center space-x-2">
                        <input type="checkbox" name="is_active" value="1" checked id="create_is_active" class="w-4 h-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                        <label for="create_is_active" class="text-xs text-slate-700 font-semibold">Aktif</label>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('modal-create-category').classList.add('hidden')"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-200">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl shadow-md shadow-emerald-700/20">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Category -->
<div id="modal-edit-category" class="hidden fixed inset-0 z-50 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white border border-slate-200 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-black text-emerald-950">Edit Kategori</h3>
            <button onclick="document.getElementById('modal-edit-category').classList.add('hidden')" class="text-slate-400 hover:text-slate-700 font-bold">&times;</button>
        </div>

        <form id="form-edit-category" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Nama Kategori</label>
                <input type="text" name="name" id="edit_name" required
                       class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 font-semibold focus:outline-none focus:border-emerald-600 focus:bg-white transition-all">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Deskripsi</label>
                <textarea name="description" id="edit_description" rows="3"
                          class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 font-semibold focus:outline-none focus:border-emerald-600 focus:bg-white transition-all"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Urutan Tampilan</label>
                    <input type="number" name="display_order" id="edit_display_order"
                           class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-900 font-semibold focus:outline-none focus:border-emerald-600 focus:bg-white transition-all">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Status</label>
                    <div class="pt-2 flex items-center space-x-2">
                        <input type="checkbox" name="is_active" value="1" id="edit_is_active" class="w-4 h-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600">
                        <label for="edit_is_active" class="text-xs text-slate-700 font-semibold">Aktif</label>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('modal-edit-category').classList.add('hidden')"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-200">
                    Batal
                </button>
                <button type="submit" class="px-4 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-extrabold rounded-xl shadow-md shadow-emerald-700/20">
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
