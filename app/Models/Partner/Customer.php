<?php

namespace App\Models\Partner;

use App\Models\Core\Organization;
use App\Models\Voucher\Invoice;
use App\Models\Voucher\Receipt;
use App\Models\Voucher\SaleOrder;
use App\Models\Partner\PartnerCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'partner_category_id',
        'name',
        'cnic',
        'ntn',
        'strn',
        'incorporation_no',
        'contact_person',
        'contact_no',
        'email',
        'address',
        'longitude',
        'latitude',
        'credit_limit',
        'payment_terms_days',
        'current_balance',
        'is_active',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:4',
        'current_balance' => 'decimal:4',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PartnerCategory::class, 'partner_category_id');
    }

    public function saleOrders(): HasMany
    {
        return $this->hasMany(SaleOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
