<?php

namespace App\Http\Controllers\v1\api\Pricing;

use Illuminate\Http\Request;
use App\Services\Pricing\CustomerCouponService;
use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Pricing\AssignCustomerCouponRequest;
use App\Http\Resources\Pricing\CustomerCouponResource;

class CustomerCouponController extends BaseApiController
{
    protected array $permissions = [
        'store' => 'pricing.coupon.assign',
    ];

    public function __construct(
        protected CustomerCouponService $service
    ) {
        parent::__construct(app(\App\Services\Auth\AuthorizationService::class));
    }

    public function store(AssignCustomerCouponRequest $request)
    {
        $this->authorizeAction($request);

        $record = $this->service->assign(
            $this->context($request)->organization_id,
            $request->validated()
        );

        return $this->created(new CustomerCouponResource($record));
    }
}
