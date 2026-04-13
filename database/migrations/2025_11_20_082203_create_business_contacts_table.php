<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * 1. Partner Categories
         */
        Schema::create('partner_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();

            $table->string('name');
            $table->enum('type', ['customer', 'supplier']);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'name', 'type'], 'partner_cat_unique');
        });

        /**
         * 2. Customers (CRM)
         */
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();

            $table->foreignId('partner_category_id')
                ->nullable()
                ->constrained('partner_categories')
                ->nullOnDelete();

            // Core Identity
            $table->string('name');
            $table->unique(['organization_id', 'name'], 'customer_name_unique');

            // Tax & Legal
            $table->string('cnic')->nullable();
            $table->string('ntn')->nullable();
            $table->string('strn')->nullable();
            $table->string('incorporation_no')->nullable();

            // Contacts
            $table->string('contact_person')->nullable();
            $table->string('contact_no')->nullable();
            $table->string('email')->nullable();

            // Composite index for common lookup by organization + email
            $table->index(['organization_id', 'email'], 'customers_org_email_idx');

            $table->string('address')->nullable();

            // Geo
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();

            // Financial Controls
            $table->decimal('credit_limit', 15, 4)->default(0);
            $table->unsignedInteger('payment_terms_days')->default(0);
            $table->decimal('current_balance', 15, 4)->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });

        /**
         * 3. Suppliers (SCM)
         */
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();

            $table->foreignId('partner_category_id')
                ->nullable()
                ->constrained('partner_categories')
                ->nullOnDelete();

            // Identity
            $table->string('name');
            $table->unique(['organization_id', 'name'], 'supplier_name_unique');

            // Legal & Tax
            $table->string('cnic')->nullable();
            $table->string('ntn')->nullable();
            $table->string('strn')->nullable();
            $table->string('incorporation_no')->nullable();

            // Contact
            $table->string('contact_person')->nullable();
            $table->string('contact_no')->nullable();
            $table->string('email')->nullable();

            // Composite index for common lookup by organization + email
            $table->index(['organization_id', 'email'], 'suppliers_org_email_idx');

            $table->string('address')->nullable();

            // Geo
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();

            // Financial
            $table->unsignedInteger('payment_terms_days')->default(0);
            $table->decimal('current_balance', 15, 4)->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('partner_categories');
    }
};
