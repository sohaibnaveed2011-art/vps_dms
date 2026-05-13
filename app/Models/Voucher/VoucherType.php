<?php

namespace App\Models\Vouchers;

use App\Models\Core\Organization;
use Illuminate\Database\Eloquent\Model;

class VoucherType extends Model
{
    protected $fillable = [
        'organization_id',
        'name',
        'prefix',
        'module',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /* ======================
     |  Relationships
     ====================== */

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function saleOrders()
    {
        return $this->hasMany(SaleOrder::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function creditNotes()
    {
        return $this->hasMany(CreditNote::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchaseBills()
    {
        return $this->hasMany(PurchaseBill::class);
    }

    public function sequences()
    {
        return $this->hasMany(DocumentNumberSequence::class);
    }
}