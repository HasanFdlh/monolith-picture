<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PhotoFrameController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect('/login'));

// GUEST
Route::middleware('guest')->group(function () {

    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');

    Route::get('/forgot-password', [AuthController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
});

// AUTH
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/branding', [BrandingController::class, 'index'])->name('branding');
    Route::post('/branding', [BrandingController::class, 'store'])->name('branding.store');
    Route::put('/branding', [BrandingController::class, 'update'])->name('branding.update');

    Route::get('/frames', [PhotoFrameController::class, 'index'])->name('frames.index');
    Route::get('/frames/create', [PhotoFrameController::class, 'create'])->name('frames.create');
    Route::post('/frames', [PhotoFrameController::class, 'store'])->name('frames.store');
    Route::get('/frames/{frame}/edit', [PhotoFrameController::class, 'edit'])->name('frames.edit');
    Route::put('/frames/{frame}', [PhotoFrameController::class, 'update'])->name('frames.update');
    Route::delete('/frames/{frame}', [PhotoFrameController::class, 'destroy'])->name('frames.destroy');

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/sessions/{id}', [SessionController::class, 'show'])->name('sessions.show');
    Route::get('/sessions/{id}/download', [SessionController::class, 'download'])->name('sessions.download');
    Route::delete('/sessions/{session}', [SessionController::class, 'destroy'])->name('sessions.destroy');


    Route::middleware(['permission:users.view'])->group(function () {
        Route::get('/users', [UserController::class, 'index']);
    });

    Route::middleware(['permission:users.create'])->group(function () {
        Route::post('/users', [UserController::class, 'store']);
    });

    Route::middleware(['permission:users.update'])->group(function () {
        Route::put('/users/{id}', [UserController::class, 'update']);
    });

    Route::middleware(['permission:users.delete'])->group(function () {
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
    });
});
