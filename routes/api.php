<?php

use App\Http\Controllers\Admin\BlockController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Kebun Tebu MVP
|--------------------------------------------------------------------------
*/

// Public Authentication API (Sanctum Bearer Tokens / OAuth)
Route::post('/v1/auth/token', [AuthController::class, 'issueToken'])->name('api.v1.auth.token');

// Protected API endpoints
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Current user info
    Route::get('/auth/me', [AuthController::class, 'me'])->name('api.v1.auth.me');
    Route::post('/auth/logout', [AuthController::class, 'revokeToken'])->name('api.v1.auth.logout');

    // GeoJSON & Block API
    Route::get('/blocks/geojson', [BlockController::class, 'geojson'])->name('api.v1.blocks.geojson');

    // Report Offline Sync API
    Route::post('/reports/sync', [ReportController::class, 'sync'])->name('api.v1.reports.sync');
});
