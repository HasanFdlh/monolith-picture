<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SessionController;

Route::post('/sessions', [SessionController::class, 'store']);

