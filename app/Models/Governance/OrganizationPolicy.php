<?php

namespace App\Models\Governance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
