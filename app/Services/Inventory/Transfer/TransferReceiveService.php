<?php

namespace App\Services\Inventory\Transfer;

use App\Models\Inventory\TransferOrder;
use App\Services\Inventory\Core\UniversalStockEngine;
use App\Services\Inventory\Core\ConditionService;
use App\Services\Inventory\Core\LocationService;

class TransferReceiveService
{
    public function __construct(
        protected UniversalStockEngine $engine,
        protected ConditionService $condition,
        protected LocationService $location
    ) {}

    public function receiveItem(
        TransferOrder $order,
        array $payload,
        int $userId
    ): void {

        $item = $order->items()
            ->where('id', $payload['item_id'])
            ->firstOrFail();

        $orgId = $order->organization_id;

        $transitConditionId = $this->condition->getId('TRANSIT', $orgId);
        $goodConditionId    = $this->condition->getId('GOOD', $orgId);

        $transitLocationId = $this->location->getTransitLocationId($orgId);

        $this->engine->transfer([
            'organization_id'       => $orgId,
            'source_location_id'    => $transitLocationId,
            'destination_location_id'=> $order->destination_location_id,
            'product_variant_id'    => $item->product_variant_id,
            'inventory_batch_id'    => $item->inventory_batch_id,
            'condition_from_id'     => $transitConditionId,
            'condition_to_id'       => $goodConditionId,
            'quantity'              => abs($payload['quantity']),
            'unit_cost'             => $item->unit_cost,
            'reference_type'        => TransferOrder::class,
            'reference_id'          => $order->id,
            'created_by'            => $userId,
            'transaction_type'      => 'transfer_received',
        ]);
    }

    public function isFullyReceived(TransferOrder $order): bool
    {
        return ! $order->items()
            ->whereColumn('allocated_quantity', '>', 'received_quantity')
            ->exists();
    }
}
