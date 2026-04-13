<?php

namespace App\Services\Inventory\Serial;

use App\Models\Inventory\SerialNumber;

class SerialTrackingService
{
    public function moveSerial(
        int $serialId,
        int $newLocationId,
        string $status
    ): void {

        $serial = SerialNumber::findOrFail($serialId);

        $serial->update([
            'stock_location_id' => $newLocationId,
            'status' => $status,
        ]);
    }
}
