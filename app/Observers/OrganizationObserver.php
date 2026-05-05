<?php

namespace App\Observers;

use App\Jobs\SetupNewOrganization;
use App\Models\Core\Organization;
use Illuminate\Support\Facades\Log;

class OrganizationObserver
{
    /**
     * Handle the Organization "created" event.
     */
    public function created(Organization $organization): void
    {
        Log::info('📝 Organization created, dispatching job', ['org_id' => $organization->id]);
        
        // Dispatch job to queue
        SetupNewOrganization::dispatch($organization);
        
        Log::info('✅ Job dispatched successfully', ['org_id' => $organization->id]);
    }
}
