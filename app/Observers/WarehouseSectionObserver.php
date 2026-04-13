<?php

namespace App\Observers;

use App\Models\Core\WarehouseSection;
use App\Models\Inventory\StockLocation;

class WarehouseSectionObserver
{
    /**
     * Handle the WarehouseSection "created" event.
     */
    public function created(WarehouseSection $section): void
    {
        $location = new StockLocation([
            'organization_id' => $section->warehouse->organization_id,
            'branch_id' => $section->warehouse->branch_id,
            'name' => $section->name,
            'code' => $section->code,
            'is_active' => true,
        ]);

        // 🔥 THIS LINE IS THE KEY
        $location->locatable()->associate($section);

        $location->save();
    }

    /**
     * Handle the WarehouseSection "updated" event.
     * Sync cached metadata (name/code) if changed.
     */
    public function updated(WarehouseSection $section): void
    {
        $location = StockLocation::where([
            'locatable_type' => WarehouseSection::class,
            'locatable_id' => $section->id,
        ])->first();

        if (! $location) {
            return;
        }

        $dirty = false;

        if ($section->wasChanged('name')) {
            $location->name = $section->name;
            $dirty = true;
        }

        if ($section->wasChanged('code')) {
            $location->code = $section->code;
            $dirty = true;
        }

        if ($dirty) {
            $location->save();
        }
    }

    /**
     * Handle the WarehouseSection "deleted" event.
     * Soft-disable the stock location (do NOT delete).
     */
    public function deleted(WarehouseSection $section): void
    {
        StockLocation::where([
            'locatable_type' => WarehouseSection::class,
            'locatable_id' => $section->id,
        ])->update([
            'is_active' => false,
        ]);
    }

    /**
     * Handle the WarehouseSection "restored" event.
     */
    public function restored(WarehouseSection $section): void
    {
        StockLocation::where([
            'locatable_type' => WarehouseSection::class,
            'locatable_id' => $section->id,
        ])->update([
            'is_active' => true,
        ]);
    }
}
