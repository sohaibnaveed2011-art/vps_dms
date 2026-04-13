<?php

namespace App\Http\Controllers\v1\api\Auth;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Auth\StoreUserRequest;
use App\Http\Requests\Auth\UpdateUserRequest;
use App\Http\Resources\Auth\UserResource;
use App\Services\Auth\UserService;
use Illuminate\Http\Request;

class UserController extends BaseApiController
{
    protected array $permissions = [
        'index'       => 'auth.user.view',
        'store'       => 'auth.user.create',
        'show'        => 'auth.user.show',
        'update'      => 'auth.user.update',
        'destroy'     => 'auth.user.delete',
        'restore'     => 'auth.user.restore',
        'forceDelete' => 'auth.user.forceDelete',
    ];

    public function __construct(protected UserService $service)
    {
        parent::__construct();
    }

    public function index(Request $request)
    {
        $this->authorizeAction($request);

        $filters = $request->only(['is_active', 'search']);

        // If non-admin, restrict to their organization's users
        $this->restrictToContext($request, $filters);

        $users = $this->service->paginate(
            $filters,
            $this->perPage($request)
        );

        return $this->success(
            UserResource::collection($users),
            $this->paginationMetadata($users)
        );
    }

    public function store(StoreUserRequest $request)
    {
        $this->authorizeAction($request);

        $this->service->create($request->validated());

        return $this->created(['message' => 'User created successfully']);
    }

    public function show(Request $request, int $id)
    {
        // Service find() already throws NotFoundException if null
        $user = $this->service->find($id);

        $this->authorizeAction($request, $user);

        return $this->success(new UserResource($user));
    }

    public function update(UpdateUserRequest $request, int $id)
    {
        $user = $this->service->find($id);

        $this->authorizeAction($request, $user);

        $this->service->update($user, $request->validated());

        return $this->updated('User updated successfully');
    }

    public function destroy(Request $request, int $id)
    {
        $user = $this->service->find($id);

        $this->authorizeAction($request, $user);

        $this->service->delete($user);

        return $this->deleted('User deleted successfully.');
    }

    public function restore(Request $request, int $id)
    {
        $user = $this->service->findWithTrashed($id);

        $this->authorizeAction($request, $user);

        $this->service->restore($user);

        return $this->success(['message' => 'User restored successfully.']);
    }

    public function forceDelete(Request $request, int $id)
    {
        $user = $this->service->findWithTrashed($id);

        $this->authorizeAction($request, $user);

        $this->service->forceDelete($user);

        return $this->deleted('User permanently deleted.');
    }
}
