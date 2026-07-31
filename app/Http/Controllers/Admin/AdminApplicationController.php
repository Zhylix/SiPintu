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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminApplicationController extends Controller
{
    public function index()
    {
        $applications = Application::with(['roles', 'category'])->latest()->paginate(15);
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
        ]);

        $plainSecret = $validated['client_secret'];

        $app = Application::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['slug']),
            'category_id' => $validated['category_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'base_url' => rtrim($validated['base_url'], '/'),
            'icon' => $validated['icon'] ?? 'app-symbol',
            'client_id' => $validated['client_id'],
            'client_secret' => Hash::make($plainSecret),
            'redirect_uri' => $validated['redirect_uri'],
            'logout_uri' => $validated['logout_uri'] ?? null,
            'scopes' => $validated['scopes'],
            'status' => $validated['status'],
            'health_check_url' => $validated['health_check_url'] ?? null,
            'last_health_status' => $validated['health_check_url'] ? 'online' : null,
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
            'redirect_uri' => ['required', 'string'],
            'logout_uri' => ['nullable', 'string'],
            'scopes' => ['required', 'string'],
            'status' => ['required', Rule::in(['active', 'maintenance', 'inactive'])],
            'health_check_url' => ['nullable', 'url'],
            'roles' => ['required', 'array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $application->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['slug']),
            'category_id' => $validated['category_id'] ?? null,
            'description' => $validated['description'] ?? null,
            'base_url' => rtrim($validated['base_url'], '/'),
            'icon' => $validated['icon'] ?? 'app-symbol',
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
        AuditLogger::log('admin_delete_application', [
            'application_id' => $application->id,
            'name' => $appName,
        ]);

        $application->delete();

        return redirect()->route('admin.applications.index')->with('success', "Aplikasi {$appName} telah dihapus dari registry.");
    }
}
