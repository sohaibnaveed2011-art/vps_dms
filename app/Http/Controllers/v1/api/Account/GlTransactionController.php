<?php

namespace App\Http\Controllers\v1\api\Account;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Account\ListGlTransactionsRequest;
use App\Http\Resources\Account\GlTransactionResource;
use App\Services\Account\GlTransactionService;

class GlTransactionController extends BaseApiController
{
    public function __construct(private GlTransactionService $service)
    {
        parent::__construct(app(\App\Services\Auth\AuthorizationService::class));
    }

    /**
     * General Ledger listing (Reporting)
     */
    public function index(ListGlTransactionsRequest $request)
    {
        return GlTransactionResource::collection(
            $this->service->list($request->validated())
        );
    }

    /**
     * Single GL entry (Auditing)
     */
    public function show(int $id)
    {
        $transaction = $this->service->find($id);
        if (! $transaction) {
            return $this->notFound('GL Transaction not found');
        }

        return $this->success(
            new GlTransactionResource($transaction)
        );
    }
}
