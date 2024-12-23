<?php

use App\Http\Controllers\BarangayOfficialDashboardController;
use App\Http\Controllers\BpemoAdminDashboardController;
use App\Http\Controllers\BpemoStaffDashboardController;
use App\Http\Controllers\LguResponderDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicUserDashboardController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});


// Profile routes (common for all authenticated users)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


// Role-specific dashboards
Route::middleware(['auth'])->group(function () {
    Route::middleware('role:bpemo_admin')->group(function () {
        Route::get('/bpemo-admin/dashboard', [BpemoAdminDashboardController::class, 'index'])
            ->name('bpemo.admin.dashboard');
    });

    Route::middleware('role:bpemo_staff')->group(function () {
        Route::get('/bpemo-staff/dashboard', [BpemoStaffDashboardController::class, 'index'])
            ->name('bpemo.staff.dashboard');
    });

    Route::middleware('role:lgu_responder')->group(function () {
        Route::get('/lgu-responder/dashboard', [LguResponderDashboardController::class, 'index'])
            ->name('lgu.responder.dashboard');
    });

    Route::middleware('role:barangay_official')->group(function () {
        Route::get('/barangay-official/dashboard', [BarangayOfficialDashboardController::class, 'index'])
            ->name('barangay.official.dashboard');
    });

    Route::middleware('role:public_user')->group(function () {
        Route::get('/public-user/dashboard', [PublicUserDashboardController::class, 'index'])
            ->name('public.user.dashboard');
    });
});

// Unauthorized route for role mismatch
Route::get('/unauthorized', function () {
    return Inertia::render('Errors/Unauthorized'); // Create an Inertia page for this
})->name('unauthorized');

// Include authentication routes
require __DIR__.'/auth.php';

Route::get('/test', function() {
    return 'Test route works!';
});

