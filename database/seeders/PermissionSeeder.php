<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Standard CRUD permission generator
     */
    private function crud(string $domain, string $resource, bool $withWorkflow = false): array
    {
        $permissions = [
            "{$domain}.{$resource}.view",        // index
            "{$domain}.{$resource}.create",      // store
            "{$domain}.{$resource}.show",        // show
            "{$domain}.{$resource}.update",      // update
            "{$domain}.{$resource}.destroy",     // delete
            "{$domain}.{$resource}.restore",     // restore
            "{$domain}.{$resource}.forceDelete", // forceDelete
        ];

        if ($withWorkflow) {
            $permissions = array_merge($permissions, [
                "{$domain}.{$resource}.review",
                "{$domain}.{$resource}.approve",
            ]);
        }

        return $permissions;
    }

    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            /* ======================
             | RBAC (SYSTEM LEVEL)
             ====================== */
            ...$this->crud('auth', 'role'),
            ...$this->crud('auth', 'permission'),

            /* ======================
             | USER MANAGEMENT
             ====================== */
            ...$this->crud('auth', 'user'),
            'user.assignment.view',
            'user.assignment.show',
            'user.assignment.create',
            'user.assignment.update',
            'user.context.view',
            'user.context.show',

            /* ======================
             | CORE MASTER DATA
             ====================== */
            ...$this->crud('core', 'organization'),
            ...$this->crud('core', 'branch'),
            ...$this->crud('core', 'warehouse'),
            ...$this->crud('core', 'warehouseSection'),
            ...$this->crud('core', 'outlet'),
            ...$this->crud('core', 'outletSection'),
            ...$this->crud('core', 'sectionCategory'),
            ...$this->crud('core', 'tax'),
            ...$this->crud('core', 'financialYear'),

            /* ======================
             | PARTNERS
             ====================== */
            ...$this->crud('partner', 'partnerCategory'),
            ...$this->crud('partner', 'customer'),
            ...$this->crud('partner', 'supplier'),

            /* ======================
             | INVENTORY
             ====================== */
            ...$this->crud('inventory', 'brand'),
            ...$this->crud('inventory', 'brandModel'),
            ...$this->crud('inventory', 'category'),
            ...$this->crud('inventory', 'unit'),
            ...$this->crud('inventory', 'variation'),
            ...$this->crud('inventory', 'variationValue'),
            ...$this->crud('inventory', 'product'), 
            ...$this->crud('inventory', 'stockTransaction'),
            ...$this->crud('inventory', 'priceList'),
            ...$this->crud('inventory', 'coupon'),
            ...$this->crud('inventory', 'promotion'),


            /* ======================
             | ACCOUNT
             ====================== */
            ...$this->crud('accounts', 'account'),
            'accounts.account.export',
            'accounts.account.import',
            
            /* ======================
             | VOUCHERS (Sale/Purchase)
             ====================== */
            ...$this->crud('voucher', 'saleOrder', true),
            ...$this->crud('voucher', 'invoice', true),
            ...$this->crud('voucher', 'deliveryNote', true),
            ...$this->crud('voucher', 'creditNote', true),
            ...$this->crud('voucher', 'purchaseOrder', true),
            ...$this->crud('voucher', 'purchaseBill', true),
            ...$this->crud('voucher', 'receiptNote', true),
            ...$this->crud('voucher', 'debitNote', true),
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        /* ======================================================
         | ROLES
         ====================================================== */

        // ADMIN
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin->syncPermissions(
            Permission::where(function ($q) {
                $q->where('name', 'like', 'auth.%')
                  ->orWhere('name', 'like', 'user.%')
                  ->orWhere('name', 'like', 'rbac.%')
                  ->orWhere('name', 'like', 'core.%')
                  ->orWhere('name', 'like', 'inventory.%')
                  ->orWhere('name', 'like', 'partner.%')
                  ->orWhere('name', 'like', 'voucher.%')
                  ->orWhere('name', 'like', 'accounts.%');
            })->get()
        );
    }
}
