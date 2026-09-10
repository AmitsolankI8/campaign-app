<?php

use App\Http\Controllers\Settings\CommunicationSettingsController;
use App\Http\Controllers\Settings\PreferencesController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Settings\SystemSettingsController;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('settings/communication', [CommunicationSettingsController::class, 'index'])->name('communication.index');
    Route::put('settings/communication/{channel}/{provider}', [CommunicationSettingsController::class, 'update'])->name('communication.update');
    Route::get('settings/system', [SystemSettingsController::class, 'edit'])->name('system-settings.edit');
    Route::put('settings/system', [SystemSettingsController::class, 'update'])->name('system-settings.update');
    Route::get('settings/preferences', [PreferencesController::class, 'index'])->name('preferences.index');

    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/security', [SecurityController::class, 'edit'])
        ->middleware(RequirePassword::class)
        ->name('security.edit');

    Route::put('settings/password', [SecurityController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::inertia('settings/appearance', 'settings/Appearance')->name('appearance.edit');
});

Route::get('.well-known/passkey-endpoints', function () {
    return response()->json([
        'enroll' => route('security.edit'),
        'manage' => route('security.edit'),
    ]);
})->name('well-known.passkeys');
