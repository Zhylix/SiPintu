<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationCategory;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminApplicationCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = ApplicationCategory::withCount('applications');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $categories = $query->orderBy('display_order', 'asc')->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:application_categories,slug'],
            'icon' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'display_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category = ApplicationCategory::create([
            'name' => $validated['name'],
            'slug' => ! empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']),
            'icon' => $validated['icon'] ?? 'folder',
            'description' => $validated['description'] ?? null,
            'display_order' => $validated['display_order'] ?? 0,
            'is_active' => $request->has('is_active') ? (bool) $request->is_active : true,
        ]);

        AuditLogger::log('admin_create_app_category', [
            'category_id' => $category->id,
            'name' => $category->name,
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', "Kategori '{$category->name}' berhasil dibuat.");
    }

    public function update(Request $request, ApplicationCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('application_categories')->ignore($category->id)],
            'icon' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'display_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            'name' => $validated['name'],
            'slug' => ! empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']),
            'icon' => $validated['icon'] ?? 'folder',
            'description' => $validated['description'] ?? null,
            'display_order' => $validated['display_order'] ?? 0,
            'is_active' => $request->has('is_active') ? (bool) $request->is_active : false,
        ]);

        AuditLogger::log('admin_update_app_category', [
            'category_id' => $category->id,
            'name' => $category->name,
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', "Kategori '{$category->name}' berhasil diperbarui.");
    }

    public function destroy(ApplicationCategory $category): RedirectResponse
    {
        $catName = $category->name;

        AuditLogger::log('admin_delete_app_category', [
            'category_id' => $category->id,
            'name' => $catName,
        ]);

        $category->delete();

        return redirect()->route('admin.categories.index')
            ->with('success', "Kategori '{$catName}' telah dihapus.");
    }
}
