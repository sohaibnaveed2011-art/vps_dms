<?php

namespace App\Http\Controllers\v1\api\Pricing;

use Illuminate\Http\Request;
use App\Guards\PolicyGuard;
use App\Services\Pricing\PromotionScopeService;
use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Pricing\StorePromotionScopeRequest;
use App\Http\Resources\Pricing\PromotionScopeResource;

class PromotionScopeController extends BaseApiController
{
    protected array $permissions = [
        'store'   => 'pricing.promotion.scope.manage',
        'destroy' => 'pricing.promotion.scope.manage',
    ];

    public function __construct(
        protected PromotionScopeService $service,
        protected PolicyGuard $policyGuard
    ) {
        parent::__construct(app(\App\Services\Auth\AuthorizationService::class));
    }

    public function store(StorePromotionScopeRequest $request)
    {
        $this->authorizeAction($request);

        $scope = $this->service->create(
            $this->context($request)->organization_id,
            $request->validated()
        );

        return $this->created(new PromotionScopeResource($scope));
    }

    public function destroy(Request $request, int $id)
    {
        $this->authorizeAction($request);

        $this->service->delete($id);

        return $this->deleted('Promotion scope removed.');
    }
}
