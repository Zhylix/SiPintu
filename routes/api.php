<?php

use App\Http\Controllers\Api\ApiIdentityController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Identity Gateway Routes (Protected by OAuth Bearer Token)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->middleware('oauth.bearer')->group(function () {
    Route::get('/user', [ApiIdentityController::class, 'user'])->name('api.v1.user');
    Route::get('/user/profile', [ApiIdentityController::class, 'profile'])->name('api.v1.user.profile');
    Route::get('/user/roles', [ApiIdentityController::class, 'roles'])->name('api.v1.user.roles');

    // Gateway Proxy API for SIJUNA Data Access
    Route::get('/sijuna/students', [ApiIdentityController::class, 'students'])->name('api.v1.sijuna.students');
    Route::get('/sijuna/students/{externalId}', [ApiIdentityController::class, 'studentDetail'])->name('api.v1.sijuna.student_detail');
});

