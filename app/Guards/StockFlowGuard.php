<?php

namespace App\Guards;

use App\Exceptions\ForbiddenException;
use App\Services\Policy\StockFlowPolicyService;

class StockFlowGuard
{
    public function __construct(
        protected StockFlowPolicyService $policy
    ) {}

    public function enforce(
        int $organizationId,
        string $fromType,
        ?int $fromId,
        string $toType,
        ?int $toId
    ): void {

        if (! $this->policy->isAllowed(
            $organizationId,
            $fromType,
            $fromId,
            $toType,
            $toId
        )) {
            throw new ForbiddenException('Stock flow not allowed.');
        }
    }
}