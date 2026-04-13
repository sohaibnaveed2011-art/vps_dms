<?php

namespace App\Services\Inventory\Core;

use App\Models\Inventory\StockLocation;
use RuntimeException;

class LocationService
{
    public function getLocationId(
        int $organizationId,
        string $type,
        int $locatableId
    ): int {

        $location = StockLocation::query()
            ->where('organization_id', $organizationId)
            ->where('locatable_type', $type)
            ->where('locatable_id', $locatableId)
            ->first();

        if (! $location) {
            throw new RuntimeException("Location not found.");
        }

        return $location->id;
    }

    public function getTransitLocationId(int $organizationId): int
    {
        return StockLocation::query()
            ->where('organization_id', $organizationId)
            ->where('type', 'transit')
            ->value('id')
            ?? throw new RuntimeException("Transit location not configured.");
    }
}
