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
                ->nullOnDelete();

            $table->string('name');
            $table->string('code', 50);
            $table->text('description')->nullable();
            $table->unsignedInteger('level')->default(0);
            $table->string('currency_code', 3)->default('PKR');
            $table->decimal('opening_balance', 18, 6)->default(0);
            $table->date('opening_balance_date')->nullable();
            $table->boolean('is_taxable')->default(false);
            $table->boolean('automatic_postings_disabled')->default(false);

            $table->enum('type', ['Asset', 'Liability', 'Equity', 'Revenue', 'Expense']);
            $table->enum('normal_balance', ['Debit', 'Credit'])->nullable();

            $table->boolean('is_group')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

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

            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('financial_year_id')
                ->constrained('financial_years')
                ->restrictOnDelete();

            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('outlet_id')->nullable()->constrained()->nullOnDelete();

            $table->string('voucher_no')->index();
            $table->string('document_number')->nullable()->index();

            $table->date('date')->index();

            $table->morphs('reference');

            $table->boolean('is_posted')->default(false);
            $table->boolean('is_reversed')->default(false);

            $table->text('memo')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'financial_year_id', 'date']);
            $table->unique(['organization_id', 'financial_year_id', 'voucher_no'], 'unique_voucher_per_fy');
            $table->index(['is_posted', 'date']);
            $table->index(['is_posted', 'is_reversed']);
            $table->index(['voucher_no', 'organization_id']);
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('journal_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('account_id')
                ->constrained('accounts')
                ->restrictOnDelete();

            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('outlet_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('debit', 18, 6)->default(0);
            $table->decimal('credit', 18, 6)->default(0);

            $table->text('line_memo')->nullable();
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['account_id']);
            $table->index(['journal_id', 'account_id']);
            $table->index(['account_id', 'debit', 'credit']);
            $table->index(['branch_id', 'warehouse_id', 'outlet_id']);
            $table->index(['account_id', 'created_at']);
            $table->index(['journal_id']);
        });

        /*
        |--------------------------------------------------------------------------
        | CHECK Constraints (Database-level Safety)
        |--------------------------------------------------------------------------
        */

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            try {
                $version = DB::selectOne('SELECT VERSION() as version')->version;
                $mysqlVersion = preg_replace('/[^0-9.]/', '', $version);
                
                if (version_compare($mysqlVersion, '8.0.16', '>=')) {
                    DB::statement('
                        ALTER TABLE journal_lines 
                        ADD CONSTRAINT chk_debit_credit_valid 
                        CHECK (
                            debit >= 0 
                            AND credit >= 0 
                            AND ((debit > 0 AND credit = 0) OR (debit = 0 AND credit > 0))
                        )
                    ');
                } else {
                    DB::statement("
                        CREATE TRIGGER validate_journal_line_before_insert 
                        BEFORE INSERT ON journal_lines 
                        FOR EACH ROW 
                        BEGIN
                            IF (NEW.debit < 0 OR NEW.credit < 0 OR (NEW.debit > 0 AND NEW.credit > 0) OR (NEW.debit = 0 AND NEW.credit = 0)) THEN
                                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Invalid debit/credit combination';
                            END IF;
                        END
                    ");
                }
            } catch (\Throwable $e) {
                \Log::warning('Could not create CHECK constraint: ' . $e->getMessage());
            }
        }

        if ($driver === 'pgsql') {
            DB::statement("
                CREATE OR REPLACE VIEW unbalanced_journals AS
                SELECT 
                    j.id,
                    j.voucher_no,
                    j.date,
                    SUM(jl.debit) as total_debit,
                    SUM(jl.credit) as total_credit
                FROM journals j
                JOIN journal_lines jl ON jl.journal_id = j.id
                WHERE j.is_posted = true
                GROUP BY j.id, j.voucher_no, j.date
                HAVING ABS(SUM(jl.debit) - SUM(jl.credit)) > 0.0001
            ");
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP VIEW IF EXISTS unbalanced_journals');
            DB::statement('ALTER TABLE journal_lines DROP CONSTRAINT IF EXISTS chk_debit_credit_valid');
        } elseif ($driver === 'mysql') {
            DB::statement('DROP TRIGGER IF EXISTS validate_journal_line_before_insert');
            DB::statement('ALTER TABLE journal_lines DROP CHECK IF EXISTS chk_debit_credit_valid');
        }

        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journals');
        Schema::dropIfExists('accounts');
    }
};