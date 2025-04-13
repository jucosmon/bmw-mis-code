<?php

use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\BarangayController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ManageAccount\BpemoAdminManageAccountsController;
use App\Http\Controllers\ManageAccount\LguResponderManageAccountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GenerateReportController;
use App\Http\Controllers\GuidelineController;
use App\Http\Controllers\MunicipalityController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RespondActionController;
use App\Http\Controllers\SightingController;
use App\Http\Controllers\SpeciesController;
use App\Http\Controllers\StrandedIncidentController;
use App\Http\Controllers\StrandedSpeciesController;
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
Route::middleware(['auth', 'verified', 'active', 'notRestricted'])->group(function () {
    Route::post('/validate-password', [PasswordController::class, 'validatePassword'])->name('user.validatePassword');
    Route::get('/profile/view', [ProfileController::class, 'view'])->name('profile.view');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/notifications', [NotificationController::class, 'index']); // Fetch notifications
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']); // Mark as read
        Route::get('/stranded-incidents/{id}/status', action: [StrandedIncidentController::class, 'getStrandedIncidentStatus']);
    });

    //manage stranded incident use case
    Route::prefix('stranded-incident')->group(function () {
        // index
        Route::middleware(['role:bpemo_admin,bpemo_staff,lgu_responder,barangay_official'])->get('/responder/update-page/{id}', [StrandedIncidentController::class, 'updateResponderPage'])
            ->name('stranded.incident.responder.update.page');

        Route::middleware(['role:bpemo_admin,bpemo_staff,lgu_responder,barangay_official'])->patch('/stranded-incident/{id}/false', [StrandedIncidentController::class, 'markAsFalse'])
            ->name('stranded.incident.false');

        // complete
        Route::middleware(['role:bpemo_admin,bpemo_staff,lgu_responder'])->patch('/complete/{id}', [StrandedIncidentController::class, 'complete'])
            ->name('stranded.incident.complete');

        // resolve
        Route::middleware(['role:bpemo_admin,bpemo_staff'])->patch('/resolve/{id}', [StrandedIncidentController::class, 'resolve'])
            ->name('stranded.incident.resolve');
        Route::middleware(['role:bpemo_admin,bpemo_staff'])->patch('/unresolve/{id}', [StrandedIncidentController::class, 'unresolve'])
            ->name('stranded.incident.unresolve');
        Route::middleware(['role:bpemo_admin,bpemo_staff,lgu_responder,barangay_official'])->get('/resolved-incidents', [StrandedIncidentController::class, 'indexResolvedIncidents'])
            ->name('resolved.incidents.index');

        // respond actions inside a specific stranded incident
        Route::middleware(['role:bpemo_admin,bpemo_staff,lgu_responder,barangay_official'])->post('/respond', action: [RespondActionController::class, 'respond'])
        ->name('stranded.incident.respond');


        // detailed species form inside a specific stranded incident
        Route::middleware(['role:bpemo_admin,bpemo_staff,lgu_responder'])->prefix('stranded-species')->group(function () {
            Route::get('/createPage/{id}', [StrandedSpeciesController::class, 'createPage'])
                ->name('stranded.species.createPage');
            Route::post('/create/{id}', [StrandedSpeciesController::class, 'create'])
                ->name('stranded.species.create');
            Route::get('/view/{id}', [StrandedSpeciesController::class, 'view'])
                ->name('stranded.species.view');
            Route::get('/update-page/{id}', [StrandedSpeciesController::class, 'updatePage'])
                ->name('stranded.species.update.page');
            Route::post('/update/{id}', [StrandedSpeciesController::class, 'update'])
                ->name('stranded.species.update');
            Route::patch('/archive/{id}', [StrandedSpeciesController::class, 'archive'])
                ->name('stranded.species.archive');
            Route::patch('/unarchive/{id}', [StrandedSpeciesController::class, 'unarchive'])
                ->name('stranded.species.unarchive');
        });
    });


    // manage sightings
    Route::prefix('sighting')->group(function () {
        // index
        Route::middleware(['role:bpemo_admin,bpemo_staff'])->patch('/unverify/{id}', [SightingController::class, 'unverify'])
            ->name('sighting.unverify');
        Route::get('/finished', [SightingController::class, 'indexFinishedSightings'])
            ->name('sighting.finished.index');
    });

    Route::prefix('guideline')->group(function (){

        // MANAGING GUIDELINES MAINLY BY BPEMO ADMIN
        Route::middleware(['role:bpemo_admin'])->group(function () {
            //creating another guideline
            Route::get('/{user_role}/create-page', [GuidelineController::class, 'createPage'])
                ->name('manage.guideline.createPage');
            Route::post('{user_role}/create', [GuidelineController::class, 'create'])
                ->name('manage.guideline.create');
            //viewing another guideline
            Route::get('/view/{id}', [GuidelineController::class, 'view'])
                ->name('manage.guideline.view');
            //updating another guideline
            Route::get('/update-page/{id}', [GuidelineController::class, 'updatePage'])
                ->name('manage.guideline.updatePage');
            Route::post('/update/{id}', [GuidelineController::class, 'update'])
                ->name('manage.guideline.update');
            //archiving another guideline
            Route::patch('/archive/{id}', [GuidelineController::class, 'archive'])
                ->name('manage.guideline.archive');
            //unarchiving another guideline
            Route::patch('/unarchive/{id}', [GuidelineController::class, 'unarchive'])
                ->name('manage.guideline.unarchive');
            // Index route should be last
            Route::get('/{user_role}/{archived}', [GuidelineController::class, 'index'])
                ->name('manage.guideline.index');
            });
    });

    Route::prefix('generate-report')->group(function () {
        Route::get('/cluster-map', [GenerateReportController::class, 'clusterMapIndex'])
            ->name('generate.report.cluster.map');
        Route::get('/summary-report', [GenerateReportController::class, 'summaryReportIndex'])
            ->name('generate.report.summary.report');
    });
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

                //creating another species
                Route::get('/create-page', [SpeciesController::class, 'createPage'])
                    ->name('bpemo.admin.manage.species.create.page');
                Route::post('/create', [SpeciesController::class, 'create'])
                    ->name('bpemo.admin.manage.species.create');
                //updating another species
                Route::get('/update-page/{id}', [SpeciesController::class, 'updatePage'])
                    ->name('bpemo.admin.manage.species.update.page');
                Route::post('/update/{id}', [SpeciesController::class, 'update'])
                    ->name('bpemo.admin.manage.species.update');
                //archiving another species
                Route::patch('/archive/{id}', [SpeciesController::class, 'archive'])
                    ->name('bpemo.admin.manage.species.archive');
                //unarchiving another species
                Route::patch('/unarchive/{id}', [SpeciesController::class, 'unarchive'])
                    ->name('bpemo.admin.manage.species.unarchive');
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


Route::prefix('access')->group(function(){
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('stranded-incident')->group(function () {
        // index
        Route::get('/', [StrandedIncidentController::class, 'index'])->name('stranded.incident.index');
        //creating another stranded incident
        Route::get('/create-page', [StrandedIncidentController::class, 'createPage'])
        ->name('stranded.incident.createPage');
        Route::post('/create', [StrandedIncidentController::class, 'create'])
            ->name('stranded.incident.create');
        //viewing another stranded incident
        Route::get('/view/{id}', [StrandedIncidentController::class, 'view'])
            ->name('stranded.incident.view');
        //updating another stranded incident
        Route::get('/public-user/update-page/{id}', [StrandedIncidentController::class, 'updatePage'])
            ->name('stranded.incident.update.page');
        Route::post('/update/{id}', [StrandedIncidentController::class, 'update'])
            ->name('stranded.incident.update');

        //archiving another stranded incident
        Route::patch('/archive/{id}', [StrandedIncidentController::class, 'archive'])
            ->name('stranded.incident.archive');
        //unarchiving another stranded incident
        Route::patch('/unarchive/{id}', [StrandedIncidentController::class, 'unarchive'])
            ->name('stranded.incident.unarchive');


        // comments inside a specific stranded incident
        Route::prefix('comments')->group(function () {
            Route::post('/create', [CommentController::class, 'create'])
                ->name('stranded.incident.comment.create');

            Route::patch('/update/{comment}', [CommentController::class, 'update'])
                ->name('stranded.incident.comment.update');

            Route::patch('/archive/{comment}', [CommentController::class, 'archive'])
                ->name('stranded.incident.comment.archive');
        });
    });


    // manage sightings
    Route::prefix('sighting')->group(function () {
        // index
        Route::get('/', [SightingController::class, 'index'])->name('sighting.index');
        //create
        Route::get('/create-page', [SightingController::class, 'createPage'])
        ->name('sighting.createPage');
        Route::post('/create', [SightingController::class, 'create'])
            ->name('sighting.create');
        //view
        Route::get('/view/{id}', [SightingController::class, 'view'])
            ->name('sighting.view');
        //update
        Route::get('/update-page/{id}', [SightingController::class, 'updatePage'])
            ->name('sighting.update.page');
        Route::post('/update/{id}', [SightingController::class, 'update'])
            ->name('sighting.update');
        //archiving another stranded incident
        Route::patch('/archive/{id}', [SightingController::class, 'archive'])
            ->name('sighting.archive');
        //unarchiving another stranded incident
        Route::patch('/unarchive/{id}', [SightingController::class, 'unarchive'])
            ->name('sighting.unarchive');
        Route::get('/finished', [SightingController::class, 'indexFinishedSightings'])
            ->name('sighting.finished.index');
    });

    Route::prefix('guideline')->group(function (){
            Route::get('/view/{id}', action: [GuidelineController::class, 'viewForBasicUser'])
                ->name('guideline.view');
            Route::get('/', [GuidelineController::class, 'indexForBasicUser'])
                ->name('guideline.index');
    });

    Route::prefix('species')->group(function () {
        Route::get('/', [SpeciesController::class, 'index'])
            ->name('species.index');
        Route::get('/search', [SpeciesController::class, 'search'])
            ->name('species.search');
        Route::get('/result', [SpeciesController::class, 'resultPage'])
            ->name('species.result');
        Route::get('/view/{id}', [SpeciesController::class, 'view'])
            ->name('species.view');
    });


    Route::get('/public/track', function () {
        return Inertia::render('Public/TrackReport');
    })->name('public.track');

    Route::post('/public/track', [StrandedIncidentController::class, 'publicTrack'])
        ->name('public.track.submit');
});
