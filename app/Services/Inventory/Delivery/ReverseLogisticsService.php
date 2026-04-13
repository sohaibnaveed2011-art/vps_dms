<?php

namespace App\Services\Inventory\Delivery;

use App\Services\Inventory\Core\UniversalStockEngine;
use App\Services\Inventory\Core\ConditionService;

class ReverseLogisticsService
{
    public function __construct(
        protected UniversalStockEngine $engine,
        protected ConditionService $condition
    ) {}

    public function initiateReturn(array $payload): void
    {
        /*
            Required payload:
            - organization_id
            - source_location_id (customer location)
            - original_location_id (warehouse/outlet where delivery originated)
            - product_variant_id
            - inventory_batch_id
            - quantity
            - unit_cost
            - reference_type
            - reference_id
            - created_by
        */

        $orgId = $payload['organization_id'];

        $this->engine->transfer([
            ...$payload,
            'destination_location_id' => $payload['original_location_id'],
            'condition_from_id' => $this->condition->getId('GOOD', $orgId),
            'condition_to_id'   => $this->condition->getId('RETURNED', $orgId),
            'transaction_type'  => 'reverse_pickup',
        ]);
    }
}
