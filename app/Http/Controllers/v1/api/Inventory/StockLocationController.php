<?php

namespace App\Http\Controllers\v1\api\Inventory;

use App\Exceptions\ForbiddenException;
use App\Exceptions\NotFoundException;
use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Inventory\StoreStockLocationRequest;
use App\Http\Requests\Inventory\UpdateStockLocationRequest;
use App\Http\Resources\Inventory\StockLocationResource;
use App\Services\Inventory\StockLocationService;
use Illuminate\Http\Request;

class StockLocationController extends BaseApiController
{
    protected array $permissions = [
        'index'       => 'inventory.stockLocation.view',
        'store'       => 'inventory.stockLocation.create',
        'show'        => 'inventory.stockLocation.show',
        'update'      => 'inventory.stockLocation.update',
        'destroy'     => 'inventory.stockLocation.delete',
        'restore'     => 'inventory.stockLocation.restore',
        'forceDelete' => 'inventory.stockLocation.forceDelete',
    ];

    public function __construct(
        protected StockLocationService $service,
    ) {
        parent::__construct();
    }

    protected function context(Request $request)
    {
        return $request->user()->activeContext()
            ?? throw new ForbiddenException('No active context.');
    }

    public function index(Request $request)
    {
        $this->authorizeAction($request);

        $context = $this->context($request);

        $locations = $this->service->paginate(
            $context->organization_id,
            $request->only(['search', 'is_active']),
            $this->perPage($request)
        );

        return $this->success(
            StockLocationResource::collection($locations->items()),
            [
                'total' => $locations->total(),
                'per_page' => $locations->perPage(),
                'current_page' => $locations->currentPage(),
            ]
        );
    }

    public function store(StoreStockLocationRequest $request)
    {
        $this->authorizeAction($request);

        $context = $this->context($request);

        $this->policyGuard->requireFeature(
            $context->organization_id,
            'inventory'
        );

        $location = $this->service->create(
            $request->validated()
        );

        return $this->created(new StockLocationResource($location));
    }

    public function show(Request $request, int $id)
    {
        $context = $this->context($request);

        $location = $this->service->find(
            $context->organization_id,
            $id
        ) ?? throw new NotFoundException('Stock Location not found.');

        $this->authorizeAction($request, $location);

        return $this->success(new StockLocationResource($location));
    }

    public function update(UpdateStockLocationRequest $request, int $id)
    {
        $context = $this->context($request);

        $location = $this->service->find(
            $context->organization_id,
            $id
        ) ?? throw new NotFoundException('Stock Location not found.');

        $this->authorizeAction($request, $location);

        $updated = $this->service->update($location, $request->validated());

        return $this->success(new StockLocationResource($updated));
    }

    public function destroy(Request $request, int $id)
    {
        $context = $this->context($request);

        $location = $this->service->find(
            $context->organization_id,
            $id
        ) ?? throw new NotFoundException('Stock Location not found.');

        $this->authorizeAction($request, $location);

        $this->service->delete($location);

        return $this->deleted('Stock Location deleted successfully.');
    }

    public function restore(Request $request, int $id)
    {
        $context = $this->context($request);

        $location = $this->service->findWithTrashed(
            $context->organization_id,
            $id
        ) ?? throw new NotFoundException('Stock Location not found.');

        $this->authorizeAction($request, $location);

        $this->service->restore($location);

        return $this->success(['message' => 'Stock Location restored successfully.']);
    }

    public function forceDelete(Request $request, int $id)
    {
        $context = $this->context($request);

        $location = $this->service->findWithTrashed(
            $context->organization_id,
            $id
        ) ?? throw new NotFoundException('Stock Location not found.');

        $this->authorizeAction($request, $location);

        $this->service->forceDelete($location);

        return $this->deleted('Stock Location permanently deleted.');
    }
}
