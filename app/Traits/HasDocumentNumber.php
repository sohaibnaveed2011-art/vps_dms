<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use App\Models\Vouchers\DocumentNumberSequence;

trait HasDocumentNumbers
{
    protected static function bootHasDocumentNumbers()
    {
        static::creating(function ($model) {
            if (empty($model->document_number)) {
                $model->document_number = static::generateDocumentNumber($model);
            }
        });
    }

    public static function generateDocumentNumber($model): string
    {
        $sequence = DocumentNumberSequence::where('organization_id', $model->organization_id)
            ->where('voucher_type_id', $model->voucher_type_id)
            ->where('financial_year_id', $model->financial_year_id)
            ->first();

        if (!$sequence) {
            throw new \Exception("No document number sequence configured for voucher type: {$model->voucher_type_id}");
        }

        return DB::transaction(function () use ($sequence) {
            $current = $sequence->current_number;
            $sequence->increment('current_number');

            $prefix = $sequence->prefix ?? '';
            $suffix = $sequence->suffix ?? '';
            $padded = str_pad($current, $sequence->padding_length, '0', STR_PAD_LEFT);

            return $prefix . $padded . $suffix;
        });
    }
}