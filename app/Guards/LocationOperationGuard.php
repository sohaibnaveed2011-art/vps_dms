<?php

namespace App\Guards;

use App\Contracts\ContextScope;
use App\Models\User;

final class LocationOperationGuard
{
    public static function canOperateOn(
        User $user,
        ContextScope $location
    ): bool {

        $context = $user->activeContext();

        if (! $context) return false;

        if ($context->organization_id !== $location->organizationId()) {
            return false;
        }

        if ($context->branch_id &&
            $context->branch_id !== $location->branchId()) {
            return false;
        }

        if ($context->warehouse_id &&
            $context->warehouse_id !== $location->warehouseId()) {
            return false;
        }

        if ($context->outlet_id &&
            $context->outlet_id !== $location->outletId()) {
            return false;
        }

        return true;
    }
}

