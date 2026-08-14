<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\PhotoboothController;

Route::post('/sessions', [SessionController::class, 'store']);

Route::get('/templates', [PhotoboothController::class, 'templates']);
Route::apiResource('photo-sessions', PhotoboothController::class)
    ->parameters(['photo-sessions' => 'photoSession']);
Route::post('/photo-sessions/{photoSession}/photos', [PhotoboothController::class, 'addPhotos']);
Route::delete('/photo-sessions/{photoSession}/photos/{media}', [PhotoboothController::class, 'deletePhoto']);
Route::post('/photo-sessions/{photoSession}/print', [PhotoboothController::class, 'print']);
