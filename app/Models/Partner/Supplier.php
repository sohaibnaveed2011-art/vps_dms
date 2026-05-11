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
 * @property int $payment_terms_days
 * @property numeric $current_balance
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read PartnerCategory|null $category
 * @property-read Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PurchaseBill> $purchaseBills
 * @property-read int|null $purchase_bills_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PurchaseOrder> $purchaseOrders
 * @property-read int|null $purchase_orders_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereCnic($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereContactNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereContactPerson($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereCurrentBalance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereIncorporationNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereNtn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier wherePartnerCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier wherePaymentTermsDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereStrn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Supplier withoutTrashed()
 * @mixin \Eloquent
 */
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
