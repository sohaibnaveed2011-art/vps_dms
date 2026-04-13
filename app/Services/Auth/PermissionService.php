<?php

namespace App\Services\Auth;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    /**
     * List permissions.
     *
     * Permissions are SYSTEM-LEVEL and STATIC.
     */
    public function paginate(array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = Permission::query();

        if (! empty($filters['q'])) {
            $query->where('name', 'like', '%'.$filters['q'].'%');
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Find permission.
     */
    public function find(int $id): ?Permission
    {
        return Permission::find($id);
    }

    /**
     * Create permission.
     *
     * ⚠ SYSTEM ADMIN ONLY
     * ⚠ Normally seeded, not UI-created
     */
    public function create(array $data): Permission
    {
        return Permission::create([
            'name'       => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'web',
        ]);
    }

    /**
     * Update permission.
     *
     * ⚠ Strongly discouraged in production
     */
    public function update(Permission $permission, array $data): Permission
    {
        $permission->update([
            'name' => $data['name'],
        ]);

        return $permission->refresh();
    }

    /**
     * Delete permission.
     *
     * ⚠ Usually disabled in production
     */
    public function delete(Permission $permission): void
    {
        $permission->delete();
    }
}
