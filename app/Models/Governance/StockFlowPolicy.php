<?php

namespace App\Models\Governance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockFlowPolicy extends Model
{
    use SoftDeletes;
    protected $table = 'stock_flow_policies';

    protected $fillable = [
        'organization_id',
        'from_type',
        'from_id',
        'to_type',
        'to_id',
        'allowed',
        'description',
        'is_locked',
    ];

    protected $casts = [
        'allowed' => 'boolean',
        'is_locked' => 'boolean',
    ];
}
