<?php

use App\Http\Controllers\Admin\AdminApplicationCategoryController;
use App\Http\Controllers\Admin\AdminAnnouncementController;
use App\Http\Controllers\Admin\AdminApplicationController;
use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminMonitoringController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminSijunaController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Dudi\DudiDashboardController;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Http\Controllers\Teacher\TeacherDashboardController;
use App\Http\Controllers\UserApplicationFavoriteController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Root & Authentication Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    if (Auth::check()) {
        $user = Auth::user();
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->isTeacher()) {
            return redirect()->route('teacher.dashboard');
        }
        if ($user->isDudi()) {
            return redirect()->route('dudi.dashboard');
        }
        if ($user->isStudent()) {
            return redirect()->route('student.dashboard');
        }

        return redirect()->route('profile');
    }

    return redirect()->route('login');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', function () {
        $user = Auth::user();
        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->isTeacher()) {
            return redirect()->route('teacher.dashboard');
        }
        if ($user->isDudi()) {
            return redirect()->route('dudi.dashboard');
        }
        if ($user->isStudent()) {
            return redirect()->route('student.dashboard');
        }

        return redirect()->route('profile');
    })->name('dashboard');

    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile');
    Route::get('/profile/edit', [AuthController::class, 'showProfile'])->name('profile.edit');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [AuthController::class, 'updatePassword'])->name('profile.password');

    // Toggle favorite application for user
    Route::post('/applications/{application}/favorite', [UserApplicationFavoriteController::class, 'toggleFavorite'])->name('applications.favorite.toggle');
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
| Portal Siswa Routes (Protected by auth and role:student middleware)
|--------------------------------------------------------------------------
*/
Route::prefix('siswa')->name('student.')->middleware(['auth', 'role:student'])->group(function () {
    Route::get('/dashboard', [StudentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/apps', [StudentDashboardController::class, 'apps'])->name('apps');
});

/*
|--------------------------------------------------------------------------
| Portal Guru Routes (Protected by auth and role:teacher middleware)
|--------------------------------------------------------------------------
*/
Route::prefix('guru')->name('teacher.')->middleware(['auth', 'role:teacher'])->group(function () {
    Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');
    Route::get('/siswa-bimbingan', [TeacherDashboardController::class, 'students'])->name('students');
    Route::get('/penilaian', [TeacherDashboardController::class, 'evaluations'])->name('evaluations');
    Route::get('/apps', [TeacherDashboardController::class, 'apps'])->name('apps');
});

/*
|--------------------------------------------------------------------------
| Portal DUDI Routes (Protected by auth and role:dudi middleware)
|--------------------------------------------------------------------------
*/
Route::prefix('dudi')->name('dudi.')->middleware(['auth', 'role:dudi'])->group(function () {
    Route::get('/dashboard', [DudiDashboardController::class, 'index'])->name('dashboard');
    Route::get('/evaluasi', [DudiDashboardController::class, 'evaluations'])->name('evaluations');
    Route::get('/apps', [DudiDashboardController::class, 'apps'])->name('apps');
});

/*
|--------------------------------------------------------------------------
| Admin Panel Routes (Protected by auth and role:admin middleware)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // User Management (Teachers, DUDI, Students, Admins)
    Route::resource('users', AdminUserController::class);

    // Application Categories Management
    Route::resource('categories', AdminApplicationCategoryController::class);

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

    // Announcement Management
    Route::resource('announcements', AdminAnnouncementController::class);
    Route::patch('/announcements/{announcement}/toggle', [AdminAnnouncementController::class, 'toggleStatus'])->name('announcements.toggle');
});
