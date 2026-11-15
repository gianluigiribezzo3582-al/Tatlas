<?php

use App\Http\Controllers\Api\V1\ArtistController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClaimController;
use App\Http\Controllers\Api\V1\ManageStudioController;
use App\Http\Controllers\Api\V1\MyArtistController;
use App\Http\Controllers\Api\V1\MyPresenceController;
use App\Http\Controllers\Api\V1\RemovalRequestController;
use App\Http\Controllers\Api\V1\StudioController;
use App\Http\Controllers\Api\V1\StyleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::middleware('throttle:api')->group(function () {
        Route::get('studios', [StudioController::class, 'index'])->name('studios.index');
        Route::get('studios/search', [ManageStudioController::class, 'search'])->name('studios.search');
        Route::get('studios/{studio}', [StudioController::class, 'show'])->whereNumber('studio')->name('studios.show');
        Route::get('artists', [ArtistController::class, 'index'])->name('artists.index');
        Route::get('artists/{artist:slug}', [ArtistController::class, 'show'])->name('artists.show');
        Route::get('styles', [StyleController::class, 'index'])->name('styles.index');
    });

    Route::middleware('throttle:auth')->group(function () {
        Route::post('auth/register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('auth/login', [AuthController::class, 'login'])->name('auth.login');
        Route::post('removal-requests', [RemovalRequestController::class, 'store'])->name('removal-requests.store');
    });

    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');

        Route::get('me/artist', [MyArtistController::class, 'show'])->name('me.artist.show');
        Route::post('me/artist', [MyArtistController::class, 'store'])->name('me.artist.store');
        Route::patch('me/artist', [MyArtistController::class, 'update'])->name('me.artist.update');

        Route::post('me/presences', [MyPresenceController::class, 'store'])->name('me.presences.store');
        Route::delete('me/presences/{presence}', [MyPresenceController::class, 'destroy'])->name('me.presences.destroy');

        Route::get('me/claims', [ClaimController::class, 'index'])->name('me.claims.index');
        Route::post('studios/{studio}/claim', [ClaimController::class, 'storeForStudio'])->whereNumber('studio')->name('studios.claim');

        Route::post('studios', [ManageStudioController::class, 'store'])->name('studios.store');
        Route::patch('studios/{studio}', [ManageStudioController::class, 'update'])->whereNumber('studio')->name('studios.update');
    });
});
