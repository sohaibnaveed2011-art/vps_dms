<?php

// app/Traits/HasAccountingPeriod.php
namespace App\Traits;

use App\Models\Core\FinancialYear;
use Illuminate\Database\Eloquent\Builder;

trait HasAccountingPeriod
{
    /**
     * Scope a query to filter by financial year
     *
     * @param Builder $query
     * @param int $financialYearId
     * @return Builder
     */
    public function scopeInFinancialYear(Builder $query, int $financialYearId): Builder
    {
        return $query->where('financial_year_id', $financialYearId);
    }
    
    /**
     * Scope a query to filter by current financial year
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeInCurrentYear(Builder $query): Builder
    {
        $currentYear = FinancialYear::where('is_current', true)->first();
        return $currentYear ? $query->inFinancialYear($currentYear->id) : $query;
    }
    
    /**
     * Scope a query to filter by date range
     *
     * @param Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return Builder
     */
    public function scopeInDateRange(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
    
    /**
     * Scope a query to filter by fiscal period
     *
     * @param Builder $query
     * @param int $year
     * @param int|null $month
     * @return Builder
     */
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
}