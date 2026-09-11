<?php

use App\Http\Controllers\BatchProcessingCampaignController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\OnceOffCampaignContactController;
use App\Http\Controllers\OnceOffCampaignContactImportController;
use App\Http\Controllers\OnceOffCampaignController;
use App\Http\Controllers\OngoingCampaignController;
use App\Http\Controllers\UserManagement\RoleController;
use App\Http\Controllers\UserManagement\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
    Route::prefix('campaigns/{campaign}')
        ->name('campaigns.')
        ->group(function () {
            Route::get('once-off', [OnceOffCampaignController::class, 'show'])->name('once-off.show');
            Route::post('once-off/contacts', [OnceOffCampaignContactController::class, 'store'])->name('once-off.contacts.store');
            Route::post('once-off/contact-imports', [OnceOffCampaignContactImportController::class, 'store'])->name('once-off.contact-imports.store');
            Route::post('once-off/contact-imports/{contactImport}/sync', [OnceOffCampaignContactImportController::class, 'sync'])
                ->scopeBindings()
                ->name('once-off.contact-imports.sync');
            Route::get('ongoing', [OngoingCampaignController::class, 'show'])->name('ongoing.show');
            Route::get('batch-processing', [BatchProcessingCampaignController::class, 'show'])->name('batch-processing.show');
        });
    Route::resource('campaigns', CampaignController::class)->except('destroy');

    Route::prefix('user-management')
        ->name('user-management.')
        ->group(function () {
            Route::resource('users', UserController::class)->except('show');
            Route::resource('roles', RoleController::class)->except('show');
        });
});

require __DIR__.'/settings.php';
