<?php

use App\Http\Controllers\Api\ApiIdentityController;
use App\Http\Controllers\OAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Downstream Webhook User Sync Route (SiPintu SSO)
|--------------------------------------------------------------------------
*/
Route::post('/sipintu/sync-user', [OAuthController::class, 'syncUser'])->name('api.sipintu.sync_user');

/*
|--------------------------------------------------------------------------
| Public Gateway Validation & Health Ping Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    Route::get('/ping', [ApiIdentityController::class, 'ping'])->name('api.v1.ping');
    Route::get('/health', [ApiIdentityController::class, 'ping']);
    Route::post('/validate-client', [ApiIdentityController::class, 'validateClientCredentials'])->name('api.v1.validate_client');

    // Jurusan Public Endpoints (Accessible for Downstream Client Applications)
    Route::get('/jurusans', [ApiIdentityController::class, 'jurusans'])->name('api.v1.jurusans');
    Route::get('/jurusans/{kode}', [ApiIdentityController::class, 'jurusanDetail'])->name('api.v1.jurusan_detail');
    Route::get('/jurusans/{kode}/alumni', [ApiIdentityController::class, 'alumni'])->name('api.v1.jurusan_alumni');

    // Alumni Endpoints for Downstream Client Applications (Tracer Study, Portal Alumni, BKK, dsb)
    Route::get('/alumni', [ApiIdentityController::class, 'alumni'])->name('api.v1.alumni');
    Route::get('/alumni/{identifier}', [ApiIdentityController::class, 'alumniDetail'])->name('api.v1.alumni_detail');
});

/*
|--------------------------------------------------------------------------
| API Identity Gateway Routes (Protected by OAuth Bearer Token or Client Secret)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware('oauth.bearer')->group(function () {
    Route::get('/gateway/status', [ApiIdentityController::class, 'gatewayStatus'])->name('api.v1.gateway.status');

    Route::get('/user', [ApiIdentityController::class, 'user'])->name('api.v1.user');
    Route::get('/user/profile', [ApiIdentityController::class, 'profile'])->name('api.v1.user.profile');
    Route::get('/user/roles', [ApiIdentityController::class, 'roles'])->name('api.v1.user.roles');
    Route::match(['get', 'post'], '/user/password-sync', [ApiIdentityController::class, 'passwordSync'])->name('api.v1.user.password_sync');

    // Gateway Proxy API for SIJUNA Data Access
    Route::get('/sijuna/students', [ApiIdentityController::class, 'students'])->name('api.v1.sijuna.students');
    Route::get('/sijuna/students/{externalId}', [ApiIdentityController::class, 'studentDetail'])->name('api.v1.sijuna.student_detail');
    Route::get('/sijuna/teachers', [ApiIdentityController::class, 'teachers'])->name('api.v1.sijuna.teachers');
    Route::get('/sijuna/teachers/{externalId}', [ApiIdentityController::class, 'teacherDetail'])->name('api.v1.sijuna.teacher_detail');
});


