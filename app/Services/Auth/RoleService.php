<?php

namespace App\Services\Auth;

use App\Exceptions\ConflictException;
use App\Exceptions\ForbiddenException;
use App\Models\Auth\UserAssignment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
    /**
     * List roles (system-wide).
     */
    public function paginate(array $filters = [], int $perPage): LengthAwarePaginator
    {
        $query = Role::query();

        if (! empty($filters['q'])) {
            $query->where('name', 'like', '%'.$filters['q'].'%');
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Find role by ID.
     */
    public function find(int $id): ?Role
    {
        return Role::find($id);
    }

    /**
     * Create new role.
     *
     * NOTE:
     * - Roles are SYSTEM objects
     * - They are NOT assigned directly to users
     */
    public function create(array $data): Role
    {
        return Role::create([
            'name'       => $data['name'],
            'guard_name' => $data['guard_name'] ?? 'web',
        ]);
    }

    /**
     * Update role.
     */
    public function update(Role $role, array $data): Role
    {
        $role->update([
            'name' => $data['name'],
        ]);

        return $role->refresh();
    }

    /**
     * Delete a role.
     */
    public function delete(Role $role): void
    {
        // 1. Business Rule Guard
        if ($role->name === 'admin') {
            throw new ForbiddenException('The system admin role is protected and cannot be deleted.');
        }

        // 2. Integrity Guard (Optional but recommended)
        $hasAssignments = UserAssignment::where('role_id', $role->id)
            ->whereNull('ended_at')
            ->exists();

        if ($hasAssignments) {
            throw new ConflictException('Role is currently assigned to users.');
        }

        // 3. Action
        $role->delete();

        // No return needed. If we reach here, it worked.
    }

    /**
     * Sync permissions with role.
     *
     * SINGLE SOURCE OF TRUTH
     */
    public function syncPermissions(Role $role, array $permissionNames): Role
    {
        $permissions = Permission::whereIn('name', $permissionNames)->get();

        if ($permissions->count() !== count($permissionNames)) {
            abort(422, 'One or more permissions are invalid');
        }

        $role->syncPermissions($permissions);

        return $role->refresh();
    }
}
