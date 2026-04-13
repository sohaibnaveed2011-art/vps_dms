<?php

use App\Models\User;
// use App\Services\Auth\AuthorizationService;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('branch.{branchId}', function ($user, $branchId) {
    // Allow access if user belongs to branch or has admin role
    return isset($user->branch_id) && (int) $user->branch_id === (int) $branchId || method_exists($user, 'hasRole') && $user->hasRole('admin');
});

Broadcast::channel('organization.{orgId}', function ($user, $orgId) {
    // Allow if user belongs to the organization or has admin role
    return isset($user->organization_id) && (int) $user->organization_id === (int) $orgId || method_exists($user, 'hasRole') && $user->hasRole('admin');
});

Broadcast::channel(
    'sale-orders.review.{scope}.{scopeId}',
    function (User $user, string $scope, int $scopeId) {

        $context = $user->activeContext();

        if (! $context) {
            return false;
        }

        // 1️⃣ Context must match the channel scope
        $contextMatches = match ($scope) {
            'outlet' => $context->outlet_id === $scopeId,
            'warehouse' => $context->warehouse_id === $scopeId,
            'branch' => $context->branch_id === $scopeId,
            'organization' => $context->organization_id === $scopeId,
            default => false,
        };

        if (! $contextMatches) {
            return false;
        }

        // 2️⃣ User must have approval permission at THIS scope
        // return app(AuthorizationService::class)
        //     ->canApproveSaleOrder($user, $context);
    }
);
