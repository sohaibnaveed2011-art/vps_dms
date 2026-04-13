<?php

/* =========================================================
 | INVENTORY CONTROLLERS
 ========================================================= */
use App\Http\Controllers\v1\api\Inventory\BrandController;
use App\Http\Controllers\v1\api\Inventory\BrandModelController;
use App\Http\Controllers\v1\api\Inventory\CategoryController;
use App\Http\Controllers\v1\api\Inventory\InventoryBatchController;
use App\Http\Controllers\v1\api\Inventory\ProductController;
use App\Http\Controllers\v1\api\Inventory\StockLocationController;
use App\Http\Controllers\v1\api\Inventory\UnitController;
use App\Http\Controllers\v1\api\Inventory\VariationController;
use App\Http\Controllers\v1\api\Inventory\VariationValueController;
use App\Http\Controllers\v1\api\Inventory\PriceListController;
use App\Http\Controllers\v1\api\Inventory\PriceListItemController;
use Illuminate\Support\Facades\Route;

Route::middleware(['ability:inventory.*'])->group(function () {

    Route::apiResource('categories', CategoryController::class);
    
    Route::apiResource('brands', BrandController::class);
    Route::apiResource('brands.models', BrandModelController::class)
        ->parameters(['models' => 'brand_model']); // Keeps parameter naming clean
    // Custom actions for soft-deletes (nested)
    Route::prefix('brands/{brand}/models/{brand_model}')->group(function () {
        Route::patch('restore', [BrandModelController::class, 'restore']);
        Route::delete('force', [BrandModelController::class, 'forceDelete']);
    });
    
    Route::apiResource('units', UnitController::class);
    
    Route::apiResource('variations', VariationController::class);
    Route::apiResource('variations.values', VariationValueController::class)
        ->parameters(['values'=> 'variation_values']);
    Route::prefix('variations/{variation}/values/{variation_value}')->group(function () {
        Route::patch('restore', [VariationValueController::class, 'restore']);
        Route::delete('force', [VariationValueController::class, 'forceDelete']);
    });
    
    Route::apiResource('products', ProductController::class);
    Route::delete('products/{product}/force', [ProductController::class, 'forceDelete']);
    Route::patch('products/{product}/restore', [ProductController::class, 'restore']);

    // Route::prefix('products/{product}')->group(function () {
    //     Route::apiResource('variants', ProductVariantUnitController::class)->except(['index']);
    //     // variants index is handled by product-variants endpoint with product_id filter
    //     Route::delete('variants/{product_variant}/force', [ProductVariantController::class, 'forceDelete']);
    //     Route::patch('variants/{product_variant}/restore', [ProductVariantController::class, 'restore']);
    // });

    // Route::apiResource('product-variants', ProductVariantController::class)->only(['index']);

    // Inventory Module Routes
    Route::apiResource('stock-locations', StockLocationController::class);
    Route::delete('stock-locations/{id}/force', [StockLocationController::class, 'forceDelete']);
    Route::patch('stock-locations/{id}/restore', [StockLocationController::class, 'restore']);

    Route::apiResource('inventory-batches', InventoryBatchController::class);
    Route::get('inventory-batches/by-variant/{product_variant_id}', [InventoryBatchController::class, 'byVariant']);
    Route::get('inventory-batches/expired', [InventoryBatchController::class, 'getExpired']);
    Route::get('inventory-batches/expiring', [InventoryBatchController::class, 'getExpiring']);

    // Route::apiResource('inventory-balances', InventoryBalanceController::class)->only(['index', 'show', 'update']);
    // Route::apiResource('inventory-ledger', InventoryLedgerController::class)->only(['index', 'show']);

    // Route::apiResource('inventory-reservations', InventoryReservationController::class);

    // Route::apiResource('product-variant-prices', ProductVariantPriceController::class);
    // Route::apiResource('product-variant-discounts', ProductVariantDiscountController::class);

    // Route::apiResource('serial-numbers', SerialNumberController::class);
    // Route::apiResource('product-variant-units', ProductVariantUnitController::class);

    // Price List
    Route::apiResource('price_lists', PriceListController::class);
    Route::apiResource('price_lists.items', PriceListItemController::class)->shallow();

    // Promotions
    // Route::apiResource('promotions', PromotionController::class);
    // Route::post('promotion-scopes', [PromotionScopeController::class, 'store']);
    // Route::delete('promotion-scopes/{id}', [PromotionScopeController::class, 'destroy']);
    // Route::post('promotion-targets', [PromotionTargetController::class, 'store']);
    // Route::delete('promotion-targets/{id}', [PromotionTargetController::class, 'destroy']);

    // Coupons
    // Route::apiResource('coupons', CouponController::class);
    // Route::post('coupons/apply', [CouponController::class, 'apply']);
    // Route::post('coupon-scopes', [CouponScopeController::class, 'store']);
    // Route::delete('coupon-scopes/{id}', [CouponScopeController::class, 'destroy']);
    // Route::post('coupon-targets', [CouponTargetController::class, 'store']);
    // Route::delete('coupon-targets/{id}', [CouponTargetController::class, 'destroy']);
    // Route::post('customer-coupons', [CustomerCouponController::class, 'store']);

    // Simulation & Cart
    // Route::post('pricing/simulate', [PriceSimulationController::class, 'simulate']);
    // Route::post('cart/calculate', [CartCalculationController::class, 'calculate']);

});
