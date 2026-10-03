<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\CaptureController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MidtransCallbackController;
use App\Http\Controllers\PhotoFrameController;
use App\Http\Controllers\PhotoPackageController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PhotoboothTestController;
use App\Http\Controllers\PrinterSettingController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect('/login'));

/*
|--------------------------------------------------------------------------
| Public photobooth flow (customer, tanpa login)
|--------------------------------------------------------------------------
| Pilih paket -> bayar via Midtrans -> callback sukses -> baru bisa ambil foto.
*/
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/{payment}/status', [CheckoutController::class, 'status'])->name('checkout.status');
Route::get('/checkout/{payment}/status.json', [CheckoutController::class, 'statusJson'])->name('checkout.status.json');

Route::get('/capture/{session:session_code}', [CaptureController::class, 'show'])->name('capture.show');
Route::post('/capture/{session:session_code}/photos', [CaptureController::class, 'store'])->name('capture.store');

// HTTP Notification / callback dari Midtrans (dikecualikan dari CSRF).
Route::post('/midtrans/notification', [MidtransCallbackController::class, 'notification'])
    ->name('midtrans.notification');

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
    Route::get('/photobooth-test', [PhotoboothTestController::class, 'index'])->name('photobooth-test');

    Route::get('/branding', [BrandingController::class, 'index'])->name('branding');
    Route::post('/branding', [BrandingController::class, 'store'])->name('branding.store');
    Route::put('/branding', [BrandingController::class, 'update'])->name('branding.update');

    // Printer settings
    Route::get('/settings/printer', [PrinterSettingController::class, 'index'])->name('printer.settings');
    Route::post('/settings/printer', [PrinterSettingController::class, 'store'])->name('printer.settings.save');

    Route::resource('frames', PhotoFrameController::class);

    Route::patch('/frames/{frame}/toggle-status', [PhotoFrameController::class, 'toggleStatus'])
        ->name('frames.toggle-status');

    Route::resource('packages', PhotoPackageController::class)->except(['show', 'create', 'edit']);

    Route::patch('/packages/{package}/toggle-status', [PhotoPackageController::class, 'toggleStatus'])
        ->name('packages.toggle-status');

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/sessions/{id}', [SessionController::class, 'show'])->name('sessions.show');
    Route::get('/sessions/{id}/download', [SessionController::class, 'download'])->name('sessions.download');
    Route::post('/sessions', [SessionController::class, 'store'])->name('sessions.store');
    Route::put('/sessions/{session}', [SessionController::class, 'update'])->name('sessions.update');
    Route::post('/sessions/{id}/print', [SessionController::class, 'print'])->name('sessions.print');
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
