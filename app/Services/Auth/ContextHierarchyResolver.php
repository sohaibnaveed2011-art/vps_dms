<?php

namespace App\Services\Auth;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;

class ContextHierarchyResolver
{
    public function resolve(Model $assignable): array
    {
        return match ($assignable->getMorphClass()) {

            'organization' => [
                'organization_id' => $assignable->id,
                'branch_id' => null,
                'warehouse_id' => null,
                'outlet_id' => null,
                'path' => ['organization'],
            ],

            'branch' => [
                'organization_id' => $assignable->organization_id,
                'branch_id' => $assignable->id,
                'warehouse_id' => null,
                'outlet_id' => null,
                'path' => ['organization', 'branch'],
            ],

            'warehouse' => [
                'organization_id' => $assignable->organization_id,
                'branch_id' => $assignable->branch_id,
                'warehouse_id' => $assignable->id,
                'outlet_id' => null,
                'path' => ['organization', 'branch', 'warehouse'],
            ],

            'outlet' => [
                'organization_id' => $assignable->organization_id,
                'branch_id' => $assignable->branch_id,
                'warehouse_id' => $assignable->warehouse_id,
                'outlet_id' => $assignable->id,
                'path' => ['organization', 'branch', 'warehouse', 'outlet'],
            ],

            default => throw new RuntimeException(
                'Unsupported assignable type.'
            ),
        };
    }
}
