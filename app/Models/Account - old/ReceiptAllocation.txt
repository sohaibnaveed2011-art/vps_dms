<?php

namespace App\Models\Account;

use App\Models\Voucher\Invoice;
use App\Models\Voucher\Receipt;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReceiptAllocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'receipt_id',
        'invoice_id',
        'amount_allocated',
        'allocation_date',
    ];

    protected $casts = [
        'amount_allocated' => 'decimal:4',
        'allocation_date' => 'date',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
