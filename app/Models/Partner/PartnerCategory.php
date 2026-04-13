<?php

namespace App\Models\Partner;

use App\Models\Core\Organization;
use App\Models\Partner\Customer;
use App\Models\Partner\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartnerCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'partner_category_id');
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class, 'partner_category_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helpers
    public function canBeDeleted(): bool
    {
        return $this->customers()->count() === 0 &&
               $this->suppliers()->count() === 0;
    }
}
