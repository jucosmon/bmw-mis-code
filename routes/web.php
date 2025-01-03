<?php

use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\BarangayController;
use App\Http\Controllers\BarangayOfficialDashboardController;
use App\Http\Controllers\ManageAccount\BpemoAdminManageAccountsController;
use App\Http\Controllers\ManageAccount\LguResponderManageAccountController;
use App\Http\Controllers\BpemoAdminDashboardController;
use App\Http\Controllers\BpemoStaffDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LguResponderDashboardController;
use App\Http\Controllers\MunicipalityController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicUserDashboardController;
use App\Http\Controllers\SpeciesController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// default from inertia
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Profile routes (common for all authenticated users) default from inertia
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Role-specific capabilities
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/validate-password', [PasswordController::class, 'validatePassword'])->name('user.validatePassword');
    Route::get('/profile/view', [ProfileController::class, 'view'])->name('profile.view');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    //bpemo admin
    Route::prefix('bpemo-admin')->group(function(){
        Route::middleware('role:bpemo_admin')->group(function () {
            Route::prefix('manage-account')->group(function (){
                Route::get('/{type}', [BpemoAdminManageAccountsController::class, 'index'])
                ->name('bpemo.admin.manage.account.index');
                //creating another user
                Route::get('/{type}/create-page', [BpemoAdminManageAccountsController::class, 'createPage'])
                    ->name('bpemo.admin.manage.account.create.page');
                Route::post('/{type}/create', [BpemoAdminManageAccountsController::class, 'create'])
                    ->name('bpemo.admin.manage.account.create');
                //viewing another user
                Route::get('/view/{user_id}', [BpemoAdminManageAccountsController::class, 'view'])
                    ->name('bpemo.admin.manage.account.view');
                //updating another user
                Route::get('/{type}/update-page/{user_id}', [BpemoAdminManageAccountsController::class, 'updatePage'])
                    ->name('bpemo.admin.manage.account.update.page');
                Route::put('/{type}/update/{user_id}', [BpemoAdminManageAccountsController::class, 'update'])
                    ->name('bpemo.admin.manage.account.update');
                //disabling another user
                Route::put('/{type}/disable/{user_id}', [BpemoAdminManageAccountsController::class, 'disable'])
                    ->name('bpemo.admin.manage.account.disable');
                //activating another user account
                Route::put('/{type}/activate/{user_id}', [BpemoAdminManageAccountsController::class, 'activate'])
                    ->name('bpemo.admin.manage.account.activate');
            });

            // manage species (CRUD Functionality)
            Route::prefix('manage-species')->group(function (){
                Route::get('/{category}', [SpeciesController::class, 'index'])
                ->name('bpemo.admin.manage.species.index');
                //creating another user
                Route::get('/{category}/create-page', [SpeciesController::class, 'createPage'])
                    ->name('bpemo.admin.manage.species.create.page');
                Route::post('{category}/create', [SpeciesController::class, 'create'])
                    ->name('bpemo.admin.manage.species.create');
                //viewing another user
                Route::get('/view/{id}', [SpeciesController::class, 'view'])
                    ->name('bpemo.admin.manage.species.view');
                //updating another user
                Route::get('/update-page/{id}', [SpeciesController::class, 'updatePage'])
                    ->name('bpemo.admin.manage.species.update.page');
                Route::post('/update/{id}', [SpeciesController::class, 'update'])
                    ->name('bpemo.admin.manage.species.update');
                //disabling another user
                Route::patch('/{category}/archive/{id}', [SpeciesController::class, 'archive'])
                    ->name('bpemo.admin.manage.species.archive');
            });
        });
    });
    //bpemo staff user
    Route::prefix('bpemo-staff')->group(function(){
        Route::middleware('role:bpemo_staff')->group(function () {

        });
    });

    //lgu responder user
    Route::prefix('lgu-responder')->group(function(){
        Route::middleware('role:lgu_responder')->group(function () {

             // manage account use case
             Route::prefix('manage-account')->group(function (){
                Route::get('/barangay-official', [LguResponderManageAccountController::class, 'index'])
                ->name('lgu.responder.manage.account.index');
                //creating another user
                Route::get('/create-page/barangay-official', [LguResponderManageAccountController::class, 'createPage'])
                    ->name('lgu.responder.manage.account.create.page');
                Route::post('/create/barangay-official', [LguResponderManageAccountController::class, 'create'])
                    ->name('lgu.responder.manage.account.create');
                //viewing another user
                Route::get('/view/{user_id}', [LguResponderManageAccountController::class, 'view'])
                    ->name('lgu.responder.manage.account.view');
                //updating another user
                Route::get('/update-page/barangay-official/{user_id}', [LguResponderManageAccountController::class, 'updatePage'])
                    ->name('lgu.responder.manage.account.update.page');
                Route::put('/update/barangay-official/{user_id}', [LguResponderManageAccountController::class, 'update'])
                    ->name('lgu.responder.manage.account.update');
                //disabling another user
                Route::put('/disable/barangay-official/{user_id}', [LguResponderManageAccountController::class, 'disable'])
                    ->name('lgu.responder.manage.account.disable');
                //activating another barangay user account
                Route::put('/activate/barangay-official/{user_id}', [LguResponderManageAccountController::class, 'activate'])
                    ->name('lgu.responder.manage.account.activate');
            });
        });
    });

    //barangay official user
    Route::prefix('barangay-official')->group(function(){
        Route::middleware('role:barangay_official')->group(function () {

        });
    });

    // public user
    Route::prefix('public-user')->group(function(){
        Route::middleware('role:public_user')->group(function () {

        });
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

