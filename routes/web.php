<?php

use App\Http\Controllers\BatchProcessingCampaignController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\OnceOffCampaignContactController;
use App\Http\Controllers\OnceOffCampaignContactImportController;
use App\Http\Controllers\OnceOffCampaignController;
use App\Http\Controllers\OnceOffCampaignScheduleController;
use App\Http\Controllers\OnceOffCampaignStatusController;
use App\Http\Controllers\OngoingCampaignController;
use App\Http\Controllers\UserManagement\RoleController;
use App\Http\Controllers\UserManagement\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('campaigns', CampaignController::class)->except('destroy');

    Route::prefix('campaigns/{campaign}/once-off')->name('campaigns.once-off.')->group(function () {
        Route::get('/', [OnceOffCampaignController::class, 'show'])->name('show');
        Route::post('launch', [OnceOffCampaignStatusController::class, 'launch'])->name('launch');
        Route::post('pause', [OnceOffCampaignStatusController::class, 'pause'])->name('pause');
        Route::post('resume', [OnceOffCampaignStatusController::class, 'resume'])->name('resume');
        Route::post('cancel', [OnceOffCampaignStatusController::class, 'cancel'])->name('cancel');
        Route::post('stop', [OnceOffCampaignStatusController::class, 'stop'])->name('stop');
        Route::get('contacts', [OnceOffCampaignContactController::class, 'index'])->name('contacts.index');
        Route::get('contacts/{contact}', [OnceOffCampaignContactController::class, 'show'])->scopeBindings()->name('contacts.show');
        Route::get('contact-imports', [OnceOffCampaignContactImportController::class, 'index'])->name('contact-imports.index');
        Route::get('schedule', [OnceOffCampaignScheduleController::class, 'show'])->name('schedule.show');
        Route::put('schedule', [OnceOffCampaignScheduleController::class, 'update'])->name('schedule.update');
        Route::post('contacts', [OnceOffCampaignContactController::class, 'store'])->name('contacts.store');
        Route::post('contact-imports', [OnceOffCampaignContactImportController::class, 'store'])->name('contact-imports.store');
        Route::post('contact-imports/preview', [OnceOffCampaignContactImportController::class, 'preview'])->name('contact-imports.preview');
        Route::get('contact-imports/{contactImport}', [OnceOffCampaignContactImportController::class, 'show'])->scopeBindings()->name('contact-imports.show');
        Route::get('contact-imports/{contactImport}/download', [OnceOffCampaignContactImportController::class, 'download'])->scopeBindings()->name('contact-imports.download');
        Route::post('contact-imports/{contactImport}/sync', [OnceOffCampaignContactImportController::class, 'sync'])->scopeBindings()->name('contact-imports.sync');
    });

    Route::prefix('campaigns/{campaign}/ongoing')->name('campaigns.ongoing.')->group(function () {
        Route::get('/', [OngoingCampaignController::class, 'show'])->name('show');
    });

    Route::prefix('campaigns/{campaign}/batch-processing')->name('campaigns.batch-processing.')->group(function () {
        Route::get('/', [BatchProcessingCampaignController::class, 'show'])->name('show');
    });

    Route::prefix('user-management')->name('user-management.')->group(function () {
        Route::resource('users', UserController::class)->except('show');
        Route::resource('roles', RoleController::class)->except('show');
    });
});

require __DIR__.'/settings.php';
