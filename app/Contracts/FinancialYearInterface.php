<?php

namespace App\Contracts;

use App\Models\Core\FinancialYear;
use Carbon\Carbon;

interface FinancialYearInterface
{
    /**
     * Get active financial year for current organization context
     */
    public function getActiveYear(): ?FinancialYear;
    
    /**
     * Get financial year that contains the given date
     * 
     * @param string|Carbon $date
     */
    public function getYearForDate($date): ?FinancialYear;
    
    /**
     * Check if journal can be posted to this financial year
     */
    public function canPostJournal(FinancialYear $year): bool;
    
    /**
     * Get financial year by organization ID (from context)
     */
    public function getCurrentOrganizationYear(): ?FinancialYear;
}