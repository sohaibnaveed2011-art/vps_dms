<?php

namespace App\Models\Partner;

use App\Models\Core\Organization;
use App\Models\Partner\Customer;
use App\Models\Partner\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string $type
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Customer> $customers
 * @property-read int|null $customers_count
 * @property-read Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Supplier> $suppliers
 * @property-read int|null $suppliers_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartnerCategory active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartnerCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartnerCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartnerCategory onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartnerCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartnerCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartnerCategory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartnerCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartnerCategory whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartnerCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartnerCategory whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartnerCategory whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartnerCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartnerCategory withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PartnerCategory withoutTrashed()
 * @mixin \Eloquent
 */
class PartnerCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'partner_category_id');
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class, 'partner_category_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helpers
    public function canBeDeleted(): bool
    {
        return $this->customers()->count() === 0 &&
               $this->suppliers()->count() === 0;
    }
}
