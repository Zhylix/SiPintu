<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\ApplicationCategory;
use App\Models\Role;
use App\Services\AuditLogger;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = Application::with(['roles', 'category']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where('name', 'like', "{$search}%");
        }

        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $applications = $query->latest()->paginate(15)->withQueryString();
        $categories = ApplicationCategory::orderBy('display_order')->get();

        return view('admin.applications.index', compact('applications', 'categories'));
    }

    public function create()
    {
        $roles = Role::all();
        $categories = ApplicationCategory::orderBy('display_order')->get();
        $generatedClientId = 'app_'.Str::lower(Str::random(12));
        $generatedSecret = 'sec_'.Str::random(32);

        return view('admin.applications.create', compact('roles', 'categories', 'generatedClientId', 'generatedSecret'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:applications,slug'],
            'category_id' => ['nullable', 'exists:application_categories,id'],
            'description' => ['nullable', 'string'],
            'base_url' => ['required', 'url'],
            'icon' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'client_id' => ['required', 'string', 'unique:applications,client_id'],
            'client_secret' => ['required', 'string'],
            'redirect_uri' => ['required', 'string'],
            'logout_uri' => ['nullable', 'string'],
            'scopes' => ['required', 'string'],
            'status' => ['required', Rule::in(['active', 'maintenance', 'inactive'])],
            'health_check_url' => ['nullable', 'url'],
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,id'],
        ], [
            'base_url.url' => 'Base URL harus berupa format URL valid.',
            'redirect_uri.required' => 'Redirect URI wajib diisi.',
            'roles.required' => 'Pilih minimal satu role yang diizinkan mengakses aplikasi ini.',
            'logo.image' => 'File logo harus berupa gambar (jpeg, png, jpg, gif, svg, webp).',
            'logo.max' => 'Ukuran file logo maksimal 2MB.',
        ]);

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
        }

        $plainSecret = $validated['client_secret'];

        $app = Application::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['slug']),
            'category_id' => $validated['category_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'base_url' => rtrim($validated['base_url'], '/'),
            'icon' => $validated['icon'] ?? 'app-symbol',
            'logo' => $logoPath,
            'client_id' => $validated['client_id'],
            'client_secret' => Hash::make($plainSecret),
            'redirect_uri' => $validated['redirect_uri'],
            'logout_uri' => $validated['logout_uri'] ?? null,
            'scopes' => $validated['scopes'],
            'status' => $validated['status'],
            'health_check_url' => $validated['health_check_url'] ?? null,
            'last_health_status' => ($validated['health_check_url'] ?? null) ? 'online' : null,
        ]);

        $app->roles()->sync($validated['roles']);

        AuditLogger::log('admin_register_application', [
            'application_id' => $app->id,
            'name' => $app->name,
            'client_id' => $app->client_id,
        ]);

        return redirect()->route('admin.applications.index')
            ->with('success', "Aplikasi {$app->name} berhasil terdaftar.")
            ->with('new_client_secret', $plainSecret)
            ->with('new_client_name', $app->name);
    }

    public function edit(Application $application)
    {
        $roles = Role::all();
        $categories = ApplicationCategory::orderBy('display_order')->get();

        return view('admin.applications.edit', compact('application', 'roles', 'categories'));
    }

    public function update(Request $request, Application $application): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('applications')->ignore($application->id)],
            'category_id' => ['nullable', 'exists:application_categories,id'],
            'description' => ['nullable', 'string'],
            'base_url' => ['required', 'url'],
            'icon' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:2048'],
            'redirect_uri' => ['required', 'string'],
            'logout_uri' => ['nullable', 'string'],
            'scopes' => ['required', 'string'],
            'status' => ['required', Rule::in(['active', 'maintenance', 'inactive'])],
            'health_check_url' => ['nullable', 'url'],
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,id'],
        ], [
            'logo.image' => 'File logo harus berupa gambar (jpeg, png, jpg, gif, svg, webp).',
            'logo.max' => 'Ukuran file logo maksimal 2MB.',
        ]);

        $logoPath = $application->logo;

        if ($request->hasFile('logo')) {
            if ($application->logo && Storage::disk('public')->exists($application->logo)) {
                Storage::disk('public')->delete($application->logo);
            }
            $logoPath = $request->file('logo')->store('logos', 'public');
        } elseif ($request->boolean('remove_logo')) {
            if ($application->logo && Storage::disk('public')->exists($application->logo)) {
                Storage::disk('public')->delete($application->logo);
            }
            $logoPath = null;
        }

        $application->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['slug']),
            'category_id' => $validated['category_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'base_url' => rtrim($validated['base_url'], '/'),
            'icon' => $validated['icon'] ?? 'app-symbol',
            'logo' => $logoPath,
            'redirect_uri' => $validated['redirect_uri'],
            'logout_uri' => $validated['logout_uri'] ?? null,
            'scopes' => $validated['scopes'],
            'status' => $validated['status'],
            'health_check_url' => $validated['health_check_url'] ?? null,
        ]);

        $application->roles()->sync($validated['roles']);

        AuditLogger::log('admin_update_application', [
            'application_id' => $application->id,
            'name' => $application->name,
        ]);

        return redirect()->route('admin.applications.index')->with('success', "Konfigurasi aplikasi {$application->name} berhasil diperbarui.");
    }

    public function destroyLogo(Application $application): RedirectResponse
    {
        if ($application->logo && Storage::disk('public')->exists($application->logo)) {
            Storage::disk('public')->delete($application->logo);
        }

        $application->update(['logo' => null]);

        AuditLogger::log('admin_delete_application_logo', [
            'application_id' => $application->id,
            'name' => $application->name,
        ]);

        return back()->with('success', "Logo aplikasi {$application->name} berhasil dihapus.");
    }

    public function regenerateSecret(Application $application): RedirectResponse
    {
        $newSecret = 'sec_'.Str::random(32);
        $application->update([
            'client_secret' => Hash::make($newSecret),
        ]);

        AuditLogger::log('admin_regenerate_client_secret', [
            'application_id' => $application->id,
            'name' => $application->name,
        ]);

        return back()->with('success', 'Client Secret berhasil dibuat ulang.')
            ->with('new_client_secret', $newSecret)
            ->with('new_client_name', $application->name);
    }

    public function testHealth(Application $application): RedirectResponse
    {
        if (! $application->health_check_url) {
            return back()->with('error', 'Aplikasi ini tidak memiliki Health Check URL.');
        }

        try {
            $response = Http::timeout(4)->get($application->health_check_url);
            $status = $response->successful() ? 'online' : 'warning';
        } catch (Exception $e) {
            $status = 'offline';
        }

        $application->update([
            'last_health_status' => $status,
            'last_health_check_at' => now(),
        ]);

        return back()->with('info', "Health check {$application->name}: Status ".strtoupper($status));
    }

    public function destroy(Application $application): RedirectResponse
    {
        $appName = $application->name;

        if ($application->logo && Storage::disk('public')->exists($application->logo)) {
            Storage::disk('public')->delete($application->logo);
        }

        AuditLogger::log('admin_delete_application', [
            'application_id' => $application->id,
            'name' => $appName,
        ]);

        $application->delete();

        return redirect()->route('admin.applications.index')->with('success', "Aplikasi {$appName} telah dihapus dari registry.");
    }
}

