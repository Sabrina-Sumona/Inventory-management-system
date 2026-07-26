<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\Auth\CurrentUserController;

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'Inventory Management API is running.',
        'data' => [
            'service' => 'Laravel API',
            'status' => 'healthy',
        ],
    ]);
});

Route::post('/auth/login', [
    AuthenticatedSessionController::class,
    'store',
])->middleware('throttle:5,1');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/user', CurrentUserController::class);

    Route::post('/auth/logout', [
        AuthenticatedSessionController::class,
        'destroy',
    ]);
});
