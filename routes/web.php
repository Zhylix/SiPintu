<?php

use App\Http\Controllers\Admin\AdminApplicationController;
use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminMonitoringController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminSijunaController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Demo\ExternalAppDemoController;
use App\Http\Controllers\OAuthController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Root & Authentication Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (Auth::check()) {
        return Auth::user()->isAdmin() ? redirect()->route('admin.dashboard') : redirect()->route('profile');
    }
    return redirect()->route('login');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', function () {
        return Auth::user()->isAdmin() ? redirect()->route('admin.dashboard') : redirect()->route('profile');
    })->name('dashboard');

    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [AuthController::class, 'updatePassword'])->name('profile.password');
});

/*
|--------------------------------------------------------------------------
| OAuth 2.0 & OpenID Connect Server Routes
|--------------------------------------------------------------------------
*/

Route::get('/oauth/authorize', [OAuthController::class, 'authorize'])->name('oauth.authorize');
Route::post('/oauth/token', [OAuthController::class, 'token'])->name('oauth.token');
Route::post('/oauth/logout', [OAuthController::class, 'logout'])->name('oauth.logout');
Route::get('/.well-known/openid-configuration', [OAuthController::class, 'openidConfiguration'])->name('oauth.well-known');
Route::get('/oauth/jwks.json', [OAuthController::class, 'jwks'])->name('oauth.jwks');

/*
|--------------------------------------------------------------------------
| Admin Panel Routes (Protected by auth and admin middleware)
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // User Management (Teachers, DUDI, Students, Admins)
    Route::resource('users', AdminUserController::class);

    // External Application Registry & OAuth Clients
    Route::resource('applications', AdminApplicationController::class);
    Route::post('/applications/{application}/regenerate-secret', [AdminApplicationController::class, 'regenerateSecret'])->name('applications.regenerate-secret');
    Route::post('/applications/{application}/test-health', [AdminApplicationController::class, 'testHealth'])->name('applications.test-health');

    // Role & Permission Management
    Route::get('/roles', [AdminRoleController::class, 'index'])->name('roles.index');
    Route::put('/roles/{role}/permissions', [AdminRoleController::class, 'updatePermissions'])->name('roles.update-permissions');

    // SIJUNA API Integration & Student Sync
    Route::get('/sijuna', [AdminSijunaController::class, 'index'])->name('sijuna.index');
    Route::post('/sijuna/sync', [AdminSijunaController::class, 'triggerSync'])->name('sijuna.sync');

    // Audit Logs
    Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs.index');

    // System Monitoring
    Route::get('/monitoring', [AdminMonitoringController::class, 'index'])->name('monitoring.index');
    Route::post('/monitoring/health-checks', [AdminMonitoringController::class, 'runHealthChecks'])->name('monitoring.run-health-checks');
});

/*
|--------------------------------------------------------------------------
| External Application Simulator & Demo Routes
|--------------------------------------------------------------------------
*/

Route::get('/demo/health', [ExternalAppDemoController::class, 'healthCheck'])->name('demo.health');
Route::prefix('demo/{appSlug?}')->group(function () {
    Route::get('/', [ExternalAppDemoController::class, 'index'])->name('demo.index');
    Route::get('/sso-login', [ExternalAppDemoController::class, 'loginRedirect'])->name('demo.login');
    Route::get('/callback', [ExternalAppDemoController::class, 'callback'])->name('demo.callback');
    Route::get('/logout', [ExternalAppDemoController::class, 'logout'])->name('demo.logout');
});
