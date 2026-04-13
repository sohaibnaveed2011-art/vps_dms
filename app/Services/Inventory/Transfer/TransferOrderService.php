<?php

namespace App\Services\Inventory\Transfer;

use App\Models\Inventory\TransferOrder;
use App\Models\Inventory\TransferOrderItem;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TransferOrderService
{
    public function __construct(
        protected TransferAllocationService $allocation,
        protected TransferDispatchService $dispatch,
        protected TransferReceiveService $receive
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    public function create(array $data): TransferOrder
    {
        return DB::transaction(function () use ($data) {

            $order = TransferOrder::create([
                ...$data,
                'status' => 'draft',
            ]);

            foreach ($data['items'] as $item) {

                $order->items()->create([
                    'organization_id'    => $order->organization_id,
                    'product_variant_id' => $item['product_variant_id'],
                    'inventory_batch_id' => $item['inventory_batch_id'] ?? null,
                    'quantity'           => $item['quantity'],
                    'unit_cost'          => $item['unit_cost'],
                    'line_total'         => $item['quantity'] * $item['unit_cost'],
                ]);
            }

            return $order->load('items');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Allocate
    |--------------------------------------------------------------------------
    */

    public function allocate(TransferOrder $order): void
    {
        if ($order->status !== 'draft') {
            throw new RuntimeException("Only draft orders can be allocated.");
        }

        DB::transaction(function () use ($order) {

            foreach ($order->items as $item) {
                $this->allocation->allocateItem($order, $item);
            }

            $order->update([
                'status' => 'approved',
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Dispatch (Partial Supported)
    |--------------------------------------------------------------------------
    */

    public function dispatch(TransferOrder $order, array $dispatchItems, int $userId): void
    {
        if (! in_array($order->status, ['approved','in_transit'])) {
            throw new RuntimeException("Order not dispatchable.");
        }

        DB::transaction(function () use ($order, $dispatchItems, $userId) {

            foreach ($dispatchItems as $payload) {
                $this->dispatch->dispatchItem($order, $payload, $userId);
            }

            $order->update([
                'status' => 'in_transit',
                'in_transit_by' => $userId,
                'in_transit_at' => now(),
            ]);
        });
    }

    public function dispatchSerials(TransferOrderItem $item)
    {
        foreach ($item->reservation->serials as $serial) {

            if ($serial->status !== 'reserved') {
                throw new RuntimeException("Serial not reserved.");
            }

            $serial->update([
                'status' => 'in_transit',
                'stock_location_id' => $this->getTransitLocationId()
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Receive (Partial Supported)
    |--------------------------------------------------------------------------
    */

    public function receive(TransferOrder $order, array $receiveItems, int $userId): void
    {
        if ($order->status !== 'in_transit') {
            throw new RuntimeException("Order not in transit.");
        }

        DB::transaction(function () use ($order, $receiveItems, $userId) {

            foreach ($receiveItems as $payload) {
                $this->receive->receiveItem($order, $payload, $userId);
            }

            // If all received → completed
            if ($this->receive->isFullyReceived($order)) {
                $order->update([
                    'status' => 'completed',
                    'completed_by' => $userId,
                    'completed_at' => now(),
                ]);
            }
        });
    }

    public function receiveSerials(TransferOrderItem $item, int $destinationLocationId)
    {
        foreach ($item->reservation->serials as $serial) {

            if ($serial->status !== 'in_transit') {
                throw new RuntimeException("Serial not in transit.");
            }

            $serial->update([
                'status' => 'available',
                'stock_location_id' => $destinationLocationId
            ]);
        }
    }

    protected function getTransitLocationId(int $organizationId): int
    {
        return \App\Models\Inventory\StockLocation::query()
            ->where('organization_id', $organizationId)
            ->where('type', 'transit') // add this column if missing
            ->value('id')
            ?? throw new RuntimeException("Transit location not configured.");
    }


}
