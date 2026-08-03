<?php

use App\Http\Controllers\Api\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\Auth\CurrentUserController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\CustomerContactController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SupplierContactController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\SupplierFinancialSettingController;
use App\Http\Controllers\Api\UserAssignmentController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'success' => true,

        'message' =>
            'Inventory Management API is running.',

        'data' => [
            'service' =>
                'Laravel API',

            'status' =>
                'healthy',
        ],
    ]);
});

Route::post('/auth/login', [
    AuthenticatedSessionController::class,
    'store',
])->middleware(
    'throttle:login'
);

Route::post('/auth/forgot-password', [
    PasswordResetController::class,
    'sendResetLink',
])->middleware(
    'throttle:password-reset-link'
);

Route::post('/auth/reset-password', [
    PasswordResetController::class,
    'reset',
])->middleware(
    'throttle:password-reset'
);

Route::middleware(
    'auth:sanctum'
)->group(function (): void {
    Route::get(
        '/auth/user',
        CurrentUserController::class
    );

    Route::post('/auth/logout', [
        AuthenticatedSessionController::class,
        'destroy',
    ]);

    Route::apiResource(
        'companies',
        CompanyController::class
    )->only([
        'index',
        'show',
        'update',
    ]);

    Route::apiResource(
        'branches',
        BranchController::class
    )->only([
        'index',
        'store',
        'show',
        'update',
        'destroy',
    ]);

    Route::post(
        '/branches/{branch}/restore',
        [
            BranchController::class,
            'restore',
        ]
    )->whereNumber(
        'branch'
    );

    Route::post(
        '/warehouses/{warehouse}/restore',
        [
            WarehouseController::class,
            'restore',
        ]
    )->whereNumber(
        'warehouse'
    );

    Route::apiResource(
        'warehouses',
        WarehouseController::class
    )->only([
        'index',
        'store',
        'show',
        'update',
        'destroy',
    ]);

    Route::post(
        '/suppliers/{supplier}/restore',
        [
            SupplierController::class,
            'restore',
        ]
    )->whereNumber(
        'supplier'
    );

    Route::apiResource(
        'suppliers',
        SupplierController::class
    )->only([
        'index',
        'store',
        'show',
        'update',
        'destroy',
    ]);

    Route::post(
        '/supplier-contacts/{supplierContact}/restore',
        [
            SupplierContactController::class,
            'restore',
        ]
    )->whereNumber(
        'supplierContact'
    );

    Route::apiResource(
        'supplier-contacts',
        SupplierContactController::class
    )
        ->parameters([
            'supplier-contacts' =>
                'supplierContact',
        ])
        ->only([
            'index',
            'store',
            'show',
            'update',
            'destroy',
        ]);

    Route::apiResource(
        'supplier-financial-settings',
        SupplierFinancialSettingController::class
    )
        ->parameters([
            'supplier-financial-settings' =>
                'supplierFinancialSetting',
        ])
        ->only([
            'index',
            'store',
            'show',
            'update',
            'destroy',
        ]);

    Route::post(
        '/customers/{customer}/restore',
        [
            CustomerController::class,
            'restore',
        ]
    )->whereNumber(
        'customer'
    );

    Route::apiResource(
        'customers',
        CustomerController::class
    )->only([
        'index',
        'store',
        'show',
        'update',
        'destroy',
    ]);

    Route::post(
        '/customer-contacts/{customerContact}/restore',
        [
            CustomerContactController::class,
            'restore',
        ]
    )->whereNumber(
        'customerContact'
    );

    Route::apiResource(
        'customer-contacts',
        CustomerContactController::class
    )
        ->parameters([
            'customer-contacts' =>
                'customerContact',
        ])
        ->only([
            'index',
            'store',
            'show',
            'update',
            'destroy',
        ]);

    Route::get(
        '/roles',
        [
            RoleController::class,
            'index',
        ]
    );

    Route::patch(
        '/users/{user}/deactivate',
        [
            UserController::class,
            'deactivate',
        ]
    )->whereNumber(
        'user'
    );

    Route::patch(
        '/users/{user}/activate',
        [
            UserController::class,
            'activate',
        ]
    )->whereNumber(
        'user'
    );

    Route::apiResource(
        'users',
        UserController::class
    )->only([
        'index',
        'store',
        'show',
        'update',
    ]);

    Route::get(
        '/users/{user}/assignments',
        [
            UserAssignmentController::class,
            'show',
        ]
    )->whereNumber(
        'user'
    );

    Route::put(
        '/users/{user}/branch-assignments',
        [
            UserAssignmentController::class,
            'syncBranches',
        ]
    )->whereNumber(
        'user'
    );

    Route::put(
        '/users/{user}/warehouse-assignments',
        [
            UserAssignmentController::class,
            'syncWarehouses',
        ]
    )->whereNumber(
        'user'
    );
});