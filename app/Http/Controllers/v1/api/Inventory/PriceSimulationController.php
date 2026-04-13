<?php

namespace App\Http\Controllers\v1\api\Pricing;

use App\Services\Pricing\PriceSimulationService;
use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Pricing\PriceSimulationRequest;

class PriceSimulationController extends BaseApiController
{
    protected array $permissions = [
        'simulate' => 'pricing.simulate',
    ];

    public function __construct(
        protected PriceSimulationService $service
    ) {
        parent::__construct();
    }

    public function simulate(PriceSimulationRequest $request)
    {
        $this->authorizeAction($request);

        $result = $this->service->simulate(
            $this->context($request),
            $request->validated()
        );

        return $this->success($result);
    }
}
