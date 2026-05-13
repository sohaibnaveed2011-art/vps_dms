<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // =================================================================
        // 1. SUPPORTING TABLES (Shared across all documents)
        // =================================================================

        // Document number sequences (race-condition safe)
        Schema::create('document_number_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('voucher_type_id')->constrained('voucher_types')->cascadeOnDelete();
            $table->foreignId('financial_year_id')->constrained('financial_years')->cascadeOnDelete();
            $table->unsignedBigInteger('current_number')->default(1);
            $table->string('prefix')->nullable();
            $table->string('suffix')->nullable();
            $table->unsignedSmallInteger('padding_length')->default(5);
            $table->unique(['organization_id', 'voucher_type_id', 'financial_year_id'], 'seq_org_vt_fy_unique');
            $table->timestamps();
        });

        // Exchange rates
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->char('from_currency', 3);
            $table->char('to_currency', 3);
            $table->decimal('rate', 18, 6);
            $table->date('effective_date');
            $table->date('expiry_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['from_currency', 'to_currency', 'effective_date'], 'fx_unique_rate');
            $table->index(['from_currency', 'to_currency', 'effective_date'], 'fx_lookup_idx');
        });

        // Document attachments
        Schema::create('document_attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('document');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size');
            $table->string('hash')->unique();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['document_type', 'document_id'], 'attachments_doc_idx');
            $table->index('hash', 'attachments_hash_idx');
        });

        // Document comments
        Schema::create('document_comments', function (Blueprint $table) {
            $table->id();
            $table->morphs('document');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('comment');
            $table->boolean('is_internal')->default(false);
            $table->json('attachments')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['document_type', 'document_id', 'created_at'], 'comments_doc_date_idx');
        });

        // Document linking/references
        Schema::create('document_links', function (Blueprint $table) {
            $table->id();
            $table->morphs('source_document');
            $table->morphs('target_document');
            $table->string('link_type'); // 'payment', 'return', 'fulfillment', 'reversal', 'reference'
            $table->json('link_metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['source_document_type', 'source_document_id', 'target_document_type', 'target_document_id'], 'doc_link_unique');
            $table->index(['source_document_type', 'source_document_id'], 'doc_link_source_idx');
            $table->index(['target_document_type', 'target_document_id'], 'doc_link_target_idx');
        });

        // Comprehensive audit log
        Schema::create('document_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('document');
            $table->string('event'); // created, updated, deleted, restored, submitted, reviewed, approved, rejected, cancelled, locked, unlocked
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('diff')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['document_type', 'document_id', 'created_at'], 'audit_doc_date_idx');
            $table->index(['event', 'created_at'], 'audit_event_date_idx');
        });

        // Document status history
        Schema::create('document_status_history', function (Blueprint $table) {
            $table->id();
            $table->morphs('document');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['document_type', 'document_id', 'created_at'], 'status_history_doc_date_idx');
        });

        // Document rejection history (detailed)
        Schema::create('document_rejection_history', function (Blueprint $table) {
            $table->id();
            $table->morphs('document');
            $table->unsignedTinyInteger('attempt_number');
            $table->foreignId('rejected_by')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->json('validation_errors')->nullable(); // field-by-field validation failures
            $table->timestamp('rejected_at');
            $table->timestamps();
            $table->index(['document_type', 'document_id', 'attempt_number'], 'rejection_doc_attempt_idx');
        });

        // Document locks (editing prevention)
        Schema::create('document_locks', function (Blueprint $table) {
            $table->id();
            $table->morphs('document');
            $table->foreignId('locked_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('locked_until');
            $table->string('reason')->nullable();
            $table->timestamps();
            $table->unique(['document_type', 'document_id'], 'lock_doc_unique');
            $table->index('locked_until', 'lock_expiry_idx');
        });

        // =================================================================
        // 2. VOUCHER TYPES (Base configuration)
        // =================================================================

        Schema::create('voucher_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->string('prefix')->nullable();
            $table->enum('module', ['sale', 'purchase', 'accounts', 'payroll', 'inventory'])->default('sale');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'name'], 'voucher_types_org_name_unique');
            $table->index(['organization_id', 'module'], 'voucher_types_org_module_idx');
        });

        // =================================================================
        // 3. BASE DOCUMENT TRAIT (Columns for all documents)
        // =================================================================
        // Instead of repeating, we'll add these to each table manually.
        // Common fields added to all document tables:
        // - submitted_at, rejected_at, rejected_by, rejection_reason, rejection_details
        // - approval_attempts, resubmitted_at, fully_allocated_at
        // - allocated_amount (for invoices/bills)

        // =================================================================
        // 4. SALE FLOW DOCUMENTS
        // =================================================================

        // Sale Orders
        Schema::create('sale_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('outlet_id')->nullable()->constrained('outlets')->nullOnDelete();
            $table->foreignId('financial_year_id')->nullable()->constrained('financial_years')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('voucher_type_id')->constrained('voucher_types')->restrictOnDelete();

            $table->string('document_number');
            $table->date('order_date')->index();
            $table->date('delivery_date')->nullable()->index();
            $table->decimal('grand_total', 18, 4)->default(0)->index();
            $table->enum('status', ['draft', 'submitted', 'confirmed', 'delivered', 'cancelled', 'rejected'])->default('draft')->index();

            // Approval & rejection tracking
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->json('rejection_details')->nullable();
            $table->unsignedTinyInteger('approval_attempts')->default(0);
            $table->timestamp('resubmitted_at')->nullable();

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes (removed redundant unique)
            $table->unique(['organization_id', 'document_number'], 'so_org_docnum_unique');
            $table->index(['organization_id', 'customer_id'], 'so_org_customer_idx');
            $table->index(['organization_id', 'voucher_type_id'], 'so_org_vt_idx');
            $table->index(['organization_id', 'status'], 'so_org_status_idx');
            $table->index(['organization_id', 'financial_year_id', 'order_date'], 'so_org_fy_date_idx');
            $table->index('deleted_at', 'so_deleted_at_idx');
        });

        // Invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('pos_session_id')->nullable()->constrained('pos_sessions')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('voucher_type_id')->constrained('voucher_types')->restrictOnDelete();
            $table->char('currency_code', 3)->default('PKR');
            $table->decimal('exchange_rate', 18, 6)->default(1); // Consistent precision
            $table->string('document_number');
            $table->string('fbr_invoice_number')->nullable();
            $table->string('fbr_pos_fee')->nullable();
            $table->date('date')->index();
            $table->decimal('sub_total', 18, 4)->default(0);
            $table->decimal('tax_total', 18, 4)->default(0);
            $table->decimal('paid_amount', 18, 4)->default(0);
            $table->decimal('due_amount', 18, 4)->default(0);
            $table->decimal('allocated_amount', 18, 4)->default(0); // NEW: track allocations
            $table->decimal('grand_total', 18, 4)->default(0)->index();
            $table->enum('status', ['draft', 'submitted', 'posted', 'paid', 'overdue', 'cancelled', 'rejected'])->default('draft')->index();

            // Approval & rejection tracking
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->json('rejection_details')->nullable();
            $table->unsignedTinyInteger('approval_attempts')->default(0);
            $table->timestamp('resubmitted_at')->nullable();
            $table->timestamp('fully_allocated_at')->nullable(); // NEW: when paid in full

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

            // Only ONE unique constraint (most specific)
            $table->unique(['organization_id', 'financial_year_id', 'document_number'], 'inv_org_fy_docnum_unique');
            $table->index(['organization_id', 'status'], 'inv_org_status_idx');
            $table->index(['organization_id', 'voucher_type_id'], 'inv_org_vt_idx');
            $table->index(['organization_id', 'customer_id'], 'inv_org_customer_idx');
            $table->index(['organization_id', 'financial_year_id', 'date'], 'inv_org_fy_date_idx');
            $table->index(['organization_id', 'due_amount'], 'inv_org_due_idx');
            $table->index('deleted_at', 'inv_deleted_at_idx');
        });

        // Credit Notes (Sales returns)
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('voucher_type_id')->constrained('voucher_types')->restrictOnDelete(); // FIXED: changed from cascade
            $table->string('document_number');
            $table->date('date')->index();
            $table->decimal('grand_total', 18, 4)->default(0);
            $table->enum('status', ['draft', 'submitted', 'posted', 'cancelled', 'rejected'])->default('draft')->index();

            // Approval & rejection tracking
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->json('rejection_details')->nullable();
            $table->unsignedTinyInteger('approval_attempts')->default(0);
            $table->timestamp('resubmitted_at')->nullable();

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

            $table->unique(['organization_id', 'financial_year_id', 'document_number'], 'cn_org_fy_docnum_unique');
            $table->index(['organization_id', 'invoice_id'], 'cn_org_invoice_idx');
            $table->index(['organization_id', 'status'], 'cn_org_status_idx');
            $table->index('deleted_at', 'cn_deleted_at_idx');
        });

        // Delivery Notes
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('sale_order_id')->nullable()->constrained('sale_orders')->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('voucher_type_id')->constrained('voucher_types')->restrictOnDelete();
            $table->string('document_number');
            $table->date('date')->index();
            $table->foreignId('rider_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['draft', 'picked', 'in_transit', 'delivered', 'cancelled', 'rejected'])->default('draft')->index();

            // Approval & rejection tracking
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->json('rejection_details')->nullable();
            $table->unsignedTinyInteger('approval_attempts')->default(0);
            $table->timestamp('resubmitted_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'document_number'], 'dn_org_docnum_unique');
            $table->index(['organization_id', 'sale_order_id'], 'dn_org_so_idx');
            $table->index(['organization_id', 'invoice_id'], 'dn_org_inv_idx');
            $table->index(['organization_id', 'status'], 'dn_org_status_idx');
            $table->index('deleted_at', 'dn_deleted_at_idx');
        });

        // =================================================================
        // 5. PURCHASE FLOW DOCUMENTS
        // =================================================================

        // Purchase Orders
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('voucher_type_id')->constrained('voucher_types')->restrictOnDelete();
            $table->foreignId('financial_year_id')->nullable()->constrained('financial_years')->restrictOnDelete();
            $table->string('document_number');
            $table->date('order_date')->index();
            $table->date('expected_receipt_date')->nullable()->index();
            $table->decimal('grand_total', 18, 4)->default(0);
            $table->enum('status', ['draft', 'submitted', 'ordered', 'received', 'cancelled', 'rejected'])->default('draft')->index();

            // Approval & rejection tracking
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->json('rejection_details')->nullable();
            $table->unsignedTinyInteger('approval_attempts')->default(0);
            $table->timestamp('resubmitted_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'document_number'], 'po_org_docnum_unique');
            $table->index(['organization_id', 'supplier_id'], 'po_org_supplier_idx');
            $table->index(['organization_id', 'status'], 'po_org_status_idx');
            $table->index(['organization_id', 'financial_year_id', 'order_date'], 'po_org_fy_date_idx');
            $table->index('deleted_at', 'po_deleted_at_idx');
        });

        // Purchase Bills
        Schema::create('purchase_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('voucher_type_id')->constrained('voucher_types')->restrictOnDelete();
            $table->string('document_number');
            $table->char('currency_code', 3)->default('PKR');
            $table->decimal('exchange_rate', 18, 6)->default(1); // Consistent precision
            $table->string('supplier_invoice_number')->nullable();
            $table->date('date')->index();
            $table->decimal('grand_total', 18, 4)->default(0);
            $table->decimal('paid_amount', 18, 4)->default(0);
            $table->decimal('due_amount', 18, 4)->default(0);
            $table->decimal('allocated_amount', 18, 4)->default(0); // NEW: track allocations
            $table->enum('status', ['draft', 'submitted', 'posted', 'paid', 'overdue', 'cancelled', 'rejected'])->default('draft')->index();

            // Approval & rejection tracking
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->json('rejection_details')->nullable();
            $table->unsignedTinyInteger('approval_attempts')->default(0);
            $table->timestamp('resubmitted_at')->nullable();
            $table->timestamp('fully_allocated_at')->nullable(); // NEW: when fully paid

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

            $table->unique(['organization_id', 'financial_year_id', 'document_number'], 'pb_org_fy_docnum_unique');
            $table->index(['organization_id', 'supplier_id'], 'pb_org_supplier_idx');
            $table->index(['organization_id', 'status'], 'pb_org_status_idx');
            $table->index(['organization_id', 'due_amount'], 'pb_org_due_idx');
            $table->index(['organization_id', 'financial_year_id', 'date'], 'pb_org_fy_date_idx');
            $table->index('deleted_at', 'pb_deleted_at_idx');
        });

        // Debit Notes (Supplier returns)
        Schema::create('debit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('purchase_bill_id')->nullable()->constrained('purchase_bills')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('voucher_type_id')->constrained('voucher_types')->restrictOnDelete(); // FIXED: changed from cascade
            $table->string('document_number');
            $table->date('date')->index();
            $table->decimal('grand_total', 18, 4)->default(0);
            $table->enum('status', ['draft', 'submitted', 'posted', 'cancelled', 'rejected'])->default('draft')->index();

            // Approval & rejection tracking
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->json('rejection_details')->nullable();
            $table->unsignedTinyInteger('approval_attempts')->default(0);
            $table->timestamp('resubmitted_at')->nullable();

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

            $table->unique(['organization_id', 'financial_year_id', 'document_number'], 'dbn_org_fy_docnum_unique');
            $table->index(['organization_id', 'purchase_bill_id'], 'dbn_org_pb_idx');
            $table->index(['organization_id', 'status'], 'dbn_org_status_idx');
            $table->index('deleted_at', 'dbn_deleted_at_idx');
        });

        // Receipt Notes (Goods received)
        Schema::create('receipt_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->foreignId('purchase_bill_id')->nullable()->constrained('purchase_bills')->nullOnDelete();
            $table->foreignId('voucher_type_id')->constrained('voucher_types')->restrictOnDelete();
            $table->string('document_number');
            $table->date('date')->index();
            $table->enum('status', ['draft', 'received', 'inspected', 'rejected', 'cancelled'])->default('draft')->index();

            // Approval & rejection tracking
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->json('rejection_details')->nullable();
            $table->unsignedTinyInteger('approval_attempts')->default(0);
            $table->timestamp('resubmitted_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'document_number'], 'grn_org_docnum_unique');
            $table->index(['organization_id', 'purchase_order_id'], 'grn_org_po_idx');
            $table->index(['organization_id', 'purchase_bill_id'], 'grn_org_pb_idx');
            $table->index(['organization_id', 'status'], 'grn_org_status_idx');
            $table->index('deleted_at', 'grn_deleted_at_idx');
        });

        // =================================================================
        // 6. DOCUMENT LINE ITEMS
        // =================================================================

        Schema::create('document_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->morphs('document');
            $table->foreignId('product_variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->foreignId('inventory_batch_id')->nullable()->constrained('inventory_batches')->nullOnDelete();
            $table->decimal('cost_of_goods_sold', 18, 4)->nullable();
            $table->decimal('quantity', 18, 4);
            $table->decimal('unit_price', 18, 4);
            $table->decimal('discount_amount', 18, 4)->default(0);
            $table->decimal('tax_rate', 8, 3)->default(0);
            $table->decimal('line_total', 18, 4);
            $table->text('notes')->nullable();
            $table->timestamps();

            // Optimized indexes
            $table->index('inventory_batch_id', 'idx_di_batch');
            $table->index('product_variant_id', 'idx_di_variant');
            $table->index('organization_id', 'idx_di_org');
            $table->index('created_at', 'idx_di_created');
            $table->index(['document_type', 'document_id', 'product_variant_id'], 'idx_di_doc_var');
            $table->index(['organization_id', 'product_variant_id'], 'idx_di_org_var');
            $table->index(['organization_id', 'document_type', 'created_at'], 'idx_di_org_doc_created');
        });

        // =================================================================
        // 7. PAYMENTS & RECEIPTS (Improved with proper reference splitting)
        // =================================================================

        // Receipts (Customer payments)
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('financial_year_id')->constrained('financial_years')->restrictOnDelete();
            $table->foreignId('voucher_type_id')->constrained('voucher_types')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('document_number')->unique();
            $table->decimal('amount', 18, 4);
            $table->decimal('unallocated_amount', 18, 4)->default(0); // NEW: track unallocated balance
            $table->date('date')->index();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->string('reference_number')->nullable();
            $table->enum('status', ['draft', 'posted', 'cancelled', 'rejected'])->default('draft')->index();

            // Approval & rejection tracking
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->unsignedTinyInteger('approval_attempts')->default(0);
            $table->timestamp('resubmitted_at')->nullable();

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
            $table->index(['organization_id', 'status'], 'receipts_org_status_idx');
            $table->index('deleted_at', 'receipts_deleted_at_idx');
        });

        // Receipt References (Split from morphs for better tracking)
        Schema::create('receipt_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained('receipts')->cascadeOnDelete();
            $table->morphs('reference'); // invoice, sale_order, credit_note, etc.
            $table->decimal('amount', 18, 4);
            $table->timestamps();
            $table->unique(['receipt_id', 'reference_type', 'reference_id'], 'receipt_ref_unique');
            $table->index(['reference_type', 'reference_id'], 'receipt_ref_doc_idx');
        });

        // Payments (Supplier payments)
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('financial_year_id')->constrained('financial_years')->restrictOnDelete();
            $table->foreignId('voucher_type_id')->constrained('voucher_types')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('document_number')->unique();
            $table->decimal('amount', 18, 4);
            $table->decimal('unallocated_amount', 18, 4)->default(0); // NEW: track unallocated balance
            $table->date('date')->index();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->string('reference_number')->nullable();
            $table->enum('status', ['draft', 'posted', 'cancelled', 'rejected'])->default('draft')->index();

            // Approval & rejection tracking
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->unsignedTinyInteger('approval_attempts')->default(0);
            $table->timestamp('resubmitted_at')->nullable();

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
            $table->index(['organization_id', 'status'], 'payments_org_status_idx');
            $table->index('deleted_at', 'payments_deleted_at_idx');
        });

        // Payment References
        Schema::create('payment_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->cascadeOnDelete();
            $table->morphs('reference'); // purchase_bill, purchase_order, debit_note, etc.
            $table->decimal('amount', 18, 4);
            $table->timestamps();
            $table->unique(['payment_id', 'reference_type', 'reference_id'], 'payment_ref_unique');
            $table->index(['reference_type', 'reference_id'], 'payment_ref_doc_idx');
        });

        // =================================================================
        // 8. LEGACY ALLOCATION TABLES (Deprecated - kept for backward compatibility)
        // =================================================================
        // These are replaced by receipt_references and payment_references above.
        // Keep them empty or remove after migration.

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
        // Drop in reverse dependency order
        Schema::dropIfExists('payment_allocations');
        Schema::dropIfExists('receipt_allocations');
        Schema::dropIfExists('payment_references');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('receipt_references');
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

        Schema::dropIfExists('document_locks');
        Schema::dropIfExists('document_rejection_history');
        Schema::dropIfExists('document_status_history');
        Schema::dropIfExists('document_audit_logs');
        Schema::dropIfExists('document_links');
        Schema::dropIfExists('document_comments');
        Schema::dropIfExists('document_attachments');
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('document_number_sequences');
        Schema::dropIfExists('voucher_types');
    }
};