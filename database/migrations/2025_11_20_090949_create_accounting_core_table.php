<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Chart of Accounts
        |--------------------------------------------------------------------------
        */
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete(); // allows hierarchical COA

            $table->string('name');
            $table->string('code');

            $table->enum('type', ['Asset', 'Liability', 'Equity', 'Revenue', 'Expense']);
            $table->enum('normal_balance', ['Debit', 'Credit'])->nullable(); // Helps prevent directional posting errors

            $table->boolean('is_group')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['organization_id', 'code']);
            $table->index(['organization_id', 'type']);
        });

        /*
        |--------------------------------------------------------------------------
        | Journals (Header)
        |--------------------------------------------------------------------------
        */
        Schema::create('journals', function (Blueprint $table) {
            $table->id();

            // Scope
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_year_id')
                ->constrained('financial_years')
                ->restrictOnDelete();

            // Operational Dimensions
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('outlet_id')->nullable()->constrained()->nullOnDelete();

            // Voucher grouping
            $table->string('voucher_no')->index();
            $table->string('document_number')->nullable()->index();

            $table->date('date')->index();

            // Source document
            $table->morphs('reference');

            // Status control
            $table->boolean('is_posted')->default(false);
            $table->boolean('is_reversed')->default(false);

            $table->text('memo')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['organization_id', 'financial_year_id', 'date']);
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('journal_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('account_id')
                ->constrained('accounts')
                ->restrictOnDelete();

            // Optional extended cost center logic
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('outlet_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('debit', 18, 6)->default(0);
            $table->decimal('credit', 18, 6)->default(0);

            $table->text('line_memo')->nullable();

            $table->timestamps();

            $table->index(['account_id']);
            $table->index(['journal_id', 'account_id']);
        });

        /*
        |--------------------------------------------------------------------------
        | CHECK Constraints (Database-level Safety)
        |--------------------------------------------------------------------------
        */

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {

            DB::statement('
                ALTER TABLE journal_lines
                ADD CONSTRAINT chk_debit_credit_valid
                CHECK (
                    debit >= 0
                    AND credit >= 0
                    AND NOT (debit > 0 AND credit > 0)
                    AND NOT (debit = 0 AND credit = 0)
                )
            ');

        } elseif ($driver === 'mysql') {

            try {
                $verRow = DB::selectOne('SELECT VERSION() as v');
                $version = $verRow ? $verRow->v : null;

                if ($version && preg_match('/^(\d+\.\d+\.\d+)/', $version, $m)) {
                    if (version_compare($m[1], '8.0.16', '>=')) {

                        DB::statement('
                            ALTER TABLE journal_lines
                            ADD CONSTRAINT chk_debit_credit_valid
                            CHECK (
                                debit >= 0
                                AND credit >= 0
                                AND NOT (debit > 0 AND credit > 0)
                                AND NOT (debit = 0 AND credit = 0)
                            )
                        ');
                    }
                }
            } catch (\Throwable $e) {
                // Fallback to application-level validation
            }
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            try {
                DB::statement('ALTER TABLE journal_lines DROP CONSTRAINT IF EXISTS chk_debit_credit_valid');
            } catch (\Throwable $e) {
            }
        }

        if ($driver === 'mysql') {
            try {
                DB::statement('ALTER TABLE journal_lines DROP CHECK chk_debit_credit_valid');
            } catch (\Throwable $e) {
            }
        }

        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journals');
        Schema::dropIfExists('accounts');
    }
};
