<?php

namespace App\Models\Core;

use App\Models\Core\Organization;
use App\Models\Core\SectionCategory;
use App\Models\Core\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $warehouse_id
 * @property int|null $parent_section_id
 * @property int|null $section_category_id
 * @property string|null $hierarchy_path
 * @property int $level
 * @property string $name
 * @property string|null $code
 * @property string|null $zone
 * @property string|null $aisle
 * @property string|null $rack
 * @property string|null $shelf
 * @property string|null $bin
 * @property string|null $description
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, WarehouseSection> $childSections
 * @property-read int|null $child_sections_count
 * @property-read Organization $organization
 * @property-read WarehouseSection|null $parentSection
 * @property-read SectionCategory|null $sectionCategory
 * @property-read Warehouse $warehouse
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseSection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseSection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseSection onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseSection query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseSection whereAisle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseSection whereBin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseSection whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseSection whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseSection whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseSection whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseSection whereHierarchyPath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseSection whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseSection whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseSection whereLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseSection whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseSection whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseSection whereParentSectionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseSection whereRack($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseSection whereSectionCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseSection whereShelf($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseSection whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseSection whereWarehouseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseSection whereZone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseSection withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WarehouseSection withoutTrashed()
 * @mixin \Eloquent
 */
class WarehouseSection extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'warehouse_id',
        'parent_section_id',
        'section_category_id',
        'hierarchy_path',
        'level',
        'name',
        'code',
        'zone',
        'aisle',
        'rack',
        'shelf',
        'bin',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function parentSection()
    {
        return $this->belongsTo(WarehouseSection::class, 'parent_section_id');
    }

    public function childSections()
    {
        return $this->hasMany(WarehouseSection::class, 'parent_section_id')
                    ->orderBy('name');
    }

    public function sectionCategory()
    {
        return $this->belongsTo(SectionCategory::class);
    }
}
