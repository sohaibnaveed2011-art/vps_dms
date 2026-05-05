<?php

/*
|--------------------------------------------------------------------------
| API Routes (v1)
|--------------------------------------------------------------------------
| Enforcement order:
| 1. auth:sanctum        → authentication
| 2. context.guard      → active operational context
| 3. ability:{domain}.* → token scope ceiling
| 4. Controller authz   → fine-grained permission checks
|--------------------------------------------------------------------------
*/

/* =========================================================
 | AUTH CONTROLLERS
 ========================================================= */
use App\Http\Controllers\v1\api\Auth\ContextSwitchController;
use App\Http\Controllers\v1\api\Auth\PermissionController;
use App\Http\Controllers\v1\api\Auth\RoleController;
use App\Http\Controllers\v1\api\Auth\UserAssignmentController;
use App\Http\Controllers\v1\api\Auth\UserContextController;
use App\Http\Controllers\v1\api\Auth\UserController;
use Illuminate\Support\Facades\Route;

/* =========================================================
 | API VERSION v1
 ========================================================= */
Route::prefix('v1')->group(function () {

    /* =====================================================
     | PUBLIC AUTH (NO TOKEN)
     ===================================================== */
    require __DIR__.'/api/auth.php';

    /*
    |--------------------------------------------------------------------------
    | AUTHORITY POLICIES (SYSTEM ONLY)
    |--------------------------------------------------------------------------
    | No permissions
    | is_admin enforced in AuthorizationService
    */
    require __DIR__.'/api/policies.php';

    /* =====================================================
     | AUTHENTICATED (TOKEN REQUIRED)
     ===================================================== */
    Route::middleware('auth:sanctum')->group(function () {

        /* ===============================================
        | RBAC (NO CONTEXT REQUIRED)
        ================================================= */
        Route::prefix('rbac')->middleware('ability:auth.*')->group(function () {
            Route::apiResource('roles', RoleController::class);
            Route::apiResource('permissions', PermissionController::class);
            Route::put('roles/{role}/sync-permissions', [RoleController::class, 'syncPermissions']);
        });

        /* ================================================
         | USERS (SYSTEM / ORG ADMIN) (NO CONTEXT REQUIRED)
         ================================================== */
        Route::middleware('ability:users.*')->group(function () {
            Route::apiResource('users', UserController::class);
            /* Assignment & Context management */
            Route::apiResource('assignments', UserAssignmentController::class)->only(['index', 'show']);
            Route::post('assignments/{assignment}/revoke', [UserAssignmentController::class, 'revokeRoleAt']);
            Route::get('/active-assignments', [UserAssignmentController::class, 'myActiveAssignments']);
            // View available contexts
            Route::get('contexts', [UserContextController::class, 'index']);
            Route::get('contexts/{context}', [UserContextController::class, 'show']);
            Route::prefix('users')->group(function () {
                /* Role assignment at scope */
                Route::post('{user}/assign-role-at', [UserAssignmentController::class, 'assignRoleAt']);
            });
        });

        /* =====================================
         | CONTEXT SWITCH (NO CONTEXT REQUIRED)
         ======================================= */
        Route::get('available-contexts', [UserContextController::class, 'available']);
        Route::post('switch-context', [ContextSwitchController::class, 'switch']);

        /* =====================================================
         | CONTEXT-BOUND APIs
         ===================================================== */
        Route::middleware('context.guard')->group(function () {
            /* =========================
             | CORE MASTER DATA
             ========================= */
            require __DIR__. '/api/config.php';

            /* =========================
             | PARTNERS
             ========================= */
            require __DIR__.'/api/partner.php';

            /* =========================
             | INVENTORY
             ========================= */
            require __DIR__.'/api/inventory.php';
            /* =========================
             | VOUCHERS
             ========================= */
            require __DIR__.'/api/voucher.php';
            /* =========================
             | ACCOUNT
             ========================= */
            require __DIR__.'/api/account.php';
        });
    });
});
