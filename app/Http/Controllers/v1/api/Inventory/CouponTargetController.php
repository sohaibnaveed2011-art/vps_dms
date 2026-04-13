<?php

namespace App\Http\Controllers\v1\api\Pricing;

use Illuminate\Http\Request;
use App\Services\Pricing\CouponTargetService;
use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Pricing\StoreCouponTargetRequest;
use App\Http\Resources\Pricing\CouponTargetResource;

class CouponTargetController extends BaseApiController
{
    protected array $permissions = [
        'store'   => 'pricing.coupon.target.manage',
        'destroy' => 'pricing.coupon.target.manage',
    ];

    public function __construct(
        protected CouponTargetService $service
    ) {
        parent::__construct(app(\App\Services\Auth\AuthorizationService::class));
    }

    public function store(StoreCouponTargetRequest $request)
    {
        $this->authorizeAction($request);

        $target = $this->service->create(
            $this->context($request)->organization_id,
            $request->validated()
        );

        return $this->created(new CouponTargetResource($target));
    }

    public function destroy(Request $request, int $id)
    {
        $this->authorizeAction($request);

        $this->service->delete($id);

        return $this->deleted('Coupon target removed.');
    }
}
