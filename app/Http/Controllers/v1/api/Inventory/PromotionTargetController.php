<?php

namespace App\Http\Controllers\v1\api\Pricing;

use Illuminate\Http\Request;
use App\Services\Pricing\PromotionTargetService;
use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Pricing\StorePromotionTargetRequest;
use App\Http\Resources\Pricing\PromotionTargetResource;

class PromotionTargetController extends BaseApiController
{
    protected array $permissions = [
        'store'   => 'pricing.promotion.target.manage',
        'destroy' => 'pricing.promotion.target.manage',
    ];

    public function __construct(
        protected PromotionTargetService $service
    ) {
        parent::__construct();
    }

    public function store(StorePromotionTargetRequest $request)
    {
        $this->authorizeAction($request);

        $target = $this->service->create(
            $this->context($request)->organization_id,
            $request->validated()
        );

        return $this->created(new PromotionTargetResource($target));
    }

    public function destroy(Request $request, int $id)
    {
        $this->authorizeAction($request);

        $this->service->delete($id);

        return $this->deleted('Promotion target removed.');
    }
}
