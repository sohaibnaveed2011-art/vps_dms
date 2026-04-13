<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\api\Core\OrganizationController;
use App\Http\Controllers\v1\api\Governance\{
    OrganizationPolicyController,
    AuthorityPolicyController,
    StockFlowPolicyController,
};

/*
|--------------------------------------------------------------------------
| GOVERNANCE POLICIES (Organization Scoped)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum','system.admin'])->group(function () {
    Route::post('organizations', [OrganizationController::class, 'store']);
    Route::prefix('organizations/{organization}')->scopeBindings()->group(function () {
        /*
        |--------------------------------------------------------------------------
        | GLOBAL POLICY LOCK (Organization Level)
        |--------------------------------------------------------------------------
        */
        Route::controller(OrganizationController::class)->group(function () {
            Route::put('policies/lock', 'lockAll');
            Route::put('policies/unlock', 'unlockAll');
        });

        /*
        |--------------------------------------------------------------------------
        | POLICY DOMAIN ROUTES
        |--------------------------------------------------------------------------
        */
        registerPolicyRoutes('organization-policies', OrganizationPolicyController::class);
        registerPolicyRoutes('authority-policies', AuthorityPolicyController::class);
        registerPolicyRoutes('stock-flow-policies', StockFlowPolicyController::class);
        // registerPolicyRoutes('stock-control-policies', StockControlPolicyController::class);
    });
});

/*
|--------------------------------------------------------------------------
| Shared Policy Route Definition
|--------------------------------------------------------------------------
*/
function registerPolicyRoutes(string $prefix, string $controller): void
{
    Route::prefix($prefix)
        ->controller($controller)
        ->group(function () {

            // CRUD
            Route::get('/', 'index');
            Route::post('/', 'store');
            Route::get('{policy}', 'show');
            Route::put('{policy}', 'update');
            Route::delete('{policy}', 'destroy');

            // Locking
            Route::put('{policy}/lock', 'lock');
            Route::put('{policy}/unlock', 'unlock');

            // Soft Delete Handling
            Route::put('{policy}/restore', 'restore');
            Route::delete('{policy}/force', 'forceDelete');
        });
}
