<?php

namespace App\Models\Inventory;

use App\Models\Inventory\Promotion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $promotion_id
 * @property string $scopeable_type
 * @property int $scopeable_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Promotion $promotion
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionScope able()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionScope newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionScope newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionScope onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionScope query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionScope whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionScope whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionScope whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionScope wherePromotionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionScope whereScopeableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionScope whereScopeableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionScope whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionScope withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionScope withoutTrashed()
 * @mixin \Eloquent
 */
class PromotionScope extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'promotion_id',
        'scopeable_type',
        'scopeable_id',
    ];

    protected $casts = [
        'scopeable_id' => 'integer',
    ];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function scopeable()
    {
        return $this->morphTo();
    }
}