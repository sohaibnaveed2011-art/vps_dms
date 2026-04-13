<?php

namespace Database\Seeders\Policy;

use Illuminate\Database\Seeder;
use App\Models\Governance\OrganizationPolicy;

class OrganizationPolicySeeder extends Seeder
{
    /**
     * Seed policies for ONE organization
     */
    public function runForOrganization(int $orgId): void
    {
        $this->seedFeatures($orgId);
        $this->seedHierarchy($orgId);
        $this->seedInventory($orgId);
    }

    /* =========================================================
     | FEATURES
     ========================================================= */

    private function seedFeatures(int $orgId): void
    {
        OrganizationPolicy::updateOrCreate(
            [
                'organization_id' => $orgId,
                'key' => 'organization.features',
            ],
            [
                'category' => 'features',
                'description' => 'Enable or disable organization-level features',
                'value' => [
                    'core' => true,
                    'inventory' => true,
                    'partners' => true,
                    'procurement' => true,
                    'sales' => true,
                    'manufacturing' => false,
                    'multi_branch' => true,
                    'approval_workflow' => true,
                    'budgeting' => false,
                    'asset_management' => false,
                ],
                'is_locked' => false,
            ]
        );
    }

    /* =========================================================
     | HIERARCHY
     ========================================================= */

    private function seedHierarchy(int $orgId): void
    {
        OrganizationPolicy::updateOrCreate(
            [
                'organization_id' => $orgId,
                'key' => 'organization.structure',
            ],
            [
                'category' => 'hierarchy',
                'description' => 'Organization hierarchy configuration',
                'value' => [
                    'hierarchy' => [
                        'branch' => true,
                        'warehouse' => true,
                        'outlet' => true,
                        'cost_center' => false,
                        'project' => false,
                    ],
                    'limits' => [
                        'branch' => 1,
                        'warehouse' => 1,
                        'outlet' => 2,
                    ],
                    'enforce_strict_parent' => true,
                    'max_depth' => 7,
                    'allowed_paths' => [
                        ['organization'],
                        ['organization', 'branch'],
                        ['organization', 'warehouse'],
                        ['organization', 'outlet'],
                        ['organization', 'branch', 'outlet'],
                        ['organization', 'warehouse', 'outlet'],
                        ['organization', 'branch', 'warehouse', 'outlet'],
                    ],
                ],
                'is_locked' => false,
            ]
        );
    }

    /* =========================================================
     | INVENTORY
     ========================================================= */

    private function seedInventory(int $orgId): void
    {
        OrganizationPolicy::updateOrCreate(
            [
                'organization_id' => $orgId,
                'key' => 'organization.inventory',
            ],
            [
                'category' => 'inventory',
                'description' => 'Inventory control configuration',
                'value' => [
                    'stock_tracking' => true,
                    'allow_negative_stock' => false,
                    'batch_tracking' => true,
                    'serial_tracking' => false,
                    'expiry_tracking' => true,
                    'valuation_method' => 'FIFO',
                    'reservation_enabled' => true,
                    'auto_reserve_on_sales' => true,
                    'low_stock_alert' => [
                        'enabled' => true,
                        'default_threshold' => 10,
                    ],
                    'transfer' => [
                        'require_approval' => true,
                        'auto_receive' => false,
                    ],
                    'adjustment' => [
                        'require_reason' => true,
                        'require_approval' => false,
                    ],
                ],
                'is_locked' => false,
            ]
        );
    }
}
