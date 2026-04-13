<?php

namespace App\Http\Controllers\v1\api\Auth;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Auth\StoreRoleRequest;
use App\Http\Requests\Auth\SyncRolePermissionsRequest;
use App\Http\Requests\Auth\UpdateRoleRequest;
use App\Http\Resources\Auth\RoleResource;
use App\Services\Auth\RoleService;
use Illuminate\Http\Request;
use App\Exceptions\NotFoundException;
use App\Exceptions\ForbiddenException;

class RoleController extends BaseApiController
{
    /**
     * 🔐 SYSTEM ADMIN ONLY
     */
    protected array $permissions = [
        'index'           => 'rbac.role.view',
        'store'           => 'rbac.role.create',
        'show'            => 'rbac.role.show',
        'update'          => 'rbac.role.update',
        'destroy'         => 'rbac.role.delete',
        'syncPermissions' => 'rbac.role.update',
    ];

    public function __construct(protected RoleService $service)
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        // 1. Check if user has 'rbac.role.view' (Admin or Business User)
        $this->authorizeAction($request);

        // 2. We skip restrictToContext because you want "All Roles"
        // to be visible regardless of organization.

        $roles = $this->service->paginate(
            $request->only(['q']), // Pass relevant filters
            $this->perPage($request)
        );

        return $this->success(
            RoleResource::collection($roles),
            $this->paginationMetadata($roles)
        );
    }

    public function store(StoreRoleRequest $request)
    {
        $this->authorizeAction($request);

        $this->service->create($request->validated());

        return $this->created(['message' => 'Role created successfully.']);
    }

    public function show(Request $request, int $id)
    {
        $this->authorizeAction($request);
        $this->ensureAdmin($request);

        $role = $this->service->find($id) ?? throw new NotFoundException('Role not found.');

        return $this->success(new RoleResource($role->load('permissions')));
    }

    public function update(UpdateRoleRequest $request, int $id)
    {
        $this->authorizeAction($request);

        $role = $this->service->find($id) ?? throw new NotFoundException('Role not found.');

        $this->service->update($role, $request->validated());

        return $this->updated('Role updated successfully.');
    }

public function destroy(Request $request, int $id)
{
    $this->authorizeAction($request);
    $this->ensureAdmin($request);

    // 1. Find it
    $role = $this->service->find($id)
        ?? throw new NotFoundException('Role not found.');

    // 2. Try to delete it
    $this->service->delete($role);

    // 3. Respond
    return $this->deleted('Role deleted successfully.');
}

    public function syncPermissions(SyncRolePermissionsRequest $request, int $id)
    {
        $this->authorizeAction($request);

        $role = $this->service->find($id) ?? throw new NotFoundException('Role not found.');

        // Guard against modifying the core system admin role
        if ($role->name === 'admin') {
            throw new ForbiddenException('Admin role permissions are system-controlled.');
        }

        $this->service->syncPermissions($role, $request->validated()['permissions']);

        return $this->success(null, ['message' => 'Permissions synced successfully.']);
    }
}
