<?php

namespace App\Models\Inventory;

use App\Models\Inventory\Promotion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $promotion_id
 * @property string $targetable_type
 * @property int $targetable_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Promotion $promotion
 * @property-read Model|\Eloquent $targetable
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionTarget newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionTarget newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionTarget onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionTarget query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionTarget whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionTarget whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionTarget whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionTarget wherePromotionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionTarget whereTargetableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionTarget whereTargetableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionTarget whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionTarget withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PromotionTarget withoutTrashed()
 * @mixin \Eloquent
 */
class PromotionTarget extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'promotion_id',
        'targetable_type',
        'targetable_id',
    ];

    protected $casts = [
        'targetable_id' => 'integer',
    ];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function targetable()
    {
        return $this->morphTo();
    }
}