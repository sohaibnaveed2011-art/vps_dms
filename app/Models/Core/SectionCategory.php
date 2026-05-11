<?php

namespace App\Models\Core;

use App\Models\Core\Organization;
use App\Models\Core\OutletSection;
use App\Models\Core\WarehouseSection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Organization $organization
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OutletSection> $outletSections
 * @property-read int|null $outlet_sections_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WarehouseSection> $warehouseSections
 * @property-read int|null $warehouse_sections_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SectionCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SectionCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SectionCategory onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SectionCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SectionCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SectionCategory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SectionCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SectionCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SectionCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SectionCategory whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SectionCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SectionCategory withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SectionCategory withoutTrashed()
 * @mixin \Eloquent
 */
class SectionCategory extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'is_active',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function warehouseSections(): HasMany
    {
        return $this->hasMany(WarehouseSection::class, 'section_category_id');
    }

    public function outletSections(): HasMany
    {
        return $this->hasMany(OutletSection::class, 'section_category_id');
    }
}
