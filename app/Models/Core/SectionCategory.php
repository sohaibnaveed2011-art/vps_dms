<?php

namespace App\Models\Core;

use App\Models\Core\Organization;
use App\Models\Core\OutletSection;
use App\Models\Core\WarehouseSection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SectionCategory extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'organization_id',
        'name',
        'description',
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
