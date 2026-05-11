<?php

namespace App\Models\Partner;

use App\Models\Voucher\Invoice;
use App\Models\Voucher\Receipt;
use App\Models\Voucher\SaleOrder;
use App\Models\Core\Organization;
use Illuminate\Database\Eloquent\Model;
use App\Models\Partner\PartnerCategory;
use App\Models\Inventory\CustomerCoupon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int $id
 * @property int $organization_id
 * @property int|null $partner_category_id
 * @property string $name
 * @property string|null $cnic
 * @property string|null $ntn
 * @property string|null $strn
 * @property string|null $incorporation_no
 * @property string|null $contact_person
 * @property string|null $contact_no
 * @property string|null $email
 * @property string|null $address
 * @property numeric|null $longitude
 * @property numeric|null $latitude
 * @property numeric $credit_limit
 * @property int $payment_terms_days
 * @property numeric $current_balance
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read PartnerCategory|null $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, CustomerCoupon> $customerCoupons
 * @property-read int|null $customer_coupons_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Receipt> $receipts
 * @property-read int|null $receipts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, SaleOrder> $saleOrders
 * @property-read int|null $sale_orders_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCnic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereContactNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereContactPerson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCreditLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereCurrentBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereIncorporationNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereNtn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer wherePartnerCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer wherePaymentTermsDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereStrn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Customer withoutTrashed()
 * @mixin \Eloquent
 */
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

    public function customerCoupons()
    {
        return $this->hasMany(CustomerCoupon::class);
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
