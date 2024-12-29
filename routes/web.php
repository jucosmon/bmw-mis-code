<?php

use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\BarangayController;
use App\Http\Controllers\BarangayOfficialDashboardController;
use App\Http\Controllers\BpemoAdmin\ManageAccountsController;
use App\Http\Controllers\BpemoAdminDashboardController;
use App\Http\Controllers\BpemoStaffDashboardController;
use App\Http\Controllers\LguResponderDashboardController;
use App\Http\Controllers\MunicipalityController;
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
    Route::post('/validate-password', [PasswordController::class, 'validatePassword'])->name('user.validatePassword');

    //bpemo admin
    Route::prefix('bpemo-admin')->group(function(){
        Route::middleware('role:bpemo_admin')->group(function () {
            //admin dashboard
            Route::get('/dashboard', [BpemoAdminDashboardController::class, 'index'])
                ->name('bpemo.admin.dashboard');
            //admin manage account use case
            Route::prefix('manage-accounts')->group(function (){
                Route::get('/{type}', [ManageAccountsController::class, 'index'])
                ->name('bpemo.admin.manage.account.index');
                //creating user
                Route::get('/create-page/{type}', [ManageAccountsController::class, 'createPage'])
                    ->name('bpemo.admin.manage.account.create.page');
                Route::post('/create/{type}', [ManageAccountsController::class, 'create'])
                    ->name('bpemo.admin.manage.account.create');
                //viewing user info
                Route::get('/view/{user_id}', [ManageAccountsController::class, 'view'])
                    ->name('bpemo.admin.manage.account.view');
                //updating user info
                Route::get('/update-page/{type}/{user_id}', [ManageAccountsController::class, 'updatePage'])
                    ->name('bpemo.admin.manage.account.update.page');
                Route::put('/update/{type}/{user_id}', [ManageAccountsController::class, 'update'])
                    ->name('bpemo.admin.manage.account.update');
                //disabling user account

            });
        });
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

//barangay and municipality sample fetching of data
Route::get('/municipalities', [MunicipalityController::class, 'index']);
Route::get('/barangays', [BarangayController::class, 'index']);

// Unauthorized route for role mismatch
Route::get('/unauthorized', function () {
    return Inertia::render('Errors/Unauthorized'); // Create an Inertia page for this
})->name('unauthorized');

// Include authentication routes
require __DIR__.'/auth.php';

Route::get('/test', function() {
    return 'Test route works!';
});

