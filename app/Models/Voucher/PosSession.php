<?php

namespace App\Models\Voucher;

use App\Models\Core\Branch;
use App\Models\Core\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
