<?php

namespace App\Models\Core;

use App\Models\Core\Organization;
use App\Models\Core\Outlet;
use App\Models\Core\SectionCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
