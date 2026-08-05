<?php

use App\Http\Controllers\Api\ApiIdentityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Gateway Validation & Health Ping Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    Route::get('/ping', [ApiIdentityController::class, 'ping'])->name('api.v1.ping');
    Route::get('/health', [ApiIdentityController::class, 'ping']);
    Route::post('/validate-client', [ApiIdentityController::class, 'validateClientCredentials'])->name('api.v1.validate_client');
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

    // Gateway Proxy API for SIJUNA Data Access
    Route::get('/sijuna/students', [ApiIdentityController::class, 'students'])->name('api.v1.sijuna.students');
    Route::get('/sijuna/students/{externalId}', [ApiIdentityController::class, 'studentDetail'])->name('api.v1.sijuna.student_detail');
    Route::get('/sijuna/teachers', [ApiIdentityController::class, 'teachers'])->name('api.v1.sijuna.teachers');
    Route::get('/sijuna/teachers/{externalId}', [ApiIdentityController::class, 'teacherDetail'])->name('api.v1.sijuna.teacher_detail');
});
