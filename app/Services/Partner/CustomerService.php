<?php

namespace App\Services\Partner;

use App\Exceptions\NotFoundException;
use App\Models\Partner\Customer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerService
{
    /**
     * Paginate with mandatory organization filtering and search grouping.
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Customer::query()
            ->when(isset($filters['organization_id']), fn($q) => $q->where('organization_id', $filters['organization_id']))
            ->when(isset($filters['partner_category_id']), fn($q) => $q->where('partner_category_id', $filters['partner_category_id']))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $term = "%{$filters['search']}%";
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('contact_no', 'like', $term);
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Unified finder with explicit organization scoping.
     */
    public function find(int $id, ?int $orgId = null, bool $withTrashed = false): Customer
    {
        $query = Customer::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        $customer = $query->find($id);

        if (!$customer) {
            throw new NotFoundException('Customer not found.');
        }

        return $customer;
    }

    public function create(array $data): Customer
    {
        return Customer::create($data);
    }

    public function update(Customer $customer, array $data): Customer
    {
        $customer->update($data);
        return $customer;
    }

    public function delete(Customer $customer): void
    {
        $customer->delete();
    }

    public function restore(int $id, ?int $orgId = null): void
    {
        $customer = $this->find($id, $orgId, withTrashed: true);

        if (!$customer->trashed()) {
            throw new NotFoundException('Customer is not deleted.');
        }

        $customer->restore();
    }

    public function forceDelete(int $id, ?int $orgId = null): void
    {
        $this->find($id, $orgId, withTrashed: true)->forceDelete();
    }
}
