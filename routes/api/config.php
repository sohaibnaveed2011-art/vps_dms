<?php

/* =========================================================
| CORE CONTROLLERS
========================================================= */
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\api\Core\OrganizationController;
use App\Http\Controllers\v1\api\Core\FinancialYearController;
use App\Http\Controllers\v1\api\Core\TaxController;
use App\Http\Controllers\v1\api\Core\SectionCategoryController;
use App\Http\Controllers\v1\api\Core\BranchController;
use App\Http\Controllers\v1\api\Core\WarehouseController;
use App\Http\Controllers\v1\api\Core\WarehouseSectionController;
use App\Http\Controllers\v1\api\Core\OutletController;
use App\Http\Controllers\v1\api\Core\OutletSectionController;

Route::middleware('ability:core.*')->group(function () {
    Route::apiResource('organizations', OrganizationController::class)->except('store');
    Route::post('organizations/{organization}/restore', [OrganizationController::class, 'restore']);
    Route::delete('organizations/{organization}/force-delete', [OrganizationController::class, 'forceDelete']);

    Route::apiResource('branches', BranchController::class);
    Route::post('branches/{id}/restore', [BranchController::class, 'restore']);
    Route::delete('branches/{id}/force-delete', [BranchController::class, 'forceDelete']);

    Route::apiResource('warehouses', WarehouseController::class);
    Route::post('warehouses/{id}/restore', [WarehouseController::class, 'restore']);
    Route::delete('warehouses/{id}/force-delete', [WarehouseController::class, 'forceDelete']);

    Route::apiResource('warehouse-sections', WarehouseSectionController::class);
    Route::post('warehouse-sections/{id}/restore', [WarehouseSectionController::class, 'restore']);
    Route::delete('warehouse-sections/{id}/force-delete', [WarehouseSectionController::class, 'forceDelete']);

    Route::apiResource('outlets', OutletController::class);
    Route::post('outlets/{id}/restore', [OutletController::class, 'restore']);
    Route::delete('outlets/{id}/force-delete', [OutletController::class, 'forceDelete']);

    Route::apiResource('outlet-sections', OutletSectionController::class);
    Route::post('outlet-sections/{id}/restore', [OutletSectionController::class, 'restore']);
    Route::delete('outlet-sections/{id}/force-delete', [OutletSectionController::class, 'forceDelete']);

    Route::apiResource('section-categories', SectionCategoryController::class);
    Route::post('section-categories/{id}/restore', [SectionCategoryController::class, 'restore']);
    Route::delete('section-categories/{id}/force-delete', [SectionCategoryController::class, 'forceDelete']);

    Route::apiResource('taxes', TaxController::class);
    Route::post('taxes/{id}/restore', [TaxController::class, 'restore']);
    Route::delete('taxes/{id}/force-delete', [TaxController::class, 'forceDelete']);

    Route::apiResource('financial_years', FinancialYearController::class);
    Route::post('financial_years/{id}/restore', [FinancialYearController::class, 'restore']);
    Route::delete('financial_years/{id}/force-delete', [FinancialYearController::class, 'forceDelete']);
});

