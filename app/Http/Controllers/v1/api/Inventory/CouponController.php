<?php

namespace App\Http\Controllers\v1\api\Pricing;

use Illuminate\Http\Request;
use App\Guards\PolicyGuard;
use App\Services\Pricing\CouponService;
use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Pricing\StoreCouponRequest;
use App\Http\Requests\Pricing\UpdateCouponRequest;
use App\Http\Requests\Pricing\ApplyCouponRequest;
use App\Http\Resources\Pricing\CouponResource;

class CouponController extends BaseApiController
{
    protected array $permissions = [
        'index'   => 'pricing.coupon.view',
        'store'   => 'pricing.coupon.create',
        'show'    => 'pricing.coupon.show',
        'update'  => 'pricing.coupon.update',
        'destroy' => 'pricing.coupon.delete',
        'apply'   => 'pricing.coupon.apply',
    ];

    public function __construct(
        protected CouponService $service,
        protected PolicyGuard $policyGuard
    ) {
        parent::__construct(app(\App\Services\Auth\AuthorizationService::class));
    }

    public function index(Request $request)
    {
        $this->authorizeAction($request);

        $coupons = $this->service->paginate(
            $this->context($request)->organization_id,
            $this->perPage($request)
        );

        return $this->success(
            CouponResource::collection($coupons)
        );
    }

    public function store(StoreCouponRequest $request)
    {
        $this->authorizeAction($request);

        $coupon = $this->service->create(
            $this->context($request)->organization_id,
            $request->validated()
        );

        return $this->created(new CouponResource($coupon));
    }

    public function apply(ApplyCouponRequest $request)
    {
        $this->authorizeAction($request);

        $result = $this->service->apply(
            $this->context($request)->organization_id,
            $request->validated()
        );

        return $this->success($result);
    }
}
