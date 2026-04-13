<?php

namespace App\Models\Partner;

use App\Models\Core\Organization;
use App\Models\Voucher\Payment;
use App\Models\Voucher\PurchaseBill;
use App\Models\Voucher\PurchaseOrder;
use App\Models\Partner\PartnerCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
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
        'payment_terms_days',
        'current_balance',
        'is_active',
    ];

    protected $casts = [
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

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchaseBills(): HasMany
    {
        return $this->hasMany(PurchaseBill::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
