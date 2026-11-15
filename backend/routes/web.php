<?php

use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Web\PublicPageController;
use App\Http\Controllers\Web\RemovalFormController;
use App\Livewire\Admin\Dashboard;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json(['name' => 'Tatlas API', 'docs' => '/api/v1']));

// Public, indexable pages (same paths are handled by the app through App Links).
Route::get('artisti/{slug}', [PublicPageController::class, 'artist'])->where('slug', '[a-z0-9-]+')->name('public.artist');
Route::get('studi/{ref}', [PublicPageController::class, 'studio'])->name('public.studio');
Route::get('sitemap.xml', [PublicPageController::class, 'sitemap'])->name('public.sitemap');
Route::get('.well-known/assetlinks.json', [PublicPageController::class, 'assetLinks'])->name('public.assetlinks');
Route::get('robots.txt', fn () => response(
    "User-agent: *\nDisallow: /admin\nDisallow: /api\nSitemap: ".PublicPageController::base()."/sitemap.xml\n",
)->header('Content-Type', 'text/plain'));
Route::view('privacy', 'public.privacy', [
    'updated' => '15/11/2026',
    'controller' => config('tatlas.privacy.controller'),
    'email' => config('tatlas.privacy.email'),
])->name('public.privacy');
Route::get('rimozione', [RemovalFormController::class, 'show'])->name('public.removal');
Route::post('rimozione', [RemovalFormController::class, 'store'])->middleware('throttle:auth')->name('public.removal.store');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'show'])->name('login');
        Route::post('login', [LoginController::class, 'login'])->middleware('throttle:auth');
    });
    Route::post('logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');
    Route::get('/', Dashboard::class)->middleware(['auth', 'can:admin'])->name('dashboard');
});
