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
        | USER CONTEXTS
        |--------------------------------------------------------------------------
        | Defines WHERE a user is currently operating.
        | Active context = ended_at IS NULL
        | Only ONE active context per user allowed (application-enforced).
        |--------------------------------------------------------------------------
        */
        Schema::create('user_contexts', function (Blueprint $table) {

            $table->id();

            // --------------------------------------------------
            // IDENTITY
            // --------------------------------------------------

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();

            // --------------------------------------------------
            // OPTIONAL HIERARCHY
            // --------------------------------------------------

            $table->foreignId('branch_id')
                ->nullable()
                ->constrained('branches')
                ->nullOnDelete();

            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->nullOnDelete();

            $table->foreignId('outlet_id')
                ->nullable()
                ->constrained('outlets')
                ->nullOnDelete();

            $table->foreignId('cash_register_id')
                ->nullable()
                ->constrained('cash_registers')
                ->nullOnDelete();

            // --------------------------------------------------
            // CONTEXT LIFECYCLE
            // --------------------------------------------------

            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();

            $table->timestamps();

            // --------------------------------------------------
            // INDEXES
            // --------------------------------------------------

            // Fast lookup for active context
            $table->index(['user_id', 'ended_at'], 'user_active_context_idx');

            $table->index('organization_id');
            $table->index('branch_id');
            $table->index('warehouse_id');
            $table->index('outlet_id');
            $table->index('cash_register_id');
        });

        /*
        |--------------------------------------------------------------------------
        | USER ASSIGNMENTS
        |--------------------------------------------------------------------------
        | Defines WHAT a user is allowed to do at a given scope.
        | Polymorphic scope (organization / branch / warehouse / outlet / etc.)
        |--------------------------------------------------------------------------
        */
        Schema::create('user_assignments', function (Blueprint $table) {

            $table->id();

            // --------------------------------------------------
            // USER & ROLE
            // --------------------------------------------------

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('role_id')
                ->constrained('roles')
                ->cascadeOnDelete();

            // --------------------------------------------------
            // POLYMORPHIC SCOPE
            // --------------------------------------------------

            $table->morphs('assignable');

            // --------------------------------------------------
            // ASSIGNMENT LIFECYCLE
            // --------------------------------------------------

            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->foreignId('assigned_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            // --------------------------------------------------
            // INDEXES (Performance Optimized)
            // --------------------------------------------------

            // Fast active lookup per user
            $table->index(['user_id', 'ended_at'], 'user_active_assignments_idx');

            // Role resolution lookup
            $table->index(['user_id', 'role_id']);

            // Optional: prevent duplicate historical rows with same timestamp
            $table->unique(
                ['user_id', 'role_id', 'assignable_type', 'assignable_id', 'started_at'],
                'user_scope_started_unique'
            );
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('user_assignments');
        Schema::dropIfExists('user_contexts');
    }
};
