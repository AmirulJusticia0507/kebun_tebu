<?php

use App\Http\Controllers\Admin\BlockController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportStatusController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Landing page → redirect ke login jika belum auth, atau ke /map
Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('map')
        : Inertia::render('Welcome', [
            'canLogin'    => Route::has('login'),
            'canRegister' => Route::has('register'),
        ]);
});

// ─── Authenticated routes ─────────────────────────────────────────────────────
Route::middleware(['auth', 'verified'])->group(function () {

    // Peta monitoring (semua user)
    Route::get('/map', [MapController::class, 'index'])->name('map');

    // Dashboard statistik (admin-only & fallback name)
    Route::get('/dashboard', function () {
        if (Auth::user()?->role === 'admin') {
            return app(DashboardController::class)->index();
        }
        return redirect()->route('map');
    })->name('dashboard');
    Route::get('/admin/dashboard', [DashboardController::class, 'index'])->middleware('role:admin')->name('admin.dashboard');

    // Form laporan & offline sync (field_officer & admin)
    Route::get('/reports/create', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
    Route::post('/reports/sync', [ReportController::class, 'sync'])->name('reports.sync');
    Route::get('/reports/export/geojson', [ReportController::class, 'exportGeoJson'])->name('reports.export.geojson');
    Route::get('/reports/export/csv', [ReportController::class, 'exportCsv'])->name('reports.export.csv');
    Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');

    // Notification center
    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    // Update status laporan (admin)
    Route::patch('/reports/{report}/status', [ReportStatusController::class, 'update'])->name('reports.status');

    // ─── Admin-only management routes ──────────────────────────────────────────
    Route::middleware('role:admin')->prefix('dashboard')->name('admin.')->group(function () {

        // Manajemen Pengguna / Petugas
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Manajemen Kategori
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        // Manajemen Blok Kebun
        Route::get('/blocks', [BlockController::class, 'index'])->name('blocks.index');
        Route::post('/blocks', [BlockController::class, 'store'])->name('blocks.store');
        Route::put('/blocks/{block}', [BlockController::class, 'update'])->name('blocks.update');
        Route::delete('/blocks/{block}', [BlockController::class, 'destroy'])->name('blocks.destroy');
    });

    // GeoJSON endpoint (all authenticated)
    Route::get('/api/blocks/geojson', [BlockController::class, 'geojson'])->name('blocks.geojson');
});

require __DIR__ . '/auth.php';