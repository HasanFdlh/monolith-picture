<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PhotoboothController;

Route::post('/sessions', [SessionController::class, 'store']);

Route::get('/templates', [PhotoboothController::class, 'templates']);
Route::apiResource('photo-sessions', PhotoboothController::class)
    ->parameters(['photo-sessions' => 'photoSession']);
Route::post('/photo-sessions/{photoSession}/photos', [PhotoboothController::class, 'addPhotos']);
Route::delete('/photo-sessions/{photoSession}/photos/{media}', [PhotoboothController::class, 'deletePhoto']);
Route::post('/photo-sessions/{photoSession}/print', [PhotoboothController::class, 'print']);

// Pembayaran (kiosk Flutter): pilih paket -> QR Midtrans -> polling sampai paid.
Route::get('/packages', [PaymentController::class, 'packages']);
Route::post('/payments', [PaymentController::class, 'store']);
Route::get('/payments/{payment:order_id}', [PaymentController::class, 'show']);
Route::post('/payments/{payment:order_id}/cancel', [PaymentController::class, 'cancel']);
