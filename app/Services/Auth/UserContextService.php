<?php

namespace App\Services\Auth;

use App\Models\Auth\UserAssignment;
use App\Models\Auth\UserContext;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UserContextService
{
    public function __construct(
        protected ContextHierarchyResolver $resolver
    ) {}

    public function switchByAssignment(User $user, int $assignmentId): UserContext
    {
        return DB::transaction(function () use ($user, $assignmentId) {

            $assignment = UserAssignment::with('assignable')
                ->where('id', $assignmentId)
                ->where('user_id', $user->id)
                ->whereNull('ended_at')
                ->first()
                ?? throw new RuntimeException('Invalid assignment.');

            $resolved = $this->resolver->resolve($assignment->assignable);

            UserContext::where('user_id', $user->id)
                ->whereNull('ended_at')
                ->update(['ended_at' => now()]);

            $context = UserContext::create([
                'user_id' => $user->id,
                'organization_id' => $resolved['organization_id'],
                'branch_id' => $resolved['branch_id'],
                'warehouse_id' => $resolved['warehouse_id'],
                'outlet_id' => $resolved['outlet_id'],
                'started_at' => now(),
            ]);

            AuditLogger::log(
                $user->id,
                'context_switched',
                null,
                $resolved
            );

            return $context;
        });
    }
    public function switchByContext(
    User $user,
    int $organizationId,
    ?int $branchId = null,
    ?int $warehouseId = null,
    ?int $outletId = null
    ): UserContext {

        return DB::transaction(function () use (
            $user,
            $organizationId,
            $branchId,
            $warehouseId,
            $outletId
        ) {

            // Validate user has at least one assignment at this hierarchy
            $hasAccess = UserAssignment::query()
                ->where('user_id', $user->id)
                ->whereNull('ended_at')
                ->where(function ($q) use ($organizationId, $branchId, $warehouseId, $outletId) {

                    $q->orWhere(fn ($sub) =>
                        $sub->where('assignable_type', 'organization')
                            ->where('assignable_id', $organizationId)
                    );

                    if ($branchId) {
                        $q->orWhere(fn ($sub) =>
                            $sub->where('assignable_type', 'branch')
                                ->where('assignable_id', $branchId)
                        );
                    }

                    if ($warehouseId) {
                        $q->orWhere(fn ($sub) =>
                            $sub->where('assignable_type', 'warehouse')
                                ->where('assignable_id', $warehouseId)
                        );
                    }

                    if ($outletId) {
                        $q->orWhere(fn ($sub) =>
                            $sub->where('assignable_type', 'outlet')
                                ->where('assignable_id', $outletId)
                        );
                    }
                })
                ->exists();

            if (! $hasAccess) {
                throw new RuntimeException('Invalid context selection.');
            }

            // Expire old context
            UserContext::where('user_id', $user->id)
                ->whereNull('ended_at')
                ->update(['ended_at' => now()]);

            $context = UserContext::create([
                'user_id' => $user->id,
                'organization_id' => $organizationId,
                'branch_id' => $branchId,
                'warehouse_id' => $warehouseId,
                'outlet_id' => $outletId,
                'started_at' => now(),
            ]);

            AuditLogger::log(
                $user->id,
                'context_switched',
                null,
                [
                    'organization_id' => $organizationId,
                    'branch_id' => $branchId,
                    'warehouse_id' => $warehouseId,
                    'outlet_id' => $outletId,
                ]
            );

            return $context;
        });
    }
}
