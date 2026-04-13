<?php

namespace App\Observers;

use App\Models\Core\OutletSection;
use App\Models\Inventory\StockLocation;

class OutletSectionObserver
{
    /**
     * Handle the OutletSection "created" event.
     */
    public function created(OutletSection $section): void
    {
        StockLocation::firstOrCreate(
            [
                'locatable_type' => OutletSection::class,
                'locatable_id'   => $section->id,
            ],
            [
                'organization_id' => $section->outlet->organization_id,
                'branch_id'       => $section->outlet->branch_id,
                'name'            => $section->name,
                'code'            => $section->code,
                'is_active'       => true,
            ]
        );
    }

    /**
     * Handle the OutletSection "updated" event.
     */
    public function updated(OutletSection $section): void
    {
        $location = StockLocation::where([
            'locatable_type' => OutletSection::class,
            'locatable_id'   => $section->id,
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
     * Handle the OutletSection "deleted" event.
     * Soft-disable the stock location.
     */
    public function deleted(OutletSection $section): void
    {
        StockLocation::where([
            'locatable_type' => OutletSection::class,
            'locatable_id'   => $section->id,
        ])->update([
            'is_active' => false,
        ]);
    }

    /**
     * Handle the OutletSection "restored" event.
     */
    public function restored(OutletSection $section): void
    {
        StockLocation::where([
            'locatable_type' => OutletSection::class,
            'locatable_id'   => $section->id,
        ])->update([
            'is_active' => true,
        ]);
    }
}
