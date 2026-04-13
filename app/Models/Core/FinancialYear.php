<?php

namespace App\Models\Core;

use App\Models\Core\Organization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialYear extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'organization_id',
        'name',
        'start_date',
        'end_date',
        'is_closed',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_closed' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
