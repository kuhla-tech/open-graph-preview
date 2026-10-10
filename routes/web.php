<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\PreviewController;
use App\Http\Controllers\SearchDiscoveryController;
use App\Http\Middleware\RequireAdmin;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::pattern('locale', 'de|en');

Route::middleware(SetLocale::class)->prefix('{locale}')->group(function () {
    Route::view('/', 'welcome')->name('home');
    Route::view('/impressum', 'legal.imprint')->name('imprint');
    Route::view('/datenschutz', 'legal.privacy')->name('privacy');
    Route::get('/app.webmanifest', function () {
        $manifest = json_decode(file_get_contents(public_path('site.webmanifest')), true);
        $manifest['name'] = config('app.name');
        $manifest['short_name'] = config('app.name');
        $manifest['lang'] = app()->getLocale();
        if (config('branding.icon_192') && config('branding.icon_512')) {
            $manifest['icons'] = [
                ['src' => url(config('branding.icon_192')), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => url(config('branding.icon_512')), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ];
        } else {
            foreach ($manifest['icons'] as &$icon) {
                $icon['src'] = asset(ltrim($icon['src'], '/'));
            }
            unset($icon);
        }
        $manifest['description'] = __('Link-Vorschauen und Social-Metadaten prüfen.');
        $manifest['start_url'] = '/'.app()->getLocale().'/';
        $manifest['scope'] = '/';
        $manifest['id'] = '/';

        return response()->json($manifest)->header('Content-Type', 'application/manifest+json')->header('Cache-Control', 'no-store');
    })->name('manifest');
    Route::post('/preview', PreviewController::class)->middleware('throttle:30,1')->name('preview');
});

Route::middleware(SetLocale::class)->group(function () {
    foreach (['/', '/impressum', '/datenschutz', '/app.webmanifest'] as $path) {
        Route::get($path, fn () => abort(404));
    }
    Route::post('/preview', PreviewController::class)->middleware('throttle:30,1');
});

Route::get('/sitemap.xml', [SearchDiscoveryController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SearchDiscoveryController::class, 'robots'])->name('robots');

Route::get('/admin', fn () => redirect('/admin/settings/general'))->middleware(['auth', 'auth.session', RequireAdmin::class])->name('admin');
Route::middleware(['auth', 'auth.session', RequireAdmin::class])->prefix('admin')->group(function () {
    Route::get('/settings/{section}', [AdminController::class, 'settings'])->name('admin.settings');
    Route::post('/settings/{section}', [AdminController::class, 'update'])->middleware('password.confirm')->name('admin.settings.update');
    Route::get('/security', [AdminController::class, 'security'])->middleware('password.confirm')->name('admin.security');
    Route::post('/password', [AdminController::class, 'password'])->middleware(['password.confirm', 'throttle:5,1'])->name('admin.password');
});
Route::get('/admin/setup/{user}', [AdminController::class, 'setup'])->middleware(['signed', 'throttle:5,1'])->name('admin.setup');
Route::post('/admin/setup/{user}', [AdminController::class, 'completeSetup'])->middleware(['signed', 'throttle:5,1']);
Route::get('/media/branding/{filename}', function (string $filename) {
    abort_unless(preg_match('/^[a-f0-9-]+(?:-[a-z0-9_]+)?\.(png|ico)$/', $filename), 404);
    $path = Storage::disk('public')->path('branding/'.$filename);
    abort_unless(is_file($path), 404);

    return response()->file($path, ['Cache-Control' => 'public, max-age=31536000, immutable', 'X-Content-Type-Options' => 'nosniff']);
})->name('branding.media');
