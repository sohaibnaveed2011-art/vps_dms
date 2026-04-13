<?php

namespace App\Http\Controllers\v1\api\Inventory;

use App\Http\Requests\Pricing\StorePriceListItemRequest;
use App\Http\Requests\Pricing\UpdatePriceListItemRequest;
use App\Services\Inventory\Pricing\PriceListItemService;
use App\Http\Resources\Inventory\PriceListItemResource;
use App\Http\Controllers\v1\api\BaseApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PriceListItemController extends BaseApiController
{
    protected array $permissions = [
        'index'       => 'inventory.priceListItem.view',
        'store'       => 'inventory.priceListItem.create',
        'show'        => 'inventory.priceListItem.show',
        'update'      => 'inventory.priceListItem.update',
        'destroy'     => 'inventory.priceListItem.delete',
        'restore'     => 'inventory.priceListItem.restore',
        'forceDelete' => 'inventory.priceListItem.forceDelete',
    ];

    public function __construct(protected PriceListItemService $service) 
    {
        parent::__construct();
    }

    public function index(Request $request, int $priceListId): JsonResponse
    {
        $this->authorizeAction($request);

        $filters = $request->only(['search', 'is_active']);
        $filters['price_list_id'] = $priceListId;

        $this->restrictToContext($request, $filters);

        $items = $this->service->paginate($filters, $this->perPage($request));

        return $this->success(
            PriceListItemResource::collection($items),
            $this->paginationMetadata($items)
        );
    }

    public function store(StorePriceListItemRequest $request)
    {
        $this->authorizeAction($request);

        $this->enforcePolicy($request, feature: 'inventory');
        // Automatically injects organization_id from context
        $priceListItems = $this->service->create($this->getValidatedData($request));

        return $this->created('Price list items created successfully...!');
    }

    public function show(Request $request, int $id)
    {
        $priceListItem = $this->service->find($id, $this->getActiveOrgId($request));

        $this->authorizeAction($request, $priceListItem);

        return $this->success(new PriceListItemResource($priceListItem));
    }

    public function update(UpdatePriceListItemRequest $request, int $id)
    {
        $orgId = $this->getActiveOrgId($request);
        $priceListItem = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $priceListItem);
        $this->service->update($priceListItem, $request->validated());

        return $this->updated('Price list item updated successfully.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $priceListItem = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $priceListItem);
        $this->service->delete($priceListItem);

        return $this->deleted('Price List item deleted successfully.');
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $priceListItem = $this->service->find($id, $orgId, withTrashed: true);
        $this->authorizeAction($request, $priceListItem);

        $this->service->restore($priceListItem);

        return $this->success(['message' => 'Price List item restored successfully.']);
    }

    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $priceListItem = $this->service->find($id, $orgId, withTrashed: true);
        $this->authorizeAction($request, $priceListItem);

        $this->service->forceDelete($priceListItem);

        return $this->deleted('Price List item permanently deleted.');
    }
}