<?php

namespace App\Http\Controllers\v1\api\Pricing;

use Illuminate\Http\Request;
use App\Services\Pricing\CartCalculationService;
use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Pricing\CartCalculationRequest;

class CartCalculationController extends BaseApiController
{
    protected array $permissions = [
        'calculate' => 'pricing.cart.calculate',
    ];

    public function __construct(
        protected CartCalculationService $service
    ) {
        parent::__construct(app(\App\Services\Auth\AuthorizationService::class));
    }

    public function calculate(CartCalculationRequest $request)
    {
        $this->authorizeAction($request);

        $result = $this->service->calculate(
            $this->context($request),
            $request->validated()
        );

        return $this->success($result);
    }
}
