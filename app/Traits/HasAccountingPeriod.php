<?php

namespace App\Traits;

use App\Models\Core\FinancialYear;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasAccountingPeriod
{
    /* ======================
     |  Relationship
     ====================== */

    public function financialYear(): BelongsTo
    {
        return $this->belongsTo(FinancialYear::class, 'financial_year_id');
    }

    /* ======================
     |  Scopes
     ====================== */

    public function scopeInFinancialYear(Builder $query, int $financialYearId): Builder
    {
        return $query->where('financial_year_id', $financialYearId);
    }

    public function scopeInCurrentYear(Builder $query): Builder
    {
        $currentYear = FinancialYear::where('is_current', true)->first();
        return $currentYear ? $query->inFinancialYear($currentYear->id) : $query;
    }

    public function scopeInDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    public function scopeInFiscalPeriod(Builder $query, int $year, ?int $month = null): Builder
    {
        $financialYear = FinancialYear::where('start_date', '<=', "{$year}-12-31")
            ->where('end_date', '>=', "{$year}-01-01")
            ->first();

        if (!$financialYear) {
            return $query;
        }

        if ($month) {
            $startDate = date("{$year}-{$month}-01");
            $endDate = date("{$year}-{$month}-t");
            return $query->whereBetween('date', [$startDate, $endDate]);
        }

        return $query->inFinancialYear($financialYear->id);
    }

    /* ======================
     |  Accessors
     ====================== */

    public function getFiscalYearAttribute(): ?string
    {
        return $this->financialYear ? 
            $this->financialYear->start_date->format('Y') . '-' . $this->financialYear->end_date->format('Y') : 
            null;
    }
}