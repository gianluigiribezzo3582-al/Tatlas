<?php

use App\Http\Controllers\Api\V1\ArtistController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\StudioController;
use App\Http\Controllers\Api\V1\StyleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::middleware('throttle:api')->group(function () {
        Route::get('studios', [StudioController::class, 'index'])->name('studios.index');
        Route::get('studios/{studio}', [StudioController::class, 'show'])->whereNumber('studio')->name('studios.show');
        Route::get('artists', [ArtistController::class, 'index'])->name('artists.index');
        Route::get('artists/{artist:slug}', [ArtistController::class, 'show'])->name('artists.show');
        Route::get('styles', [StyleController::class, 'index'])->name('styles.index');
    });

    Route::middleware('throttle:auth')->prefix('auth')->name('auth.')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('login', [AuthController::class, 'login'])->name('login');
    });

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');
    });
});
