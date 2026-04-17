<?php

namespace App\Http\Controllers\v1\api\Inventory;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Inventory\BulkProductVariantDiscountRequest;
use App\Http\Requests\Inventory\UpdateProductVariantDiscountRequest;
use App\Services\Inventory\ProductVariantDiscountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductVariantDiscountController extends BaseApiController
{
    protected array $permissions = [
        'store'       => 'inventory.product.create',
        'show'        => 'inventory.product.show',
        'update'      => 'inventory.product.update',
        'destroy'     => 'inventory.product.destroy',
        'restore'     => 'inventory.product.restore',
        'forceDelete' => 'inventory.product.forceDelete',
        'bulk'        => 'inventory.product.create',
    ];

    public function __construct(protected ProductVariantDiscountService $service)
    {
        parent::__construct();
    }

    /**
     * Handles
     * - Single discount
     * - Uniform Bulk discount (same discount for multiple variants)
     * - Mixed Bulk discount (different discounts for multiple variants)
     */
    public function store(BulkProductVariantDiscountRequest $request): JsonResponse
    {
        dd($request->validated());
        $this->authorizeAction($request);
        $this->enforcePolicy($request, 'inventory');

        $product_variant_discount = $this->service->bulkUpsert([
            ...$this->getValidatedData($request),
            'items' => [
                ['product_variant_id' => $request->product_variant_id]
            ]
        ]);

        return $this->created('Discount created successfully.');
    }

    public function bulk(BulkProductVariantDiscountRequest $request): JsonResponse
    {
        $this->authorizeAction($request);
        $this->enforcePolicy($request, 'inventory');

        $product_variant_discounts = $this->service->bulkUpsert($this->getValidatedData($request));

        return $this->created('Bulk discount applied successfully.');
    }

    public function update(UpdateProductVariantDiscountRequest $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $product_variant_discount = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $product_variant_discount);
        $updated = $this->service->update($product_variant_discount, $request->validated());

        return $this->success('Discount updated successfully.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $product_variant_discount = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $product_variant_discount);
        $this->service->delete($product_variant_discount);

        return $this->deleted('Product variant discount deleted successfully.');
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $product_variant_discount = $this->service->find($id, $this->getActiveOrgId($request), true);

        $this->authorizeAction($request, $product_variant_discount);
        $this->service->restore($product_variant_discount);

        return $this->success(['message' => 'Product variant discount restored successfully.']);
    }

    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $product_variant_discount = $this->service->find($id, $this->getActiveOrgId($request), true);

        $this->authorizeAction($request, $product_variant_discount);
        $this->service->forceDelete($product_variant_discount);

        return $this->deleted('Discount permanently deleted.');
    }
}
