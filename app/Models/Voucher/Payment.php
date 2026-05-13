<?php

namespace App\Models\Vouchers;

use App\Models\Accounts\Account;
use App\Models\Accounts\Journal;
use App\Models\Core\Branch;
use App\Models\Partner\Supplier;
use App\Models\Vouchers\BaseVoucher;
use App\Models\Vouchers\PaymentReference;

class Payment extends BaseVoucher
{
    protected $table = 'payments';

    protected $fillable = [
        'organization_id',
        'branch_id',
        'financial_year_id',
        'voucher_type_id',
        'customer_id',
        'document_number',
        'amount',
        'unallocated_amount',
        'date',
        'account_id',
        'reference_number',
        'status',
        'journal_id',
        'created_by',
        'reviewed_by',
        'approved_by',
        'updated_by',
        'reviewed_at',
        'approved_at',
    ];

    /* ======================
     |  Relationships
     ====================== */

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function journal()
    {
        return $this->belongsTo(Journal::class);
    }

    public function references()
    {
        return $this->hasMany(PaymentReference::class);
    }

    /* ======================
     |  Business Logic
     ====================== */

    public function updateUnallocated(): self
    {
        $allocated = $this->references()->sum('amount');
        $this->unallocated_amount = $this->amount - $allocated;
        $this->saveQuietly();

        return $this;
    }
}