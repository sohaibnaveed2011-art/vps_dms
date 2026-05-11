<?php

namespace App\Models\Core;

use App\Models\Core\Organization;
use App\Models\Core\Outlet;
use App\Models\Core\SectionCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $outlet_id
 * @property int|null $parent_section_id
 * @property int|null $section_category_id
 * @property string $name
 * @property string|null $code
 * @property bool $is_pos_counter
 * @property int $display_order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Organization $organization
 * @property-read Outlet $outlet
 * @property-read OutletSection|null $parentSection
 * @property-read SectionCategory|null $sectionCategory
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutletSection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutletSection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutletSection onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutletSection query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutletSection whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutletSection whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutletSection whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutletSection whereDisplayOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutletSection whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutletSection whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutletSection whereIsPosCounter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutletSection whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutletSection whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutletSection whereOutletId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutletSection whereParentSectionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutletSection whereSectionCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutletSection whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutletSection withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OutletSection withoutTrashed()
 * @mixin \Eloquent
 */
class OutletSection extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'outlet_id',
        'section_category_id',
        'parent_section_id',
        'name',
        'code',
        'is_pos_counter',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_pos_counter' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function sectionCategory()
    {
        return $this->belongsTo(SectionCategory::class);
    }
        public function parentSection()
    {
        return $this->belongsTo(OutletSection::class, 'parent_section_id');
    }
}
