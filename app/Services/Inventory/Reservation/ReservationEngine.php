<?php

namespace App\Services\Inventory\Reservation;

use App\Models\Inventory\InventoryReservation;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\SerialNumber;
use App\Models\Inventory\ProductVariant;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReservationEngine
{
    protected int $scale = 6;

    public function reserve(array $payload): InventoryReservation
    {
        return DB::transaction(function () use ($payload) {

            /** @var ProductVariant $variant */
            $variant = $payload['variant'];

            return $variant->is_serial_tracked
                ? $this->reserveSerial($payload, $variant)
                : $this->reserveQuantity($payload);
        });
    }

    protected function reserveSerial(array $payload, ProductVariant $variant): InventoryReservation
    {
        $qty = (int) $payload['quantity'];

        /** @var \Illuminate\Database\Eloquent\Collection<int, SerialNumber> $serials */
        $serials = SerialNumber::query()
            ->select('serial_numbers.*')
            ->where('organization_id', $payload['organization_id'])
            ->where('stock_location_id', $payload['stock_location_id'])
            ->where('product_variant_id', $variant->id)
            ->where('condition_id', $payload['condition_id'])
            ->where('status', 'available')
            ->lockForUpdate()
            ->limit($qty)
            ->get();

        if ($serials->count() < $qty) {
            throw new RuntimeException("Not enough serials available.");
        }

        $reservation = InventoryReservation::create([
            ...$payload,
            'quantity' => $qty,
            'status'   => 'active',
        ]);

        foreach ($serials as $serial) {

            $serial->status = 'reserved';
            $serial->reserved_at = now();
            $serial->save();

            $reservation->serials()->attach($serial->id);
        }

        return $reservation;
    }

    protected function reserveQuantity(array $payload): InventoryReservation
    {
        $balance = $this->lockBalance($payload);

        $available = $balance->quantity - $balance->reserved_quantity;

        if ($available < $payload['quantity']) {
            throw new RuntimeException("Insufficient stock.");
        }

        $balance->reserved_quantity += $payload['quantity'];
        $balance->save();

        return InventoryReservation::create([
            ...$payload,
            'status' => 'active',
        ]);
    }

    public function consume(InventoryReservation $reservation): void
    {
        DB::transaction(function () use ($reservation) {

            if ($reservation->variant->is_serial_tracked) {

                foreach ($reservation->serials as $serial) {
                    $serial->status = 'in_transit';
                    $serial->save();
                }

            } else {

                $balance = $this->lockBalance($reservation->toArray());
                $balance->reserved_quantity -= $reservation->quantity;
                $balance->save();
            }

            $reservation->update([
                'status'      => 'consumed',
                'consumed_at' => now(),
            ]);
        });
    }

    public function release(InventoryReservation $reservation): void
    {
        DB::transaction(function () use ($reservation) {

            if ($reservation->variant->is_serial_tracked) {

                foreach ($reservation->serials as $serial) {
                    $serial->status = 'available';
                    $serial->reserved_at = null;
                    $serial->save();
                }

            } else {

                $balance = $this->lockBalance($reservation->toArray());
                $balance->reserved_quantity -= $reservation->quantity;
                $balance->save();
            }

            $reservation->update([
                'status'      => 'released',
                'released_at' => now(),
            ]);
        });
    }

    protected function lockBalance(array $payload): InventoryBalance
    {
        return InventoryBalance::query()
            ->where('organization_id', $payload['organization_id'])
            ->where('stock_location_id', $payload['stock_location_id'])
            ->where('product_variant_id', $payload['product_variant_id'])
            ->where('inventory_batch_id', $payload['inventory_batch_id'])
            ->where('condition_id', $payload['condition_id'])
            ->lockForUpdate()
            ->firstOrFail();
    }
}
