<?php

namespace App\Models\Vouchers;

use Illuminate\Database\Eloquent\Model;

class ReceiptReference extends Model
{
    protected $table = 'receipt_references';

    protected $fillable = [
        'receipt_id',
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

    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }

    public function reference()
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    /* ======================
     |  Scopes
     ====================== */

    public function scopeForReceipt($query, int $receiptId)
    {
        return $query->where('receipt_id', $receiptId);
    }

    public function scopeForInvoice($query, int $invoiceId)
    {
        return $query->where('reference_type', Invoice::class)
            ->where('reference_id', $invoiceId);
    }

    /* ======================
     |  Helper Methods
     ====================== */

    public static function allocate(Receipt $receipt, Model $reference, float $amount): self
    {
        return self::create([
            'receipt_id' => $receipt->id,
            'reference_type' => get_class($reference),
            'reference_id' => $reference->id,
            'amount' => $amount,
        ]);
    }
}