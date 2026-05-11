<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * ================================================================
         * 1) TRANSFER ORDERS
         * ================================================================
         */
        Schema::create('transfer_orders', function (Blueprint $table) {
            $table->id();

            // Multi-tenant
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();

            $table->foreignId('voucher_type_id')
                ->constrained('voucher_types')
                ->cascadeOnDelete();

            // Morphs with SAFE index names
            $table->nullableMorphs('document', 'to_doc_morph_idx');
            $table->nullableMorphs('source_location', 'to_src_loc_morph_idx');
            $table->nullableMorphs('destination_location', 'to_dest_loc_morph_idx');

            $table->string('document_number');

            $table->unique(
                ['organization_id', 'document_number'],
                'to_org_docnum_unique'
            );

            $table->enum('status', [
                'draft',
                'requested',
                'approved',
                'in_transit',
                'completed',
                'rejected',
            ])->default('draft');

            $table->decimal('total_quantity', 18, 6)->default(0);
            $table->decimal('grand_total_value', 18, 4)->default(0);

            // Workflow actors
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable();

            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();

            $table->foreignId('in_transit_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('in_transit_at')->nullable();

            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();

            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Optimized indexes
            $table->index(['organization_id', 'status'], 'to_org_status_idx');
            $table->index(
                ['organization_id', 'source_location_type', 'source_location_id'],
                'to_org_srcloc_idx'
            );
            $table->index(
                ['organization_id', 'destination_location_type', 'destination_location_id'],
                'to_org_destloc_idx'
            );
        });


        /*
         * ================================================================
         * 2) TRANSFER ORDER ITEMS
         * ================================================================
         */
        Schema::create('transfer_order_items', function (Blueprint $table) {
            $table->id();

            // Added organization_id (was missing)
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();

            $table->foreignId('transfer_order_id')
                ->constrained('transfer_orders')
                ->cascadeOnDelete();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            $table->foreignId('inventory_batch_id')
                ->nullable()
                ->constrained('inventory_batches')
                ->nullOnDelete();

            $table->foreignId('unit_id')
                ->nullable()
                ->constrained('units')
                ->nullOnDelete();

            $table->decimal('quantity', 18, 6)->default(0);
            $table->decimal('unit_cost', 18, 4)->default(0);
            $table->decimal('line_total', 18, 4)->default(0);
            $table->decimal('allocated_quantity', 18, 6)->default(0);
            $table->boolean('is_allocated')->default(false);

            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Optimized indexes
            $table->index(['transfer_order_id'], 'toi_transfer_idx');
            $table->index(['organization_id', 'product_variant_id'], 'toi_org_pv_idx');
            $table->index(['organization_id', 'inventory_batch_id'], 'toi_org_batch_idx');
            $table->index(['is_allocated', 'allocated_quantity'], 'toi_alloc_idx');
        });


        /*
         * ================================================================
         * 3) ITEM STOCK SUMMARY
         * ================================================================
         */
        Schema::create('item_stock_summary', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();

            $table->string('location_type', 100);
            $table->unsignedBigInteger('location_id');

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->cascadeOnDelete();

            $table->decimal('total_quantity', 18, 6)->default(0);
            $table->decimal('weighted_average_cost', 18, 6)->default(0);
            $table->decimal('total_stock_value', 18, 4)->default(0);

            $table->timestamp('calculated_at')->nullable();
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(
                ['organization_id', 'location_type', 'location_id', 'product_variant_id'],
                'iss_org_loc_item_unique'
            );

            $table->index(['organization_id', 'product_variant_id'], 'iss_org_pv_idx');
            $table->index(['organization_id', 'location_type', 'location_id'], 'iss_org_loc_idx');
            $table->index(['total_quantity'], 'iss_qty_idx');
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('event_type');   // role_assigned, context_switched, etc
            $table->string('description')->nullable();
            $table->nullableMorphs('auditable');    // optional target
            $table->json('meta')->nullable();

            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('item_stock_summary');
        Schema::dropIfExists('transfer_order_items');
        Schema::dropIfExists('transfer_orders');
        Schema::dropIfExists('audit_logs');
    }
};
