<?php

namespace App\Models\Vouchers;

use App\Models\Core\Organization;
use App\Models\Core\FinancialYear;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class DocumentNumberSequence extends Model
{
    protected $table = 'document_number_sequences';

    protected $fillable = [
        'organization_id',
        'voucher_type_id',
        'financial_year_id',
        'current_number',
        'prefix',
        'suffix',
        'padding_length',
    ];

    protected $casts = [
        'current_number' => 'integer',
        'padding_length' => 'integer',
    ];

    /* ======================
     |  Relationships
     ====================== */

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function voucherType()
    {
        return $this->belongsTo(VoucherType::class);
    }

    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class);
    }

    /* ======================
     |  Helper Methods
     ====================== */

    public function getNextNumber(): string
    {
        return DB::transaction(function () {
            $current = $this->current_number;
            $this->increment('current_number');

            $prefix = $this->prefix ?? '';
            $suffix = $this->suffix ?? '';
            $padded = str_pad($current, $this->padding_length, '0', STR_PAD_LEFT);

            return $prefix . $padded . $suffix;
        });
    }

    public static function getNextNumberFor(int $organizationId, int $voucherTypeId, int $financialYearId): string
    {
        $sequence = self::where('organization_id', $organizationId)
            ->where('voucher_type_id', $voucherTypeId)
            ->where('financial_year_id', $financialYearId)
            ->first();

        if (!$sequence) {
            throw new \Exception("No document number sequence configured for voucher type: {$voucherTypeId}");
        }

        return $sequence->getNextNumber();
    }

    public function resetSequence(int $startNumber = 1): self
    {
        $this->current_number = $startNumber;
        $this->save();

        return $this;
    }
}