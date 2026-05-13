<?php

namespace App\Models\Vouchers;

use App\Models\Vouchers\Payment;
use App\Models\Vouchers\PurchaseBill;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\Builder;

class PaymentReference extends Model
{
    protected $table = 'payment_references';

    protected $fillable = [
        'payment_id',
        'reference_type',
        'reference_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:4',
    ];

    /* ======================
     |  Relationships
     ====================== */

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function reference()
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    /* ======================
     |  Scopes
     ====================== */

    public function scopeForPayment(Builder $query, int $paymentId)
    {
        return $query->where('payment_id', $paymentId);
    }

    public function scopeForPurchaseBill(Builder $query, int $purchaseBillId)
    {
        return $query->where('reference_type', PurchaseBill::class)
            ->where('reference_id', $purchaseBillId);
    }

    /* ======================
     |  Helper Methods
     ====================== */

    public static function allocate(Payment $payment, Model $reference, float $amount): self
    {
        return self::create([
            'payment_id' => $payment->id,
            'reference_type' => get_class($reference),
            'reference_id' => $reference->id,
            'amount' => $amount,
        ]);
    }
}