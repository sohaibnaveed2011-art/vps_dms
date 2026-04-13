<?php

namespace App\Http\Controllers\v1\api\Pricing;

use Illuminate\Http\Request;
use App\Guards\PolicyGuard;
use App\Services\Pricing\PromotionService;
use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Pricing\StorePromotionRequest;
use App\Http\Requests\Pricing\UpdatePromotionRequest;
use App\Http\Resources\Pricing\PromotionResource;

class PromotionController extends BaseApiController
{
    protected array $permissions = [
        'index'   => 'pricing.promotion.view',
        'store'   => 'pricing.promotion.create',
        'show'    => 'pricing.promotion.show',
        'update'  => 'pricing.promotion.update',
        'destroy' => 'pricing.promotion.delete',
    ];

    public function __construct(
        protected PromotionService $service,
        protected PolicyGuard $policyGuard
    ) {
        parent::__construct(app(\App\Services\Auth\AuthorizationService::class));
    }

    public function index(Request $request)
    {
        $this->authorizeAction($request);

        $promotions = $this->service->paginate(
            $this->context($request)->organization_id,
            $request->only(['search']),
            $this->perPage($request)
        );

        return $this->success(
            PromotionResource::collection($promotions),
            [
                'total' => $promotions->total(),
                'per_page' => $promotions->perPage(),
                'current_page' => $promotions->currentPage(),
            ]
        );
    }

    public function store(StorePromotionRequest $request)
    {
        $this->authorizeAction($request);

        $promotion = $this->service->create(
            $this->context($request)->organization_id,
            $request->validated()
        );

        return $this->created(new PromotionResource($promotion));
    }

    public function show(Request $request, int $id)
    {
        $this->authorizeAction($request);

        $promotion = $this->service->find(
            $this->context($request)->organization_id,
            $id
        );

        return $this->success(new PromotionResource($promotion));
    }

    public function update(UpdatePromotionRequest $request, int $id)
    {
        $this->authorizeAction($request);

        $promotion = $this->service->find(
            $this->context($request)->organization_id,
            $id
        );

        $updated = $this->service->update($promotion, $request->validated());

        return $this->success(new PromotionResource($updated));
    }

    public function destroy(Request $request, int $id)
    {
        $this->authorizeAction($request);

        $promotion = $this->service->find(
            $this->context($request)->organization_id,
            $id
        );

        $this->service->delete($promotion);

        return $this->deleted('Promotion deleted successfully.');
    }
}
