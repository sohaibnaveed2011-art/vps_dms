<?php


namespace App\Models\Inventory;

use App\Models\Core\Organization;
use App\Models\Inventory\PriceListItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PriceList extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'currency',
        'is_default',
        'is_active',
        'starts_at',
        'ends_at',
        'priority',
    ];

    public function items()
    {
        return $this->hasMany(PriceListItem::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
