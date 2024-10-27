<?php

use App\Http\Controllers\Api\Auth\User\UserAuthController;
use App\Http\Middleware\AuthenticateUser;
use Illuminate\Support\Facades\Route;

Route::prefix('auth/user')->group(function () {
    Route::post('login', [UserAuthController::class, 'login'])->name('login');
    Route::post('register', [UserAuthController::class, 'register']);

    Route::middleware(AuthenticateUser::class)->group(function () { // Applying user middleware
        Route::post('logout', [UserAuthController::class, 'logout']);
        Route::get('me', [UserAuthController::class, 'me']);
    });
});
