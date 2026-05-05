<?php

namespace App\Jobs;

use App\Models\Core\Organization;
use Database\Seeders\Policy\OrganizationPolicySeeder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SetupNewOrganization implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, WithoutModelEvents;
    public int $timeout = 120;
    public int $tries = 3;
    public function __construct(public Organization $organization) {
        Log::info('🏗️ Job instantiated', ['org_id' => $organization->id]);
    }

    public function handle(): void
    {
        Log::info('🚀 Processing organization setup', ['org_id' => $this->organization->id]);
        
        // Simulate setup work (replace with your actual logic)
        sleep(2);

        $orgId = $this->organization->id;
        app(OrganizationPolicySeeder::class)->runForOrganization($orgId);

        Log::info('✅ Organization setup completed', [
            'org_id' => $this->organization->id,
            'status' => 'active'
        ]);
    }
}