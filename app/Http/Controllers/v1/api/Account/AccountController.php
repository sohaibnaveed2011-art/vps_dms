<?php

namespace App\Http\Controllers\v1\api\Account;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Account\StoreAccountRequest;
use App\Http\Requests\Account\UpdateAccountRequest;
use App\Http\Resources\Account\AccountResource;
use App\Services\Account\AccountService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class AccountController extends BaseApiController
{
    public function __construct(private AccountService $service)
    {
        parent::__construct(app(\App\Services\Auth\AuthorizationService::class));
    }

    public function index(Request $request)
    {
        $accounts = $this->service->list(
            $request->only(['organization_id', 'type', 'is_active'])
        );

        return AccountResource::collection($accounts);
    }

    public function store(StoreAccountRequest $request)
    {
        return $this->created(
            new AccountResource(
                $this->service->create($request->validated())
            )
        );
    }

    public function show(int $id)
    {
        $account = $this->service->find($id);
        if (! $account) {
            return $this->notFound('Account not found');
        }

        return $this->success(new AccountResource($account));
    }

    public function update(UpdateAccountRequest $request, int $id)
    {
        $account = $this->service->update($id, $request->validated());
        if (! $account) {
            return $this->notFound('Account not found');
        }

        return $this->success(new AccountResource($account));
    }

    public function destroy(int $id)
    {
        try {
            if (! $this->service->delete($id)) {
                return $this->notFound('Account not found');
            }

            return $this->noContent();
        } catch (QueryException) {
            return $this->conflict(
                'Cannot delete account. Transactions are linked to it.'
            );
        }
    }
}
