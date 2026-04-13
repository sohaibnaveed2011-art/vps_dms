<?php

namespace App\Models\Account;

use App\Models\Voucher\Payment;
use App\Models\Voucher\PurchaseBill;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentAllocation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'payment_id',
        'purchase_bill_id',
        'amount_allocated',
        'allocation_date',
    ];

    protected $casts = [
        'amount_allocated' => 'decimal:4',
        'allocation_date' => 'date',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function purchaseBill(): BelongsTo
    {
        return $this->belongsTo(PurchaseBill::class);
    }
}
