<?php

namespace App\Http\Controllers\v1\api\Inventory;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Pricing\StorePriceListRequest;
use App\Http\Requests\Pricing\UpdatePriceListRequest;
use App\Http\Resources\Inventory\PriceListResource;
use App\Services\Inventory\Pricing\PriceListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriceListController extends BaseApiController
{
    protected array $permissions = [
        'index'       => 'inventory.priceList.view',
        'store'       => 'inventory.priceList.create',
        'show'        => 'inventory.priceList.show',
        'update'      => 'inventory.priceList.update',
        'destroy'     => 'inventory.priceList.delete',
        'restore'     => 'inventory.priceList.restore',
        'forceDelete' => 'inventory.priceList.forceDelete',
    ];

    public function __construct(protected PriceListService $service
    ) {
        parent::__construct();
    }

    public function index(Request $request):JsonResponse
    {
        $this->authorizeAction($request);
        $filters = $request->only(['search', 'is_active']);

        // Mandatory Context Restriction: Forces organization_id into filters
        $this->restrictToContext($request, $filters);

        $priceLists = $this->service->paginate($filters, $this->perPage($request));

        return $this->success(
            PriceListResource::collection($priceLists),
            $this->paginationMetadata($priceLists)
        );
    }

    public function store(StorePriceListRequest $request): JsonResponse
    {
        $this->authorizeAction($request);
        // Enforce feature policy for the active context
        $this->enforcePolicy($request, feature: 'inventory');
        // Automatically injects organization_id from context
        $priceList = $this->service->create($this->getValidatedData($request));

        return $this->created('Price list created successfully...!');
    }

    public function show(Request $request, int $id)
    {
        $priceList = $this->service->find($id, $this->getActiveOrgId($request));

        $this->authorizeAction($request, $priceList);

        return $this->success(new PriceListResource($priceList));
    }

    public function update(UpdatePriceListRequest $request, int $id)
    {
        $orgId = $this->getActiveOrgId($request);
        $priceList = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $priceList);
        $this->service->update($priceList, $request->validated());

        return $this->updated('Price list updated successfully.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $priceList = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $priceList);
        $this->service->delete($priceList);

        return $this->deleted('Price List deleted successfully.');
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $priceList = $this->service->find($id, $orgId, withTrashed: true);
        $this->authorizeAction($request, $priceList);

        $this->service->restore($priceList);

        return $this->success(['message' => 'Price List restored successfully.']);
    }

    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $priceList = $this->service->find($id, $orgId, withTrashed: true);
        $this->authorizeAction($request, $priceList);

        $this->service->forceDelete($priceList);

        return $this->deleted('Price List permanently deleted.');
    }
}
