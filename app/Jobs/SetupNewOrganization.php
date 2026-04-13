<?php

namespace App\Jobs;

use App\Models\Core\Organization;
use Database\Seeders\Policy\OrganizationPolicySeeder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
class SetupNewOrganization implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, WithoutModelEvents;

    public function __construct(public Organization $organization) {}

    public function handle(): void
    {
        $orgId = $this->organization->id;
        app(OrganizationPolicySeeder::class)->runForOrganization($orgId);
    }
}