<?php

namespace App\Services\Inventory\Transfer;

use App\Models\Inventory\TransferOrder;
use App\Models\Inventory\TransferOrderItem;
use App\Services\Inventory\Core\InventoryBalanceService;
use RuntimeException;

class TransferAllocationService
{
    public function __construct(
        protected InventoryBalanceService $balance
    ) {}

    public function allocateItem(TransferOrder $order, TransferOrderItem $item): void
    {
        $available = $this->getAvailableQuantity(
            $order,
            $item
        );

        if ($available < $item->quantity) {
            throw new RuntimeException("Insufficient stock for allocation.");
        }

        $item->update([
            'allocated_quantity' => $item->quantity,
            'is_allocated' => true,
        ]);
    }

    protected function getAvailableQuantity(
        TransferOrder $order,
        TransferOrderItem $item
    ): float {

        return \App\Models\Inventory\InventoryBalance::query()
            ->where('organization_id', $order->organization_id)
            ->where('stock_location_id', $order->source_location_id)
            ->where('product_variant_id', $item->product_variant_id)
            ->sum('quantity');
    }
}
