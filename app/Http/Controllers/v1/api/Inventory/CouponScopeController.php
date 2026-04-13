<?php

namespace App\Http\Controllers\v1\api\Pricing;

use Illuminate\Http\Request;
use App\Services\Pricing\CouponScopeService;
use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Pricing\StoreCouponScopeRequest;
use App\Http\Resources\Pricing\CouponScopeResource;

class CouponScopeController extends BaseApiController
{
    protected array $permissions = [
        'store'   => 'pricing.coupon.scope.manage',
        'destroy' => 'pricing.coupon.scope.manage',
    ];

    public function __construct(
        protected CouponScopeService $service
    ) {
        parent::__construct(app(\App\Services\Auth\AuthorizationService::class));
    }

    public function store(StoreCouponScopeRequest $request)
    {
        $this->authorizeAction($request);

        $scope = $this->service->create(
            $this->context($request)->organization_id,
            $request->validated()
        );

        return $this->created(new CouponScopeResource($scope));
    }

    public function destroy(Request $request, int $id)
    {
        $this->authorizeAction($request);

        $this->service->delete($id);

        return $this->deleted('Coupon scope removed.');
    }
}
