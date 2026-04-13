<?php

namespace App\Services\Inventory\Core;

use App\Models\Inventory\ProductVariant;
use App\Models\Inventory\SerialNumber;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UniversalStockEngine
{
    public function __construct(
        protected StockMovementService $movement
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Universal Transfer Entry
    |--------------------------------------------------------------------------
    */

    public function transfer(array $payload): void
    {
        DB::transaction(function () use ($payload) {

            $variant = ProductVariant::query()
                ->lockForUpdate()
                ->findOrFail($payload['product_variant_id']);

            $quantity = abs((float) $payload['quantity']);

            if ($quantity <= 0) {
                throw new RuntimeException("Invalid quantity.");
            }

            /*
            |--------------------------------------------------------------------------
            | SERIAL TRACKED FLOW
            |--------------------------------------------------------------------------
            */

            if ($variant->is_serial_tracked) {
                $this->handleSerialTransfer($payload);
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | QUANTITY BASED FLOW
            |--------------------------------------------------------------------------
            */

            // 1️⃣ Deduct from source
            $this->movement->move([
                ...$payload,
                'stock_location_id' => $payload['source_location_id'],
                'condition_id'      => $payload['condition_from_id'],
                'quantity'          => -$quantity,
            ]);

            // 2️⃣ Add to destination
            $this->movement->move([
                ...$payload,
                'stock_location_id' => $payload['destination_location_id'],
                'condition_id'      => $payload['condition_to_id'],
                'quantity'          => $quantity,
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Serial Transfer Handler (Fully Typed)
    |--------------------------------------------------------------------------
    */

    protected function handleSerialTransfer(array $payload): void
    {
        $serialIds = $payload['serial_ids'] ?? [];

        if (empty($serialIds)) {
            throw new RuntimeException("Serial IDs required.");
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Inventory\SerialNumber> $serials */
        $serials = SerialNumber::query()
            ->where('organization_id', $payload['organization_id'])
            ->where('product_variant_id', $payload['product_variant_id'])
            ->whereIn('id', $serialIds)
            ->lockForUpdate()
            ->get();

        if ($serials->count() !== count($serialIds)) {
            throw new RuntimeException("Invalid or unauthorized serial detected.");
        }

        foreach ($serials as $serial) {

            if ($serial->stock_location_id !== $payload['source_location_id']) {
                throw new RuntimeException("Serial location mismatch.");
            }

            if ($serial->condition_id !== $payload['condition_from_id']) {
                throw new RuntimeException("Serial condition mismatch.");
            }

            if (! in_array($serial->status, ['available','reserved','in_transit'])) {
                throw new RuntimeException("Serial not transferable.");
            }

            $serial->stock_location_id = $payload['destination_location_id'];
            $serial->condition_id      = $payload['condition_to_id'];
            $serial->save();
        }
    }

}
