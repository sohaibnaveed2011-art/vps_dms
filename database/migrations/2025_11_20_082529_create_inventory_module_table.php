<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        /*
        |--------------------------------------------------------------------------
        | MASTER DATA
        |--------------------------------------------------------------------------
        */

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['organization_id', 'name']);
            $table->index(['organization_id', 'parent_id']);
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['organization_id', 'name']);
        });

        Schema::create('brand_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();

            $table->string('name');              // e.g. "Galaxy S24"
            $table->string('series')->nullable(); // optional grouping
            $table->string('slug')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['brand_id', 'name']);
            $table->index(['organization_id', 'brand_id']);
        });

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('short_name');
            $table->boolean('allow_decimal')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['organization_id', 'name']);
            $table->unique(['organization_id', 'short_name']);
        });

        Schema::create('variations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->string('short_name');
            $table->boolean('is_active')->default(true);
            $table->boolean('has_multiple')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['organization_id', 'name']);
        });

        Schema::create('variation_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variation_id')->constrained('variations')->cascadeOnDelete();
            $table->string('value');
            $table->string('color_code')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['variation_id', 'value']);
            $table->index(['organization_id', 'variation_id']);
        });

        /*
        |--------------------------------------------------------------------------
        | PRODUCTS & VARIANTS (SKU LEVEL)
        |--------------------------------------------------------------------------
        */

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inventory_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('sale_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('cogs_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('valuation_method', ['FIFO', 'FEFO', 'WAVG'])->default('FIFO');
            $table->boolean('has_warranty')->default(false);
            $table->integer('warranty_months')->nullable();
            $table->boolean('has_variants')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['organization_id', 'name']);
            $table->index(['organization_id', 'category_id']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku');
            $table->string('barcode')->nullable();
            $table->decimal('cost_price', 18, 6)->default(0); // Base Cost Price
            $table->decimal('sale_price', 18, 6)->default(0); // Base Sale Price
            $table->boolean('is_serial_tracked')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['product_id', 'sku']);
            $table->index(['barcode']);
            $table->index(['product_id', 'is_active']);
        });

        Schema::create('product_variant_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->decimal('conversion_factor', 18, 6);
            // Example:
            // 1 Carton = 24 PCS → conversion_factor = 24
            $table->boolean('is_base')->default(false);
            // true = inventory stored in this unit
            $table->boolean('is_purchase_unit')->default(false);
            $table->boolean('is_sale_unit')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['product_variant_id', 'unit_id']);
            $table->index(['product_variant_id', 'is_active']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->morphs('imageable');
            // Product | ProductVariant
            $table->string('path');
            $table->string('disk')->default('public');
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['imageable_type', 'imageable_id', 'is_primary']);
        });

        Schema::create('product_variation', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variation_id')->constrained('variations')->cascadeOnDelete();
            $table->timestamps();
    
            // Add this line
            $table->softDeletes();
            $table->unique(['product_id', 'variation_id']);
        });

        Schema::create('product_variant_variation_value', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variation_value_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['product_variant_id',
                'variation_value_id',
            ],'pro_var_val_unique');
            $table->index(['variation_value_id']);
        });

        Schema::create('product_variant_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->morphs('priceable');
            // Organization | Branch | Outlet | StockLocation
            $table->decimal('cost_price', 18, 6)->nullable(); // Override Cost Price
            $table->decimal('sale_price', 18, 6)->nullable(); // Override Sale Price at hierarchy level, higher priority than base price
            $table->timestamps();
            $table->softDeletes();
            $table->unique(
                ['product_variant_id', 'priceable_type', 'priceable_id'],
                'pv_prices_unique'
            );
            $table->index(['organization_id', 'product_variant_id']);
        });

        Schema::create('product_variant_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            // Replace morphs()
            $table->string('discountable_type');
            $table->unsignedBigInteger('discountable_id');
            $table->index(
                ['discountable_type', 'discountable_id'],
                'pv_disc_morph_index'
            );
            $table->enum('type', ['percentage', 'fixed']);
            $table->decimal('value', 18, 6);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(
                ['product_variant_id', 'discountable_type', 'discountable_id'],
                'pv_discount_unique'
            );
            $table->index(
                ['organization_id', 'product_variant_id', 'is_active'],
                'pv_discount_index'
            );
        });

        Schema::create('serial_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stock_location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('condition_id')->constrained('inventory_conditions');
            $table->string('serial');
            $table->enum('status', [
                'available',
                'reserved',
                'in_transit',
                'sold',
                'damaged',
                'returned',
                'scrapped'
            ])->default('available');
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['organization_id', 'serial']);
            $table->index(['stock_location_id','product_variant_id','condition_id','status'], 'serial_search_idx');
            $table->index(['organization_id', 'stock_location_id', 'status'], 'serial_loc_status_idx');
        });


        /*
        |--------------------------------------------------------------------------
        | STOCK LOCATIONS (FLEXIBLE STRUCTURE)
        |--------------------------------------------------------------------------
        */

        Schema::create('stock_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            // Polymorphic stock holder
            $table->morphs('locatable');
            $table->foreignId('parent_id')->nullable()->constrained('stock_locations')->nullOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            // storage | transit | virtual | mobile | customer
            $table->enum('type', ['storage', 'transit', 'virtual', 'mobile', 'customer'])->default('storage');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            // Allow multiple stock locations per entity
            $table->index(['organization_id', 'locatable_type', 'locatable_id'], 'sl_org_locatable_idx');
            $table->index(['organization_id', 'type'], 'sl_org_type_idx');
            $table->index(['organization_id', 'is_active'], 'sl_org_active_idx');
        });

        Schema::create('inventory_conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_sellable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['organization_id', 'name'], 'inv_cond_org_name_unique');
            $table->index(['organization_id', 'is_active'], 'inv_cond_org_active_idx');
        });


        /*
        |--------------------------------------------------------------------------
        | BATCHES (FIFO READY)
        |--------------------------------------------------------------------------
        */

        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->string('batch_number');
            $table->date('manufacturing_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('initial_cost', 18, 6);
            $table->boolean('is_recalled')->default(false);
            $table->text('recall_reason')->nullable();
            $table->string('storage_condition')->nullable();
            $table->decimal('mrp', 18, 6)->nullable();
            $table->integer('warranty_months')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamps();
            $table->softDeletes();

            /*
            |--------------------------------------------------------------------------
            | Constraints
            |--------------------------------------------------------------------------
            */

            $table->unique(['product_variant_id', 'batch_number'], 'uq_variant_batch');

            /*
            |--------------------------------------------------------------------------
            | Indexes (Optimized for FIFO + FEFO)
            |--------------------------------------------------------------------------
            */

            // FIFO ordering
            $table->index(['product_variant_id', 'created_at'],'idx_variant_created');
            // FEFO ordering
            $table->index(['product_variant_id', 'expiry_date'],'idx_variant_expiry');
            // Recall filtering
            $table->index(['product_variant_id', 'is_recalled'], 'idx_variant_recalled');

        });


        /*
        |--------------------------------------------------------------------------
        | INVENTORY BALANCES (SNAPSHOT)
        |--------------------------------------------------------------------------
        */

        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('stock_location_id')->constrained('stock_locations')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('inventory_batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            // NEW — condition support (Good / Transit / Damaged / Obsolete)
            $table->foreignId('condition_id')->constrained('inventory_conditions')->cascadeOnDelete();
            // Quantities
            $table->decimal('quantity', 18, 6)->default(0);
            $table->decimal('reserved_quantity', 18, 6)->default(0);
            // Reorder controls
            $table->decimal('min_stock', 18, 6)->default(0);
            $table->decimal('reorder_point', 18, 6)->default(0);
            // Cost
            $table->decimal('avg_cost', 18, 6)->default(0);
            $table->timestamps();
            // Unique balance bucket
            $table->unique(['stock_location_id','product_variant_id','inventory_batch_id','condition_id'], 'ib_unique_bucket');
            $table->index(['organization_id', 'product_variant_id'], 'ib_org_variant_idx');
            $table->index(['stock_location_id', 'quantity'], 'ib_loc_qty_idx');
            $table->index(['organization_id', 'stock_location_id', 'product_variant_id', 'condition_id'], 'balance_core_lookup_idx');
            $table->index(['product_variant_id', 'quantity'], 'balance_fast_stock_scan');

        });

        /*
        |--------------------------------------------------------------------------
        | IMMUTABLE LEDGER (OPTIMIZED FOR 10M+ ROWS)
        |--------------------------------------------------------------------------
        */

        Schema::create('inventory_ledger', function (Blueprint $table) {
            $table->bigIncrements('id'); // big for scale
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();

            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            $table->enum('transaction_type', [
                'opening', 'purchase', 'sale', 'transfer_in', 'transfer_out',
                'adjustment_in', 'adjustment_out', 'sales_return', 'purchase_return',
            ]);

            $table->decimal('quantity_in', 18, 6)->default(0);
            $table->decimal('quantity_out', 18, 6)->default(0);
            $table->decimal('unit_cost', 18, 6)->default(0);
            $table->decimal('total_cost', 18, 6)->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // HIGH PERFORMANCE INDEXES
            $table->index(['product_variant_id', 'created_at']);
            $table->index(['stock_location_id', 'product_variant_id']);
            $table->index(['organization_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['inventory_batch_id']);
            $table->index(['organization_id','product_variant_id','stock_location_id','created_at'], 'ledger_fast_query_idx');

        });

        /*
        |--------------------------------------------------------------------------
        | RESERVATIONS
        |--------------------------------------------------------------------------
        */

        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('condition_id')->constrained('inventory_conditions');
            $table->decimal('quantity', 18, 6);
            $table->unsignedTinyInteger('priority')->default(1);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->enum('status', [
                'active',
                'released',
                'consumed',
                'expired'
            ])->default('active');
            $table->morphs('reference');
            $table->timestamps();
            $table->index([
                'stock_location_id',
                'product_variant_id',
                'status'
            ], 'reservation_core_idx');
        });

        Schema::create('reservation_serials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('inventory_reservations')->cascadeOnDelete();
            $table->foreignId('serial_number_id')->constrained('serial_numbers')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['reservation_id', 'serial_number_id']);
            $table->index(['serial_number_id']);
        });

        /*
        |--------------------------------------------------------------------------
        | PRICE LISTS
        |--------------------------------------------------------------------------
        */

        Schema::create('price_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->string('currency', 10)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->unsignedInteger('priority')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['organization_id', 'is_default']);
        });

        Schema::create('price_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 18, 6); // Selling price for this variant in the price list
            $table->decimal('min_quantity', 18, 6)->nullable(); // Minimum quantity for this price tier
            $table->timestamps();
            $table->softDeletes();            
            $table->unique(['price_list_id', 'product_variant_id', 'min_quantity'], 'pli_unique_price_tier');
            $table->index(['product_variant_id']);
        });

        /*
        |--------------------------------------------------------------------------
        | PROMOTIONS
        |--------------------------------------------------------------------------
        */

        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['percentage', 'fixed']);
            $table->decimal('value', 18, 6);
            $table->date('start_date');
            $table->date('end_date');
            $table->unsignedInteger('priority')->default(1);
            $table->boolean('stackable')->default(false);
            $table->decimal('min_order_amount', 18, 6)->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['organization_id', 'is_active', 'start_date', 'end_date']);
        });

        Schema::create('promotion_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->morphs('scopeable');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['promotion_id', 'scopeable_type', 'scopeable_id']);
        });

        Schema::create('promotion_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->morphs('targetable');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['promotion_id', 'targetable_type', 'targetable_id'],'pi_tt_ti_unique');
        });

        /*
        |--------------------------------------------------------------------------
        | COUPONS
        |--------------------------------------------------------------------------
        */

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->enum('type', ['percentage', 'fixed']);
            $table->decimal('value', 18, 6);
            $table->decimal('min_order_amount', 18, 6)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('used_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['organization_id', 'is_active', 'valid_from', 'valid_to']);
        });

        Schema::create('coupon_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->morphs('scopeable');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['coupon_id', 'scopeable_type', 'scopeable_id']);
        });

        Schema::create('coupon_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->morphs('targetable');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['coupon_id', 'targetable_type', 'targetable_id']);
        });

        Schema::create('customer_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['customer_id', 'coupon_id']);
        });

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('customer_coupons');
        Schema::dropIfExists('coupon_targets');
        Schema::dropIfExists('coupon_scopes');
        Schema::dropIfExists('coupons');

        Schema::dropIfExists('promotion_targets');
        Schema::dropIfExists('promotion_scopes');
        Schema::dropIfExists('promotions');

        Schema::dropIfExists('price_list_items');
        Schema::dropIfExists('price_lists');

        Schema::dropIfExists('inventory_reservations');
        Schema::dropIfExists('inventory_ledger');
        Schema::dropIfExists('inventory_balances');
        Schema::dropIfExists('inventory_conditions');
        Schema::dropIfExists('inventory_batches');

        Schema::dropIfExists('stock_locations');

        Schema::dropIfExists('serial_numbers');
        Schema::dropIfExists('product_variant_discounts');
        Schema::dropIfExists('product_variant_prices');
        Schema::dropIfExists('product_variant_variation_values');
        Schema::dropIfExists('product_variations');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('product_variant_units');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');

        Schema::dropIfExists('variation_values');
        Schema::dropIfExists('variations');

        Schema::dropIfExists('units');
        Schema::dropIfExists('brand_models');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');

        Schema::enableForeignKeyConstraints();
    }
};
