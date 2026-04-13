<?php

namespace App\Services\Inventory\Transfer;

use App\Models\Inventory\TransferOrder;
use App\Services\Inventory\Core\StockMovementService;
use RuntimeException;

class TransferDispatchService
{
    public function __construct(
        protected StockMovementService $movement
    ) {}

    public function dispatchItem(
        TransferOrder $order,
        array $payload,
        int $userId
    ): void {

        $item = $order->items()
            ->where('id', $payload['item_id'])
            ->firstOrFail();

        if ($payload['quantity'] > $item->allocated_quantity) {
            throw new RuntimeException("Dispatch exceeds allocated.");
        }

        // 1️⃣ Deduct from source
        $this->movement->move([
            'organization_id'    => $order->organization_id,
            'stock_location_id'  => $order->source_location_id,
            'product_variant_id' => $item->product_variant_id,
            'inventory_batch_id' => $item->inventory_batch_id,
            'condition_id'       => 1,
            'quantity'           => -$payload['quantity'],
            'unit_cost'          => $item->unit_cost,
            'transaction_type'   => 'transfer_dispatch',
            'reference_type'     => TransferOrder::class,
            'reference_id'       => $order->id,
            'created_by'         => $userId,
        ]);

        // 2️⃣ Add to transit
        $this->movement->move([
            'organization_id'    => $order->organization_id,
            'stock_location_id'  => $order->destination_location_id,
            'product_variant_id' => $item->product_variant_id,
            'inventory_batch_id' => $item->inventory_batch_id,
            'condition_id'       => 1,
            'quantity'           => $payload['quantity'],
            'unit_cost'          => $item->unit_cost,
            'transaction_type'   => 'transfer_in_transit',
            'reference_type'     => TransferOrder::class,
            'reference_id'       => $order->id,
            'created_by'         => $userId,
        ]);
    }
}
