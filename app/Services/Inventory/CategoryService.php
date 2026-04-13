<?php

namespace App\Services\Inventory;

use App\Exceptions\NotFoundException;
use App\Models\Inventory\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CategoryService
{
    /**
     * Paginate categories with context-aware filtering.
     */
    public function paginate(array $filters = [], int $perPage): LengthAwarePaginator
    {
        return Category::query()
            ->when(isset($filters['organization_id']), fn($q) => $q->where('organization_id', $filters['organization_id']))
            ->when(array_key_exists('parent_id', $filters), fn($q) => $q->where('parent_id', $filters['parent_id']))
            ->when(isset($filters['is_active']), fn($q) => $q->where('is_active', (bool) $filters['is_active']))
            ->when(!empty($filters['search']), function ($q) use ($filters) {
                $term = "%" . trim($filters['search']) . "%";
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', $term)
                        ->orWhere('slug', 'like', $term);
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Unified find method with organization and trash support.
     */
    public function find(int $id, ?int $orgId = null, bool $withTrashed = false): Category
    {
        $query = Category::query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        $category = $query->find($id);

        if (!$category) {
            throw new NotFoundException('Category not found.');
        }

        return $category;
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);
        return $category;
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }

    public function restore(Category $category): void
    {
        if (!$category->trashed()) {
            throw new NotFoundException('Category is not deleted.');
        }

        $category->restore();
    }

    public function forceDelete(Category $category): void
    {
        $category->forceDelete();
    }
}
