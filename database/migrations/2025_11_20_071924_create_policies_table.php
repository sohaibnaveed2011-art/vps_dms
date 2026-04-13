<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         |--------------------------------------------------------------------------
         | ORGANIZATION POLICIES
         |--------------------------------------------------------------------------
         | High-level org rules (context, scope, hierarchy)
         */

        Schema::create('organization_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('category')->nullable();   // hierarchy, workflow, numbering
            $table->string('key');               // hierarchy, routing, stock_flow
            $table->json('value')->nullable();
            $table->boolean('is_locked')->default(false); // row-level lock
            $table->string('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['organization_id', 'key']);
            $table->index(['organization_id', 'category']);

        });

        /*
         |--------------------------------------------------------------------------
         | STOCK FLOW POLICIES
         |--------------------------------------------------------------------------
         | Controls allowed stock movement paths
         */

        Schema::create('stock_flow_policies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            // Polymorphic FROM
            $table->string('from_type', 100);
            $table->unsignedBigInteger('from_id')->nullable();
            // Polymorphic TO
            $table->string('to_type', 100);
            $table->unsignedBigInteger('to_id')->nullable();
            $table->boolean('allowed')->default(true);
            $table->boolean('is_locked')->default(false);
            $table->string('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            // Unique rule per organization
            $table->unique([
                'organization_id',
                'from_type',
                'from_id',
                'to_type',
                'to_id'
            ], 'stock_flow_unique');

            // Performance indexes
            $table->index([
                'organization_id',
                'from_type',
                'to_type'
            ], 'flow_type_idx');

            $table->index([
                'organization_id',
                'from_type',
                'from_id'
            ], 'flow_from_idx');

            $table->index([
                'organization_id',
                'to_type',
                'to_id'
            ], 'flow_to_idx');
        });


        /*
         |--------------------------------------------------------------------------
         | AUTHORITY POLICIES
         |--------------------------------------------------------------------------
         | Who can control stock at what level
         */
        Schema::create('authority_policies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->string('subject',50);       // stock, voucher, user
            $table->string('action',50)->nullable();   // approve, review
            $table->string('voucher_type',50)->nullable();
            $table->string('hierarchy_type',50)->nullable();  // org, branch, warehouse
            $table->unsignedBigInteger('hierarchy_id')->nullable();
            $table->enum('effect',['allow','deny'])->default('allow');
            $table->boolean('is_locked')->default(false);
            $table->string('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique([
                'organization_id',
                'role_id',
                'subject',
                'action',
                'voucher_type',
                'hierarchy_type',
                'hierarchy_id'
            ], 'authority_unique');
            $table->index([
                'organization_id',
                'role_id',
                'subject'
            ], 'authority_lookup_idx');
        });

        /*
         |--------------------------------------------------------------------------
         | STOCK CONTROL POLICIES
         |--------------------------------------------------------------------------
         | Quantitative & accounting rules
         */
        Schema::create('stock_control_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('key'); // allow_negative_stock, enforce_fifo, valuation_method
            $table->json('value')->nullable();

            $table->string('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['organization_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_control_policies');
        Schema::dropIfExists('authority_policies');
        Schema::dropIfExists('stock_flow_policies');
        Schema::dropIfExists('organization_policies');
    }
};
