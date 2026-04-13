<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |---------------------------------------------------------------------------
        | 1. CASH REGISTERS
        |---------------------------------------------------------------------------
        */
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            // Polymorphic ownership
            $table->morphs('registerable');
            // registerable_type
            // registerable_id
            $table->string('name');
            $table->enum('status', ['open', 'closed'])->default('closed');
            $table->boolean('is_locked')->default(false);
            $table->timestamps();
            $table->softDeletes();
            // Unique per scope (not per organization)
            $table->unique([
                'registerable_type',
                'registerable_id',
                'name',
            ], 'register_scope_name_unique');
            $table->index(['organization_id']);
        });

        /*
        |---------------------------------------------------------------------------
        | 2. POS SESSIONS (SHIFT MANAGEMENT)
        |---------------------------------------------------------------------------
        */
        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('cash_register_id')->constrained('cash_registers')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->boolean('is_open')->default(true);
            $table->timestamps();
            $table->index(['cash_register_id', 'is_open']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sessions');
        Schema::dropIfExists('cash_registers');
    }
};
