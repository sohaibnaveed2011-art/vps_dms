<?php

namespace App\Http\Controllers\v1\api\Auth;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Auth\StorePermissionRequest;
use App\Http\Requests\Auth\UpdatePermissionRequest;
use App\Http\Resources\Auth\PermissionResource;
use App\Services\Auth\PermissionService;
use Illuminate\Http\Request;
use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;

class PermissionController extends BaseApiController
{
    protected array $permissions = [
        'index'   => 'rbac.permission.view',
        'store'   => 'rbac.permission.create',
        'update'  => 'rbac.permission.update',
        'destroy' => 'rbac.permission.delete',
    ];

    public function __construct(protected PermissionService $service)
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $this->authorizeAction($request);
        $this->ensureAdmin($request);

        $permissions = $this->service->paginate(
            $request->only('q'),
            $this->perPage($request)
        );

        return $this->success(
            PermissionResource::collection($permissions),
            $this->paginationMetadata($permissions) // Simplified metadata
        );
    }

    public function store(StorePermissionRequest $request)
    {
        $this->authorizeAction($request);
        $this->ensureAdmin($request);

        $this->service->create($request->validated());

        return $this->created(['message' => 'Permission created successfully.']);
    }

    public function update(UpdatePermissionRequest $request, int $id)
    {
        $this->authorizeAction($request);
        $this->ensureAdmin($request);

        // Assuming your service throws a NotFoundException if find fails,
        // or you can handle it here:
        $permission = $this->service->find($id) ?? throw new NotFoundException('Permission not found.');

        $this->service->update($permission, $request->validated());

        return $this->updated('Permission updated successfully.'); // Simplified success response
    }

    public function destroy(Request $request, int $id)
    {
        $this->authorizeAction($request);
        $this->ensureAdmin($request);

        // Good design decision: keeping system integrity by blocking deletes.
        throw new ForbiddenException('Permissions are system-managed and cannot be deleted.');
    }
}
