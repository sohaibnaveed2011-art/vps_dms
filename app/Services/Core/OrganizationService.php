<?php

namespace App\Services\Core;

use App\Exceptions\NotFoundException;
use App\Models\Core\Organization;
use App\Services\Policy\BasePolicyService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrganizationService extends BasePolicyService
{
    public function paginate(array $filters = [], int $perPage): LengthAwarePaginator
    {
        return Organization::query()
            // 1. Strict ID Filter (for Tenant scoping)
            ->when(isset($filters['id']), function ($query) use ($filters) {
                $query->where('id', $filters['id']);
            })

            // 2. Global Search (Multiple columns)
            ->when(!empty($filters['search']), function ($query) use ($filters) {
                $search = trim($filters['search']);
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('ntn', 'like', "%{$search}%")
                    ->orWhere('strn', 'like', "%{$search}%")
                    ->orWhere('legal_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
                });
            })

            // 3. Status Filter
            ->when(isset($filters['is_active']), function ($query) use ($filters) {
                $query->where('is_active', $filters['is_active']);
            })

            ->latest()
            ->paginate($perPage);
    }

    public function find(int $id): Organization
    {
        $org = Organization::find($id);

        if (! $org) {
            throw new NotFoundException('Organization not found.');
        }

        return $org;
    }

    public function findWithTrashed(int $id): Organization
    {
        $org = Organization::withTrashed()->find($id);

        if (! $org) {
            throw new NotFoundException('Organization not found.');
        }

        return $org;
    }

    public function create(array $data): Organization
    {
        return Organization::create($data);
    }

    public function update(Organization $org, array $data): Organization
    {
        $org->update($data);

        return $org->fresh();
    }

    public function delete(Organization $org): void
    {
        $org->delete();
    }

    public function restore(int $id): Organization
    {
        $org = $this->findWithTrashed($id);

        if (! $org->trashed()) {
            throw new NotFoundException('Organization is not deleted.');
        }

        $org->restore();

        return $org->fresh();
    }

    public function forceDelete(int $id): void
    {
        $this->findWithTrashed($id)->forceDelete();
    }
}
