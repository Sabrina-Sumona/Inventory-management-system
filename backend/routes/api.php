<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthenticatedSessionController;

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
    Route::get('/auth/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'message' => 'Authenticated user retrieved successfully.',
            'data' => [
                'user' => $request->user(),
            ],
        ]);
    });

    Route::post('/auth/logout', [
        AuthenticatedSessionController::class,
        'destroy',
    ]);
});
