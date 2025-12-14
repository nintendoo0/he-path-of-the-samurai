<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IssController;
use App\Http\Controllers\OsdrController;
use App\Http\Controllers\ProxyController;
use App\Http\Controllers\AstroController;
use App\Http\Controllers\AstronomyController;
use App\Http\Controllers\CmsController;
use App\Http\Controllers\TelemetryController;

Route::get('/', fn() => redirect('/dashboard'));

// Панели
Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/iss', [IssController::class, 'index']);
Route::get('/osdr', [OsdrController::class, 'index']);
Route::get('/astronomy', [AstronomyController::class, 'index']);

// Telemetry (Pascal Legacy CSV)
Route::get('/telemetry', [TelemetryController::class, 'index'])->name('telemetry');
Route::get('/telemetry/api', [TelemetryController::class, 'api'])->name('telemetry.api');
Route::get('/telemetry/export', [TelemetryController::class, 'export'])->name('telemetry.export');

// Страница с примерами дизайна
Route::view('/design-examples', 'design-examples');

// Прокси к rust_iss
Route::get('/api/iss/last', [ProxyController::class, 'last']);
Route::get('/api/iss/trend', [ProxyController::class, 'trend']);
Route::get('/api/iss/history', [ProxyController::class, 'history']);

// JWST галерея (JSON)
Route::get('/api/jwst/feed', [DashboardController::class, 'jwstFeed']);

// Astronomy API
Route::get('/api/astro/events', [AstroController::class, 'events']);

// CMS Pages
Route::get('/page/{slug}', [CmsController::class, 'page']);
