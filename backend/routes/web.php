<?php

use App\Http\Controllers\Admin\LoginController;
use App\Livewire\Admin\Dashboard;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json(['name' => 'Tatlas API', 'docs' => '/api/v1']));

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'show'])->name('login');
        Route::post('login', [LoginController::class, 'login'])->middleware('throttle:auth');
    });
    Route::post('logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');
    Route::get('/', Dashboard::class)->middleware(['auth', 'can:admin'])->name('dashboard');
});
