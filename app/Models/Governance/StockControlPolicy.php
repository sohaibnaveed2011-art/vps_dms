<?php

namespace App\Models\Governance;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockControlPolicy extends Model
{
    use SoftDeletes;
    protected $table = 'stock_control_policies';

    protected $fillable = [
        'organization_id',
        'key',
        'value',
        'description',
    ];

    protected $casts = [
        'value' => 'array',
    ];
}
