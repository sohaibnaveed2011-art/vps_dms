<?php

namespace App\Models\Governance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $role_id
 * @property string $subject
 * @property string|null $action
 * @property string|null $voucher_type
 * @property string|null $hierarchy_type
 * @property int|null $hierarchy_id
 * @property string $effect
 * @property bool $is_locked
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Spatie\Permission\Models\Role $role
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuthorityPolicy newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuthorityPolicy newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuthorityPolicy onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuthorityPolicy query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuthorityPolicy whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuthorityPolicy whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuthorityPolicy whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuthorityPolicy whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuthorityPolicy whereEffect($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuthorityPolicy whereHierarchyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuthorityPolicy whereHierarchyType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuthorityPolicy whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuthorityPolicy whereIsLocked($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuthorityPolicy whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuthorityPolicy whereRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuthorityPolicy whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuthorityPolicy whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuthorityPolicy whereVoucherType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuthorityPolicy withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuthorityPolicy withoutTrashed()
 * @mixin \Eloquent
 */
class AuthorityPolicy extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'organization_id',
        'role_id',
        'subject',
        'action',
        'voucher_type',
        'hierarchy_type',
        'hierarchy_id',
        'effect',
        'is_locked',
        'description',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
    ];

    public function role()
    {
        return $this->belongsTo(
            config('permission.models.role'),
            'role_id'
        );
    }
}
