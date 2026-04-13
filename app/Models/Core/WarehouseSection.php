<?php

namespace App\Models\Core;

use App\Models\Core\Organization;
use App\Models\Core\SectionCategory;
use App\Models\Core\Warehouse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
