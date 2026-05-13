<?php

namespace App\Models\Governance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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
