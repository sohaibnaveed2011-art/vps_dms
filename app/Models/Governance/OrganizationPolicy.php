<?php

namespace App\Models\Governance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property string|null $category
 * @property string $key
 * @property array<array-key, mixed>|null $value
 * @property bool $is_locked
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationPolicy newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationPolicy newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationPolicy onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationPolicy query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationPolicy whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationPolicy whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationPolicy whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationPolicy whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationPolicy whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationPolicy whereIsLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationPolicy whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationPolicy whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationPolicy whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationPolicy whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationPolicy withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrganizationPolicy withoutTrashed()
 * @mixin \Eloquent
 */
class OrganizationPolicy extends Model
{
    use SoftDeletes;
    protected $table = 'organization_policies';

    protected $fillable = [
        'organization_id',
        'category',
        'key',
        'value',
        'description',
        'is_locked',
    ];

    protected $casts = [
        'value' => 'array',
        'is_locked' => 'boolean',
    ];
}
