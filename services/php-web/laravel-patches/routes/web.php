<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OsdrController;
use App\Http\Controllers\ProxyController;
use App\Http\Controllers\AstroController;
use App\Http\Controllers\CmsController;

Route::get('/', fn() => redirect('/dashboard'));

// Панели
Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/osdr', [OsdrController::class, 'index']);

// Прокси к rust_iss
Route::get('/api/iss/last', [ProxyController::class, 'last']);
Route::get('/api/iss/trend', [ProxyController::class, 'trend']);

// JWST галерея (JSON)
Route::get('/api/jwst/feed', [DashboardController::class, 'jwstFeed']);

// Astronomy API
Route::get('/api/astro/events', [AstroController::class, 'events']);

// CMS Pages
Route::get('/page/{slug}', [CmsController::class, 'page']);
