<?php

namespace App\Http\Controllers\v1\api\Partner;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Partner\StoreSupplierRequest;
use App\Http\Requests\Partner\UpdateSupplierRequest;
use App\Http\Resources\Partner\SupplierResource;
use App\Services\Partner\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends BaseApiController
{
    protected array $permissions = [
        'index'       => 'partner.supplier.view',
        'store'       => 'partner.supplier.create',
        'show'        => 'partner.supplier.show',
        'update'      => 'partner.supplier.update',
        'destroy'     => 'partner.supplier.destroy',
        'forceDelete' => 'partner.supplier.forceDelete',
        'restore'     => 'partner.supplier.restore',
    ];

    public function __construct(protected SupplierService $service)
    {
        parent::__construct();
    }

    public function index(Request $request):JsonResponse
    {
        $this->authorizeAction($request);
        
        $filters = $request->only(['is_active', 'search', 'partner_category_id', 'organization_id']);
        
        // Mandatory Context Restriction
        $this->restrictToContext($request, $filters);
        
        $suppliers = $this->service->paginate($filters, $this->perPage($request));
        
        return $this->success(
            SupplierResource::collection($suppliers),
            $this->paginationMetadata($suppliers)
        );
    }
    public function store(StoreSupplierRequest $request)
    {
        $this->authorizeAction($request);
        $this->enforcePolicy($request, feature: 'partners');

        // getValidatedData automatically injects the active organization context
        $supplier = $this->service->create($this->getValidatedData($request));
        return $this->created('Supplier created successfully...');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $supplier = $this->service->find($id, $this->getActiveOrgId($request));

        $this->authorizeAction($request, $supplier);

        return $this->success(new SupplierResource($supplier->load('category')));
    }

    public function update(UpdateSupplierRequest $request, int $id): JsonResponse
    {
        $supplier = $this->service->find($id, $this->getActiveOrgId($request));

        $this->authorizeAction($request, $supplier);
        $this->service->update($supplier, $request->validated());

        return $this->updated('Supplier updated successfully.');
    }

    public function destroy(Request $request, int $id)
    {
        $orgId = $this->getActiveOrgId($request);
        $supplier = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $supplier);
        $this->service->delete($supplier);

        return $this->deleted('Supplier deleted successfully.');
    }

    public function restore(Request $request, int $id)
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);
        $supplier = $this->service->find($id, $orgId, withTrashed: true);
        $this->authorizeAction($request, $supplier);
        $this->service->restore($id, $orgId);

        return $this->success(['message' => 'Supplier restored successfully.']);
    }

    public function forceDelete(Request $request, int $id)
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $supplier = $this->service->find($id, $orgId, withTrashed: true);
        $this->authorizeAction($request, $supplier);

        $this->service->forceDelete($id, $orgId);

        return $this->deleted('Supplier permanently deleted.');
    }
}