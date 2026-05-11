<?php

namespace App\Models\Voucher;

use App\Models\Core\Branch;
use App\Models\Core\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $cash_register_id
 * @property int|null $user_id
 * @property \Illuminate\Support\Carbon $started_at
 * @property \Illuminate\Support\Carbon|null $ended_at
 * @property int $is_open
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Branch|null $branch
 * @property-read \App\Models\Voucher\CashRegister $cashRegister
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Voucher\Invoice> $invoices
 * @property-read int|null $invoices_count
 * @property-read Organization $organization
 * @property-read User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PosSession newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PosSession newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PosSession query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PosSession whereCashRegisterId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PosSession whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PosSession whereEndedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PosSession whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PosSession whereIsOpen($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PosSession whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PosSession whereStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PosSession whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PosSession whereUserId($value)
 * @mixin \Eloquent
 */
class PosSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'branch_id', // Nullable in schema for optional hierarchy
        'cash_register_id',
        'user_id',
        'started_at',
        'ended_at',
        'cash_opening_balance',
        'cash_actual_balance',
        'cash_expected_balance',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'cash_opening_balance' => 'decimal:4',
        'cash_actual_balance' => 'decimal:4',
        'cash_expected_balance' => 'decimal:4',
    ];

    public function organization(): BelongsTo { return $this->belongsTo(Organization::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); } // Handles nullable branch_id
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function cashRegister(): BelongsTo { return $this->belongsTo(CashRegister::class); }

    public function invoices(): HasMany // Invoices generated during this session
    {
        return $this->hasMany(Invoice::class);
    }
}
