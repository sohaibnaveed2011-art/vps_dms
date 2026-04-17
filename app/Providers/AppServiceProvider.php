<?php

namespace App\Providers;

use App\Models\Core\Branch;
use App\Models\Core\Organization;
use App\Models\Core\Outlet;
use App\Models\Core\OutletSection;
use App\Models\Core\Warehouse;
use App\Models\Core\WarehouseSection;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductVariant;
use App\Models\User;
use App\Models\Voucher\CashRegister;
use App\Observers\OrganizationObserver;
use App\Observers\OutletSectionObserver;
use App\Observers\WarehouseSectionObserver;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * ------------------------------------------------------------------
     * MORPH MAP
     * ------------------------------------------------------------------
     *
     * IMPORTANT:
     * - Keys are persisted in DB.
     * - NEVER rename once in production.
     */
    protected const MORPH_MAP = [
        'organization'      => Organization::class,
        'branch'            => Branch::class,
        'warehouse'         => Warehouse::class,
        'outlet'            => Outlet::class,
        'warehouse_section' => WarehouseSection::class,
        'outlet_section'    => OutletSection::class,
        'cash_register'     => CashRegister::class,
        'user'              => User::class,
        'product'           => Product::class,
        'variant'           => ProductVariant::class,
    ];

    /**
     * ------------------------------------------------------------------
     * REGISTER
     * ------------------------------------------------------------------
     */
    public function register(): void
    {
        // $this->app->bind(
        // );
    }

    /**
     * ------------------------------------------------------------------
     * BOOT
     * ------------------------------------------------------------------
     */
    public function boot(): void
    {
        $this->registerMorphMap();
        $this->registerObservers();
        $this->registerEventListeners();
    }

    /**
     * ------------------------------------------------------------------
     * Morph Map Registration (STRICT MODE)
     * ------------------------------------------------------------------
     *
     * enforceMorphMap() guarantees:
     * - Only aliases are stored in DB
     * - Full class names are rejected
     * - Cleaner, shorter polymorphic values
     */
    protected function registerMorphMap(): void
    {
        Relation::enforceMorphMap(self::MORPH_MAP);
    }

    /**
     * ------------------------------------------------------------------
     * Model Observers
     * ------------------------------------------------------------------
     *
     * Used for:
     * - Auto stock location creation
     * - Metadata sync
     * - Soft-disable cascading logic
     */
    protected function registerObservers(): void
    {
        Organization::observe(OrganizationObserver::class);
        WarehouseSection::observe(WarehouseSectionObserver::class);
        OutletSection::observe(OutletSectionObserver::class);
    }

    /**
     * ------------------------------------------------------------------
     * Event Listeners
     * ------------------------------------------------------------------
     *
     * Business workflow events
     */
    protected function registerEventListeners(): void
    {
        $listeners = [
            /*
            VoucherPosted::class    => [HandleVoucherPosted::class],
            VoucherApproved::class  => [HandleVoucherApproved::class],
            VoucherUpdated::class   => [HandleVoucherUpdated::class],
            VoucherCancelled::class => [HandleVoucherCancelled::class],

            ReceiptNotePosted::class  => [HandleReceiptNotePosted::class],
            DeliveryNotePosted::class => [HandleDeliveryNotePosted::class],
            CreditNotePosted::class   => [HandleCreditNotePosted::class],
            DebitNotePosted::class    => [HandleDebitNotePosted::class],
            */
        ];

        foreach ($listeners as $event => $handlers) {
            foreach ((array) $handlers as $handler) {
                Event::listen($event, $handler);
            }
        }
    }
}
