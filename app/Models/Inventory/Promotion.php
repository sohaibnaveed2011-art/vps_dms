<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use App\Models\Inventory\PromotionScope;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Inventory\PromotionTarget;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $organization_id
 * @property string $name
 * @property string $type
 * @property numeric $value
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property int $priority
 * @property bool $stackable
 * @property numeric|null $min_order_amount
 * @property int|null $usage_limit
 * @property int $used_count
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PromotionScope> $scopes
 * @property-read int|null $scopes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, PromotionTarget> $targets
 * @property-read int|null $targets_count
 * @mixin \Eloquent
 */
class Promotion extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'organization_id',
        'name',
        'type',
        'value',
        'priority',
        'stackable',
        'min_order_amount',
        'usage_limit',
        'used_count',
        'start_date',
        'end_date',
        'is_active',
        // 'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'stackable' => 'boolean',
        'value' => 'decimal:4',
        'min_order_amount' => 'decimal:4',
    ];

    public function scopes()
    {
        return $this->hasMany(PromotionScope::class);
    }

    public function targets()
    {
        return $this->hasMany(PromotionTarget::class);
    }

    /**
     * Scope for active promotions
     */
    public function scopeActive(Builder $query): Builder
    {
        $now = now();
        return $query->where('is_active', true)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now);
    }

    /**
     * Scope for stackable promotions
     */
    public function scopeStackable(Builder $query): Builder
    {
        return $query->where('stackable', true);
    }

    /**
     * Scope for promotions applicable to a specific organization
     */
    public function scopeForOrganization(Builder $query, int $orgId): Builder
    {
        return $query->where('organization_id', $orgId);
    }

    /**
     * Check if promotion is currently valid
     */
    public function isValid(): bool
    {
        return $this->is_active &&
            now()->between($this->start_date, $this->end_date);
    }

    /**
     * Check if promotion has remaining usage
     */
    public function hasRemainingUsage(): bool
    {
        return is_null($this->usage_limit) || $this->used_count < $this->usage_limit;
    }

    /**
     * Check if promotion is applicable for subtotal
     */
    public function meetsMinimumOrder(float $subtotal): bool
    {
        return is_null($this->min_order_amount) || $subtotal >= $this->min_order_amount;
    }
}