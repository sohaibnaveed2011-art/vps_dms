<?php

namespace App\Observers;

use App\Jobs\SetupNewOrganization;
use App\Models\Core\Organization;

class OrganizationObserver
{
    /**
     * Handle the Organization "created" event.
     */
    public function created(Organization $organization): void
    {
        // Dispatch the job to the background
        SetupNewOrganization::dispatch($organization);
    }
}
