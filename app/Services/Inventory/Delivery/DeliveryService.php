<?php

namespace App\Services\Inventory\Delivery;

use App\Services\Inventory\Core\UniversalStockEngine;
use App\Services\Inventory\Core\ConditionService;

class DeliveryService
{
    public function __construct(
        protected UniversalStockEngine $engine,
        protected ConditionService $condition
    ) {}

    public function assignToRider(array $payload): void
    {
        $orgId = $payload['organization_id'];

        $this->engine->transfer([
            ...$payload,
            'condition_from_id' => $this->condition->getId('GOOD', $orgId),
            'condition_to_id'   => $this->condition->getId('TRANSIT', $orgId),
            'transaction_type'  => 'rider_assigned',
        ]);
    }

    public function deliver(array $payload): void
    {
        $orgId = $payload['organization_id'];

        $this->engine->transfer([
            ...$payload,
            'condition_from_id' => $this->condition->getId('TRANSIT', $orgId),
            'condition_to_id'   => $this->condition->getId('GOOD', $orgId),
            'transaction_type'  => 'delivered',
        ]);
    }
}
