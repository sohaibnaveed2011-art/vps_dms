<?php

namespace App\Services\Voucher;

use App\Guards\AuthorityGuard;
use App\Models\User;
use App\Models\Voucher\SaleOrder;
use Illuminate\Validation\ValidationException;

class VoucherWorkflowService
{
    public function __construct(
        protected AuthorityGuard $authorityGuard
    ) {}

    protected function resolveHierarchy(User $user): array
    {
        $context = $user->activeContext()
            ?? throw ValidationException::withMessages([
                'context' => 'No active context',
            ]);

        return match (true) {
            $context->outlet_id => ['outlet', $context->outlet_id],
            $context->warehouse_id => ['warehouse', $context->warehouse_id],
            $context->branch_id => ['branch', $context->branch_id],
            default => ['organization', $context->organization_id],
        };
    }

    public function review(User $user, SaleOrder $order): void
    {
        if (! $order->canBeReviewed()) {
            throw ValidationException::withMessages([
                'review' => 'Sale order cannot be reviewed',
            ]);
        }

        [$level, $id] = $this->resolveHierarchy($user);

        $this->authorityGuard->enforce(
            user: $user,
            subject: 'voucher',
            action: 'review',
            voucherType: 'sale_order'
        );

        $order->markReviewed($user->id);
        $order->update(['status' => 'reviewed']);
    }

    public function approve(User $user, SaleOrder $order): void
    {
        if (! $order->canBeApproved()) {
            throw ValidationException::withMessages([
                'approve' => 'Sale order cannot be approved',
            ]);
        }

        [$level, $id] = $this->resolveHierarchy($user);

        $this->authorityGuard->enforce(
            user: $user,
            subject: 'voucher',
            action: 'approve',
            voucherType: 'sale_order'
        );

        $order->markApproved($user->id);
        $order->update([
            'status' => 'approved',
            'fulfilled_by_level' => $level,
            'fulfilled_by_id' => $id,
        ]);
    }
}
