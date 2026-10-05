<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationCategory;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminApplicationCategoryController extends Controller
{
    public function index(): View
    {
        $categories = ApplicationCategory::withCount('applications')
            ->orderBy('display_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        $stats = [
            'total' => $categories->count(),
            'active' => $categories->where('is_active', true)->count(),
            'total_apps' => Application::whereNotNull('category_id')->count(),
            'uncategorized_apps' => Application::whereNull('category_id')->count(),
        ];

        return view('admin.categories.index', compact('categories', 'stats'));
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
            'icon' => $validated['icon'] ?? 'tag',
            'description' => $validated['description'] ?? null,
            'display_order' => $validated['display_order'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLogger::log('admin_create_app_category', [
            'category_id' => $category->id,
            'name' => $category->name,
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', "Kategori '{$category->name}' berhasil ditambahkan.");
    }

    public function update(Request $request, ApplicationCategory $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('application_categories', 'slug')->ignore($category->id)],
            'icon' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'display_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            'name' => $validated['name'],
            'slug' => ! empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']),
            'icon' => $validated['icon'] ?? $category->icon ?? 'tag',
            'description' => $validated['description'] ?? null,
            'display_order' => $validated['display_order'] ?? 0,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : false,
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
        $name = $category->name;

        // Nullify category_id on any associated applications to avoid orphaned foreign keys
        Application::where('category_id', $category->id)->update(['category_id' => null]);

        $category->delete();

        AuditLogger::log('admin_delete_app_category', [
            'category_id' => $category->id,
            'name' => $name,
        ]);

        return redirect()->route('admin.categories.index')
            ->with('success', "Kategori '{$name}' berhasil dihapus. Aplikasi di kategori ini kini masuk kategori Umum.");
    }
}
