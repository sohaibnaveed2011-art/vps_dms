<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Organizations
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('legal_name')->nullable();
            $table->date('business_start_date')->nullable();

            $table->string('ntn')->nullable();
            $table->string('strn')->nullable();
            $table->string('incorporation_no')->nullable();

            // Email: NOT globally unique (recommended)
            $table->string('email')->nullable();
            $table->string('contact_no')->nullable();
            $table->string('website')->nullable();

            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('Pakistan');
            $table->string('zip_code')->nullable();

            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();

            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();

            $table->char('currency_code', 3)->default('PKR');
            $table->boolean('is_active')->default(true);
            $table->boolean('policies_locked')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });

        // 2. Financial Years
        Schema::create('financial_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_closed')->default(false);
            
            // Add accounting-specific fields
            $table->enum('period_type', ['monthly', 'quarterly', 'yearly'])->default('yearly');
            $table->unsignedSmallInteger('period_number')->nullable(); // 1-12 for monthly, 1-4 for quarterly
            $table->foreignId('parent_period_id')->nullable()->constrained('financial_years')->nullOnDelete();
            $table->enum('status', ['draft', 'open', 'closing', 'closed', 'archived'])->default('open');
            
            // Closing tracking
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            
            // Opening balance tracking
            $table->decimal('opening_balance_total', 18, 4)->default(0);
            $table->boolean('opening_balances_posted')->default(false);
            
            // Audit
            $table->text('closure_notes')->nullable();
            $table->json('closure_summary')->nullable();
            
            // Indexes
            $table->index(['organization_id', 'status', 'start_date']);
            $table->index(['organization_id', 'parent_period_id']);
            
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['organization_id','name']);
            $table->index(['organization_id','is_active']);
            $table->index(['organization_id','start_date','end_date']);
        });

        // 3. Branches
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('code'); // unique constraint removed unless needed

            $table->string('email')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_no')->nullable();

            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('Pakistan');
            $table->string('zip_code')->nullable();

            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();

            $table->boolean('is_fbr_active')->default(false);
            $table->string('pos_id')->nullable();
            $table->string('pos_auth_token')->nullable();

            $table->boolean('is_active')->default(true);

            $table->unique(['organization_id', 'name']);
            $table->unique(['organization_id', 'code']); // enable only if required
            $table->index(['organization_id', 'is_active']);


            $table->timestamps();
            $table->softDeletes();
        });

        // 4. Taxes
        Schema::create('taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->decimal('rate', 8, 4);
            $table->enum('calculation_type',['exclusive','inclusive'])->default('exclusive');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['organization_id','name']);
            $table->index(['organization_id', 'is_active']);
        });

        // =================================================================
        // PHASE 2: WAREHOUSE & LOCATION MANAGEMENT (WMS)
        // =================================================================

        // 5. Warehouses (Physical Container)

        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            // if branch is deleted, prefer to set warehouse.branch_id = NULL instead of deleting warehouses
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->string('name');
            $table->string('code');
            // Branch Specific Contact
            $table->string('email')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_no')->nullable();

            // Address (Detailed)
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('Pakistan');
            $table->string('zip_code')->nullable();

            // Geo-Location (decimal)
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Unique per organization (allow different orgs to reuse codes/names)
            $table->unique(['organization_id', 'name']);
            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'is_active']);
            $table->index(['branch_id', 'is_active']);

        });

        // 6. Outlets (Sales Counter - Links to a Warehouse for Stock)
        Schema::create('outlets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            // if branch deleted, set outlet.branch_id = NULL rather than delete outlet
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            // Link the outlet to its designated stock source/shelf (a Warehouse)
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->string('name');
            $table->string('code');
            // Branch Specific Contact
            $table->string('email')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('contact_no')->nullable();

            // Address (Detailed)
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('Pakistan');
            $table->string('zip_code')->nullable();

            // Geo-Location
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'name']);
            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'is_active']);
            $table->index(['branch_id', 'is_active']);
        });

        // Section Categories (Logical grouping)
        Schema::create('section_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name'); // e.g., "Women's Apparel"
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'name']);
        });

        // 7. Warehouse Sections (Zone/Aisle/Shelf)
        Schema::create('warehouse_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            // self-referential parent: prefer nullOnDelete to avoid massive cascade deletes up/down hierarchy
            $table->foreignId('parent_section_id')->nullable()->constrained('warehouse_sections')->nullOnDelete();
            $table->foreignId('section_category_id')->nullable()->constrained('section_categories')->nullOnDelete();

            // Hierarchy metadata
            $table->string('hierarchy_path')->nullable(); // e.g., '1.3.15'
            $table->integer('level')->default(1);

            $table->string('name'); // e.g., "Aisle 3"
            $table->string('code')->nullable();
            $table->string('zone')->nullable();
            $table->string('aisle')->nullable();
            $table->string('rack')->nullable();
            $table->string('shelf')->nullable();
            $table->string('bin')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['warehouse_id', 'hierarchy_path']);
            $table->unique(['warehouse_id', 'code']);
            $table->unique(['warehouse_id', 'name']);
            $table->index(['organization_id', 'is_active']);
            $table->index(['warehouse_id', 'is_active']);
        });

        // 8. Outlet Sections (front shelf, counter, etc)
        Schema::create('outlet_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('outlet_id')->constrained('outlets')->cascadeOnDelete();
              // self-referential parent: prefer nullOnDelete to avoid massive cascade deletes up/down hierarchy
            $table->foreignId('parent_section_id')->nullable()->constrained('outlet_sections')->nullOnDelete();
            $table->foreignId('section_category_id')->nullable()->constrained('section_categories')->nullOnDelete();
            $table->string('name'); // "Front Window"
            $table->string('code')->nullable(); // "F01"
            $table->boolean('is_pos_counter')->default(false);
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['outlet_id', 'name']);
            $table->index(['organization_id', 'is_active']);
            $table->index(['outlet_id', 'is_active']);
        });
    }

    public function down(): void
    {
        // Drop in reverse dependency order to avoid FK constraint errors.
        Schema::dropIfExists('outlet_sections');
        Schema::dropIfExists('warehouse_sections');
        Schema::dropIfExists('section_categories');
        Schema::dropIfExists('outlets');
        Schema::dropIfExists('warehouses');
        Schema::dropIfExists('taxes');
        Schema::dropIfExists('branches');
        Schema::dropIfExists('financial_years');
        Schema::dropIfExists('organizations');
    }
};
