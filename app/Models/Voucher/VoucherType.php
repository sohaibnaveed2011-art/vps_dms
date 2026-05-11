<?php

namespace App\Models\Voucher;

use App\Models\Core\Organization;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string|null $prefix
 * @property string $module
 * @property int $next_number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Organization $organization
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherType whereModule($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherType whereNextNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherType whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherType wherePrefix($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|VoucherType whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class VoucherType extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'name',
        'prefix',
        'module',
        'next_number',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
}
