<?php

/* =========================================================
 | INVENTORY CONTROLLERS
 ========================================================= */
use App\Http\Controllers\v1\api\Inventory\BrandController;
use App\Http\Controllers\v1\api\Inventory\BrandModelController;
use App\Http\Controllers\v1\api\Inventory\CategoryController;
use App\Http\Controllers\v1\api\Inventory\CouponController;
use App\Http\Controllers\v1\api\Inventory\InventoryBatchController;
use App\Http\Controllers\v1\api\Inventory\PriceListController;
use App\Http\Controllers\v1\api\Inventory\PriceListItemController;
use App\Http\Controllers\v1\api\Inventory\ProductController;
use App\Http\Controllers\v1\api\Inventory\ProductImageController;
use App\Http\Controllers\v1\api\Inventory\ProductVariantPriceController;
use App\Http\Controllers\v1\api\Inventory\PromotionController;
use App\Http\Controllers\v1\api\Inventory\StockLocationController;
use App\Http\Controllers\v1\api\Inventory\UnitController;
use App\Http\Controllers\v1\api\Inventory\VariationController;
use App\Http\Controllers\v1\api\Inventory\VariationValueController;
use Illuminate\Support\Facades\Route;

Route::middleware(['ability:inventory.*'])->group(function () {

    Route::apiResource('categories', CategoryController::class);
    
    Route::apiResource('brands', BrandController::class);
    Route::apiResource('brands.models', BrandModelController::class)
        ->parameters(['models' => 'brand_model']);
    
    Route::prefix('brands/{brand}/models/{brand_model}')->group(function () {
        Route::patch('restore', [BrandModelController::class, 'restore']);
        Route::delete('force', [BrandModelController::class, 'forceDelete']);
    });
    
    Route::apiResource('units', UnitController::class);
    
    Route::apiResource('variations', VariationController::class);
    Route::apiResource('variations.values', VariationValueController::class)->parameters(['values'=> 'variation_values']);
    
    Route::prefix('variations/{variation}/values/{variation_value}')->group(function () {
        Route::patch('restore', [VariationValueController::class, 'restore']);
        Route::delete('force', [VariationValueController::class, 'forceDelete']);
    });
    
    Route::apiResource('products', ProductController::class);
    Route::delete('products/{product}/force', [ProductController::class, 'forceDelete']);
    Route::patch('products/{product}/restore', [ProductController::class, 'restore']);

    /*
    |--------------------------------------------------------------------------
    | Product Variant Prices
    |--------------------------------------------------------------------------
    */

    Route::prefix('product-variant-prices')->group(function () {
        Route::post('/bulk', [ProductVariantPriceController::class, 'bulk']);
        Route::post('/', [ProductVariantPriceController::class, 'store']);
        Route::get('/{id}', [ProductVariantPriceController::class, 'show']);
        Route::put('/{id}', [ProductVariantPriceController::class, 'update']);
        Route::delete('/{id}', [ProductVariantPriceController::class, 'destroy']);
        Route::post('/{id}/restore', [ProductVariantPriceController::class, 'restore']);
        Route::delete('/{id}/force', [ProductVariantPriceController::class, 'forceDelete']);
    });

    // ====================== PRODUCT | VARIANT IMAGES ======================
    Route::post('{type}/{id}/images', [ProductImageController::class, 'store'])
        ->where('type', 'products|product-variants');

    // ====================== COMMON IMAGE OPERATIONS ======================
    Route::prefix('images')->group(function () {
        Route::put('{imageId}/set-primary', [ProductImageController::class, 'setPrimary']);
        Route::delete('{imageId}', [ProductImageController::class, 'destroy']);
        Route::post('{imageId}/restore', [ProductImageController::class, 'restore']);
        Route::delete('{imageId}/force-delete', [ProductImageController::class, 'forceDelete']);
    });

    // Inventory Module Routes
    Route::apiResource('stock-locations', StockLocationController::class);
    Route::delete('stock-locations/{id}/force', [StockLocationController::class, 'forceDelete']);
    Route::patch('stock-locations/{id}/restore', [StockLocationController::class, 'restore']);

    Route::apiResource('inventory-batches', InventoryBatchController::class);
    Route::get('inventory-batches/by-variant/{product_variant_id}', [InventoryBatchController::class, 'byVariant']);
    Route::get('inventory-batches/expired', [InventoryBatchController::class, 'getExpired']);
    Route::get('inventory-batches/expiring', [InventoryBatchController::class, 'getExpiring']);

    // Price List
    Route::apiResource('price_lists', PriceListController::class);
    Route::apiResource('price_lists.items', PriceListItemController::class)->shallow();

    /*
    |--------------------------------------------------------------------------
    | Coupons
    |--------------------------------------------------------------------------
    */
    
    // Custom routes for coupons (must come after apiResource to avoid conflicts)
    Route::prefix('coupons')->group(function () {
        // These routes don't conflict with apiResource because they're more specific
        Route::post('validate', [CouponController::class, 'validateCoupon']);
        Route::post('apply', [CouponController::class, 'applyCoupon']);
        Route::get('statistics', [CouponController::class, 'statistics']);
        Route::get('customers/{customerId}/coupons', [CouponController::class, 'customerCoupons']);
        
        // Custom actions for specific coupons
        Route::prefix('{coupon}')->group(function () {
            Route::post('duplicate', [CouponController::class, 'duplicate']);
            Route::put('toggle-status', [CouponController::class, 'toggleStatus']);
            Route::post('bulk-assign', [CouponController::class, 'bulkAssign']);
            Route::post('restore', [CouponController::class, 'restore']);
            Route::delete('force', [CouponController::class, 'forceDelete']);
        });
    });
    // Option 1: Using apiResource (simpler, follows product pattern)
    Route::apiResource('coupons', CouponController::class);
    

    // Promotions (placeholder for future implementation)
    /*
    |--------------------------------------------------------------------------
    | Promotions
    |--------------------------------------------------------------------------
    */

    Route::prefix('promotions')->group(function () {
        // Validation & Application
        Route::post('validate', [PromotionController::class, 'validatePromotion']);
        Route::post('apply', [PromotionController::class, 'applyPromotion']);
        
        // Queries
        Route::get('active', [PromotionController::class, 'active']);
        Route::post('best-applicable', [PromotionController::class, 'bestApplicable']);
        Route::get('statistics', [PromotionController::class, 'statistics']);
        
        // Operations on specific promotion
        Route::prefix('{id}')->group(function () {
            Route::post('duplicate', [PromotionController::class, 'duplicate']);
            Route::put('toggle-status', [PromotionController::class, 'toggleStatus']);
            Route::post('bulk-assign', [PromotionController::class, 'bulkAssign']);
            Route::post('restore', [PromotionController::class, 'restore']);
            Route::delete('force', [PromotionController::class, 'forceDelete']);
        });
        // Core CRUD (using apiResource pattern)
    });
    Route::apiResource('promotions', PromotionController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
});
