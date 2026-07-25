<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth:sanctum')->get('/auth/user', function (Request $request) {
    return response()->json([
        'success' => true,
        'message' => 'Authenticated user retrieved successfully.',
        'data' => [
            'user' => $request->user(),
        ],
    ]);
});