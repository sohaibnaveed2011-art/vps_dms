<?php

namespace App\Services\Inventory\Damage;

use App\Services\Inventory\Core\StockMovementService;
use App\Services\Inventory\Core\ConditionService;

class DamageWorkflowService
{
    public function __construct(
        protected StockMovementService $movement,
        protected ConditionService $condition
    ) {}

    public function markDamaged(array $payload): void
    {
        $orgId = $payload['organization_id'];

        $goodConditionId = $this->condition->getId('GOOD', $orgId);
        $damagedConditionId = $this->condition->getId('DAMAGED', $orgId);

        // 1️⃣ Remove from GOOD
        $this->movement->move([
            ...$payload,
            'condition_id' => $goodConditionId,
            'quantity' => -abs($payload['quantity']),
            'transaction_type' => 'damage_out'
        ]);

        // 2️⃣ Add to DAMAGED
        $this->movement->move([
            ...$payload,
            'condition_id' => $damagedConditionId,
            'quantity' => abs($payload['quantity']),
            'transaction_type' => 'damage_in'
        ]);
    }
}
