<?php

use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LeakReportController;
use App\Http\Controllers\Admin\VideoController;
use App\Http\Controllers\Admin\VideoUploadController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\PosterController;
use App\Http\Controllers\StreamController;
use App\Http\Controllers\VerifyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public - the library is open
|--------------------------------------------------------------------------
| These are marketing videos. Locking them behind a login would defeat their
| purpose. Protection is the burned watermark, the forensic overlay, the
| signed short-lived URLs, and the verify code.
*/
Route::get('/', fn () => response()->file(public_path('landing.html'), ['Content-Type'=>'text/html; charset=UTF-8']))->name('landing');

Route::get('/api/videos', [LibraryController::class, 'index'])->name('library.index');
Route::get('/api/categories', [LibraryController::class, 'categories'])->name('library.categories');
Route::get('/poster/{video}', [PosterController::class, 'show'])->name('poster.show');
Route::post('/watch/{slug}/open', [LibraryController::class, 'open'])
    ->middleware('throttle:40,1')->name('library.open');

Route::get('/verify/{code}', [VerifyController::class, 'show'])->name('verify.show');

// Signed and browser-bound. Not access control - anti-hotlink and anti-ripping.
Route::get('/hls/{video}/manifest', [StreamController::class, 'manifest'])->name('stream.manifest');
Route::get('/hls/{video}/key', [StreamController::class, 'key'])->name('stream.key');
Route::get('/hls/{video}/{rendition}/{file}', [StreamController::class, 'segment'])
    ->where(['rendition' => '\d+', 'file' => 'seg_\d{5}\.ts'])
    ->name('stream.segment');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth','can:manage-content'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', fn () => response()->file(public_path('admin.html'), ['Content-Type'=>'text/html; charset=UTF-8']))->name('dashboard');

    Route::get('/api/overview', [DashboardController::class, 'overview'])->name('overview');

    Route::get('/api/videos',                [VideoController::class, 'index'])->name('videos.index');
    Route::get('/api/videos/{video}',        [VideoController::class, 'show'])->name('videos.show');
    Route::patch('/api/videos/{video}',      [VideoController::class, 'update'])->name('videos.update');
    Route::delete('/api/videos/{video}',     [VideoController::class, 'destroy'])->name('videos.destroy');
    Route::post('/api/videos/{video}/publish',   [VideoController::class, 'publish'])->name('videos.publish');
    Route::post('/api/videos/{video}/unpublish', [VideoController::class, 'unpublish'])->name('videos.unpublish');
    Route::post('/api/videos/{video}/retry',     [VideoController::class, 'retry'])->name('videos.retry');
    Route::post('/api/videos/reorder',           [VideoController::class, 'reorder'])->name('videos.reorder');

    Route::get('/api/categories',               [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/api/categories',              [CategoryController::class, 'store'])->name('categories.store');
    Route::patch('/api/categories/{category}',  [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/api/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    Route::post('/api/categories/reorder',      [CategoryController::class, 'reorder'])->name('categories.reorder');

    Route::post('/api/videos/{video}/poster',       [VideoController::class, 'poster'])->name('videos.poster');
    Route::post('/api/videos/{video}/poster/reset', [VideoController::class, 'resetPoster'])->name('videos.poster.reset');
    Route::get('/api/brand',  [BrandController::class, 'show'])->name('brand.show');
    Route::post('/api/brand', [BrandController::class, 'update'])->name('brand.update');

    Route::post('/api/leaks',                  [LeakReportController::class, 'store'])->name('leaks.store');
    Route::patch('/api/leaks/{leakReport}',    [LeakReportController::class, 'update'])->name('leaks.update');
    Route::get('/api/leaks/{leakReport}/evidence', [LeakReportController::class, 'evidence'])->name('leaks.evidence');

    Route::post('/uploads', [VideoUploadController::class, 'open'])->name('uploads.open');
    Route::put('/uploads/{uuid}/{index}', [VideoUploadController::class, 'chunk'])
        ->where('index', '\d+')->name('uploads.chunk');
    Route::get('/uploads/{uuid}', [VideoUploadController::class, 'status'])->name('uploads.status');
    Route::post('/uploads/{uuid}/complete', [VideoUploadController::class, 'complete'])->name('uploads.complete');
});

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
| Login only. There is no registration route by design - accounts are
| created from the CLI, because a public signup form on an admin panel
| is a liability rather than a feature.
*/
Route::get('/login',  [\App\Http\Controllers\AuthController::class, 'show'])->name('login');
Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])
    ->middleware('throttle:10,1')->name('login.attempt');
Route::post('/logout',[\App\Http\Controllers\AuthController::class, 'logout'])->name('logout');