<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // -----------------------------------------------------------------
        // Voucher Types
        // -----------------------------------------------------------------

        Schema::create('voucher_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->string('prefix')->nullable();
            $table->enum('module', [
                'sale',
                'purchase',
                'accounts',
                'payroll',
                'inventory',
            ])->default('sale');

            $table->unsignedBigInteger('next_number')->default(1);
            $table->timestamps();

            $table->unique(['organization_id', 'name'], 'voucher_types_org_name_unique');
            $table->index(['organization_id', 'module'], 'voucher_types_org_module_idx');
        });

        // -----------------------------------------------------------------
        // A. SALES FLOW DOCUMENTS
        // -----------------------------------------------------------------

        // 1. Sale Orders

        Schema::create('sale_orders', function (Blueprint $table) {
            $table->id();

            // Multi-tenant scope
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('outlet_id')->nullable()->constrained('outlets')->nullOnDelete();

            // Business fields
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('voucher_type_id')->constrained('voucher_types')->cascadeOnDelete();

            // Scoped document number
            $table->string('document_number');
            $table->unique(['organization_id', 'document_number'], 'so_org_docnum_unique');

            $table->date('order_date')->index();
            $table->date('delivery_date')->nullable()->index();
            $table->decimal('grand_total', 18, 4)->default(0)->index();
            $table->enum('status', ['draft', 'confirmed', 'delivered', 'cancelled'])->default('draft')->index();

            // Audit fields - nullable and nullOnDelete for safer cascade behavior
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Helpful composite indexes
            $table->index(['organization_id', 'customer_id'], 'so_org_customer_idx');
            $table->index(['organization_id', 'voucher_type_id'], 'so_org_vt_idx');
            $table->index(['organization_id', 'status'], 'so_org_status_idx');
        });

        // 2. Invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('pos_session_id')->nullable()->constrained('pos_sessions')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('voucher_type_id')->constrained('voucher_types')->cascadeOnDelete();
            $table->char('currency_code', 3)->default('PKR');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->string('document_number');
            $table->unique(['organization_id', 'document_number'], 'inv_org_docnum_unique');
            $table->string('fbr_invoice_number')->nullable();
            $table->date('date')->index();
            $table->decimal('sub_total', 18, 4)->default(0);
            $table->decimal('tax_total', 18, 4)->default(0);
            $table->decimal('paid_amount', 18, 4)->default(0);
            $table->decimal('due_amount', 18, 4)->default(0);

            $table->decimal('grand_total', 18, 4)->default(0)->index();
            $table->enum('status', ['posted', 'paid', 'overdue', 'cancelled'])->default('posted')->index();
            $table->foreignId('financial_year_id')->constrained('financial_years')->restrictOnDelete();
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['organization_id', 'customer_id'], 'inv_org_customer_idx');
            $table->index(['organization_id', 'voucher_type_id'], 'inv_org_vt_idx');
            $table->index(['organization_id', 'status'], 'inv_org_status_idx');
        });

        // 3. Credit Notes (Sales returns)
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('voucher_type_id')->constrained('voucher_types');

            $table->string('document_number');
            $table->unique(['organization_id', 'document_number'], 'cn_org_docnum_unique');

            $table->date('date')->index();
            $table->decimal('grand_total', 18, 4)->default(0);

            $table->foreignId('financial_year_id')
                ->constrained('financial_years')
                ->restrictOnDelete();
            $table->foreignId('journal_id')
                ->nullable()
                ->constrained('journals')
                ->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'invoice_id'], 'cn_org_invoice_idx');
        });

        // 4. Delivery Notes (Good Delivery)
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('sale_order_id')->nullable()->constrained('sale_orders')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();

            $table->foreignId('voucher_type_id')->constrained('voucher_types')->cascadeOnDelete();
            $table->string('document_number');
            $table->unique(['organization_id', 'document_number'], 'dn_org_docnum_unique');

            $table->date('date')->index();
            $table->foreignId('rider_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['picked', 'in_transit', 'delivered'])->default('picked')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'sale_order_id'], 'dn_org_so_idx');
            $table->index(['organization_id', 'invoice_id'], 'dn_org_inv_idx');
        });

        // -----------------------------------------------------------------
        // B. PURCHASE FLOW DOCUMENTS
        // -----------------------------------------------------------------

        // 5. Purchase Orders

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('voucher_type_id')->constrained('voucher_types')->cascadeOnDelete();

            $table->string('document_number');
            $table->unique(['organization_id', 'document_number'], 'po_org_docnum_unique');

            $table->date('order_date')->index();
            $table->date('expected_receipt_date')->nullable()->index();
            $table->decimal('grand_total', 18, 4)->default(0);
            $table->enum('status', ['draft', 'ordered', 'received', 'cancelled'])->default('draft')->index();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'supplier_id'], 'po_org_supplier_idx');
            $table->index(['organization_id', 'status'], 'po_org_status_idx');
        });

        // 6. Purchase Bills
        Schema::create('purchase_bills', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('voucher_type_id')->constrained('voucher_types')->cascadeOnDelete();
            $table->string('document_number');
            $table->unique(['organization_id', 'document_number'], 'pb_org_docnum_unique');
            $table->char('currency_code', 3)->default('PKR');
            $table->decimal('exchange_rate', 18, 8)->default(1);
            $table->string('supplier_invoice_number')->nullable();
            $table->date('date')->index();
            $table->decimal('grand_total', 18, 4)->default(0);
            $table->decimal('paid_amount', 18, 4)->default(0);
            $table->decimal('due_amount', 18, 4)->default(0);
            $table->enum('status', ['posted', 'paid', 'overdue', 'cancelled'])->default('posted')->index();
            $table->foreignId('financial_year_id')->constrained('financial_years')->restrictOnDelete();
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'supplier_id'], 'pb_org_supplier_idx');
            $table->index(['organization_id', 'status'], 'pb_org_status_idx');
        });

        // 7. Debit Notes (Supplier returns)
        Schema::create('debit_notes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('purchase_bill_id')->nullable()->constrained('purchase_bills')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('voucher_type_id')->constrained('voucher_types')->cascadeOnDelete();
            $table->string('document_number');
            $table->unique(['organization_id', 'document_number'], 'dbn_org_docnum_unique');

            $table->date('date')->index();
            $table->decimal('grand_total', 18, 4)->default(0);

            $table->foreignId('financial_year_id')->constrained('financial_years')->restrictOnDelete();
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'purchase_bill_id'], 'dbn_org_pb_idx');
        });

        // 8. Receipt Notes (Goods received)
        Schema::create('receipt_notes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('purchase_bill_id')->nullable()->constrained('purchase_bills')->nullOnDelete();

            $table->foreignId('voucher_type_id')->constrained('voucher_types')->cascadeOnDelete();
            $table->string('document_number');
            $table->unique(['organization_id', 'document_number'], 'grn_org_docnum_unique');

            $table->date('date')->index();
            $table->enum('status', ['received', 'inspected', 'rejected'])->default('received')->index();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'purchase_order_id'], 'grn_org_po_idx');
            $table->index(['organization_id', 'purchase_bill_id'], 'grn_org_pb_idx');
        });

        // -----------------------------------------------------------------
        // C. DOCUMENT LINE ITEMS (CONSOLIDATED)
        // -----------------------------------------------------------------

        Schema::create('document_items', function (Blueprint $table) {
            $table->id();

            // Scope to organization for multi-tenancy
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();

            // Polymorphic link to any document header
            $table->morphs('document');

            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();

            // Inventory details
            $table->foreignId('inventory_batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->decimal('cost_of_goods_sold', 18, 4)->nullable();

            // Financials
            $table->decimal('quantity', 18, 6);
            $table->decimal('unit_price', 18, 4);
            $table->decimal('discount_amount', 18, 4)->default(0);
            $table->decimal('tax_rate', 8, 3)->default(0);
            $table->decimal('line_total', 18, 4);

            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            // morphs() already creates an index for document_type/document_id so avoid duplicating it.
            $table->index(['organization_id', 'product_variant_id'], 'doc_items_org_product_variant_idx');
            $table->index('inventory_batch_id');
        });

        // -----------------------------------------------------------------
        // Payments / Receipts and Allocations
        // -----------------------------------------------------------------

        // Receipts (Customer payments)

        Schema::create('receipts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();

            // Reference to the document this payment relates to (invoice, advance, etc)
            $table->morphs('reference');

            $table->decimal('amount', 18, 4);
            $table->date('date')->index();

            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('reference_number')->nullable();
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'customer_id'], 'receipts_org_customer_idx');
            $table->index(['organization_id', 'account_id'], 'receipts_org_account_idx');
            // morphs('reference') already creates reference_type/reference_id index
        });

        // Payments (Supplier payments)
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();

            $table->morphs('reference');

            $table->decimal('amount', 18, 4);
            $table->date('date')->index();

            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('reference_number')->nullable();
            $table->foreignId('journal_id')->nullable()->constrained('journals')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'supplier_id'], 'payments_org_supplier_idx');
            $table->index(['organization_id', 'account_id'], 'payments_org_account_idx');
            // morphs('reference') already creates reference_type/reference_id index
        });

        // Receipt Allocations (allocate receipts to invoices)
        Schema::create('receipt_allocations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('receipt_id')->constrained('receipts')->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();

            $table->decimal('amount_allocated', 18, 4);
            $table->date('allocation_date')->index();
            $table->timestamps();

            $table->unique(['receipt_id', 'invoice_id'], 'receipt_invoice_unique');
            $table->index(['invoice_id', 'allocation_date'], 'receipt_alloc_invoice_date_idx');
        });

        // Payment Allocations (allocate payments to bills)
        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->foreignId('purchase_bill_id')->constrained('purchase_bills')->cascadeOnDelete();

            $table->decimal('amount_allocated', 18, 4);
            $table->date('allocation_date')->index();
            $table->timestamps();

            $table->unique(['payment_id', 'purchase_bill_id'], 'payment_bill_unique');
            $table->index(['purchase_bill_id', 'allocation_date'], 'payment_alloc_bill_date_idx');
        });
    }

    public function down(): void
    {
        // drop in reverse dependency order
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('receipt_allocations');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('document_items');

        Schema::dropIfExists('receipt_notes');
        Schema::dropIfExists('debit_notes');
        Schema::dropIfExists('purchase_bills');
        Schema::dropIfExists('purchase_orders');

        Schema::dropIfExists('delivery_notes');
        Schema::dropIfExists('credit_notes');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('sale_orders');

        Schema::dropIfExists('voucher_types');
    }
};
