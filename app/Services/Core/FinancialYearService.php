<?php

namespace App\Services\Core;

use App\Contracts\FinancialYearInterface;
use App\Exceptions\ConflictException;
use App\Exceptions\NotFoundException;
use App\Models\Core\FinancialYear;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class FinancialYearService implements FinancialYearInterface
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return FinancialYear::query()
            // Restriction applied here via organization_id filter
            ->when(isset($filters['organization_id']), fn($q) => $q->where('organization_id', $filters['organization_id']))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->latest()
            ->paginate($perPage);
    }

    /**
     * The $orgId parameter is critical here.
     * If NULL (System Admin), the query is unscoped.
     * If INT (Tenant), the query is strictly restricted to that org.
     */
    public function find(int $id, ?int $orgId = null, bool $withTrashed = false): FinancialYear
    {
        $query = FinancialYear::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        $fy = $query->find($id);

        if (!$fy) {
            throw new NotFoundException('Financial Year not found.');
        }

        return $fy;
    }

    public function create(array $data): FinancialYear
    {
        return FinancialYear::create($data);
    }

    public function update(FinancialYear $fy, array $data): FinancialYear
    {
        $fy->update($data);
        return $fy;
    }

    public function delete(int $id, ?int $orgId = null): void
    {
        $this->find($id, $orgId)->delete();
    }

    public function restore(int $id, ?int $orgId = null): FinancialYear
    {
        $fy = $this->find($id, $orgId, withTrashed: true);

        if (!$fy->trashed()) {
            throw new ConflictException('Financial Year is already active.');
        }

        $fy->restore();
        return $fy;
    }

    public function forceDelete(int $id, ?int $orgId = null): void
    {
        $this->find($id, $orgId, withTrashed: true)->forceDelete();
    }

    /**
     * Get the currently authenticated user
     */
    private function getAuthenticatedUser(): ?User
    {
        /** @var User|null $user */
        $user = Auth::user();
        return $user;
    }

    /**
     * Get organization ID from authenticated user's context
     */
    private function getOrganizationIdFromContext(): ?int
    {
        $user = $this->getAuthenticatedUser();
        
        if (!$user) {
            return null;
        }
        
        return $user->organizationId();
    }

    /**
     * Get active financial year for current organization context
     */
    public function getActiveYear(): ?FinancialYear
    {
        $orgId = $this->getOrganizationIdFromContext();
        
        if (!$orgId) {
            return null;
        }
        
        return FinancialYear::where('organization_id', $orgId)
            ->where('is_active', true)
            ->where('is_closed', false)
            ->first();
    }
    
    /**
     * Get financial year that contains the given date
     * 
     * @param string|Carbon $date
     */
    public function getYearForDate($date): ?FinancialYear
    {
        $orgId = $this->getOrganizationIdFromContext();
        
        if (!$orgId) {
            return null;
        }
        
        $date = $date instanceof Carbon ? $date : Carbon::parse($date);
        
        return FinancialYear::where('organization_id', $orgId)
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
    }
    
    /**
     * Check if journal can be posted to this financial year
     */
    public function canPostJournal(FinancialYear $year): bool
    {
        return $year->isOpen() && !$year->is_closed;
    }
    
    /**
     * Get current organization's financial year from active context
     * Returns the active financial year for the user's current organization context
     */
    public function getCurrentOrganizationYear(): ?FinancialYear
    {
        $orgId = $this->getOrganizationIdFromContext();
        
        if (!$orgId) {
            return null;
        }
        
        return FinancialYear::where('organization_id', $orgId)
            ->where('is_active', true)
            ->first();
    }
}