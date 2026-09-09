<?php

use App\Http\Controllers\Portal\PortalAuthController;
use App\Http\Controllers\Portal\PortalHomeController;
use Illuminate\Support\Facades\Route;

Route::prefix('portal')->name('portal.')->group(function () {
    Route::get('/login', [PortalAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [PortalAuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [PortalAuthController::class, 'logout'])->name('logout');

    Route::middleware('auth:patient,caregiver')->group(function () {
        Route::get('/', [PortalHomeController::class, 'index'])->name('home');
        Route::get('/diario', fn () => view('portal.diary'))->name('diary');
        Route::get('/metas', fn () => view('portal.goals'))->name('goals');
        Route::get('/bienestar', fn () => view('portal.wellbeing'))->name('wellbeing');
        Route::get('/pasaporte', fn () => view('portal.passport'))->name('passport');
        Route::get('/ayuda', fn () => view('portal.support'))->name('support');
    });
});
