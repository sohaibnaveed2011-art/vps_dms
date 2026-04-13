<?php

namespace App\Http\Controllers\v1\api\Partner;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Partner\StoreCustomerRequest;
use App\Http\Requests\Partner\UpdateCustomerRequest;
use App\Http\Resources\Partner\CustomerResource;
use App\Services\Partner\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends BaseApiController
{
    protected array $permissions = [
        'index'       => 'partner.customer.view',
        'store'       => 'partner.customer.create',
        'show'        => 'partner.customer.show',
        'update'      => 'partner.customer.update',
        'destroy'     => 'partner.customer.destroy',
        'forceDelete' => 'partner.customer.forceDelete',
        'restore'     => 'partner.customer.restore',
    ];

    public function __construct(protected CustomerService $service)
    {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $filters = $request->only(['organization_id','is_active', 'search', 'partner_category_id']);

        // Mandatory Context Restriction
        $this->restrictToContext($request, $filters);

        $customers = $this->service->paginate($filters, $this->perPage($request));

        return $this->success(
            CustomerResource::collection($customers),
            $this->paginationMetadata($customers)
        );
    }

    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $this->authorizeAction($request);
        $this->enforcePolicy($request, feature: 'partners');

        // getValidatedData automatically injects the active organization context
        $customer = $this->service->create($this->getValidatedData($request));

        return $this->created('Customer created successfully.');
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $customer = $this->service->find($id, $this->getActiveOrgId($request));
        $this->authorizeAction($request, $customer);

        return $this->success(new CustomerResource($customer));
    }

    public function update(UpdateCustomerRequest $request, int $id): JsonResponse
    {
        $customer = $this->service->find($id, $this->getActiveOrgId($request));
        $this->authorizeAction($request, $customer);
        $this->service->update($customer, $request->validated());

        return $this->updated('Customer updated successfully.');
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $customer = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $customer);
        $this->service->delete($customer);

        return $this->deleted('Customer deleted successfully.');
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);
        $customer = $this->service->find($id, $orgId, withTrashed: true);
        $this->authorizeAction($request, $customer);
        $this->service->restore($id, $orgId);

        return $this->success(['message' => 'Customer restored successfully.']);
    }

    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $orgId = $this->getActiveOrgId($request);

        $customer = $this->service->find($id, $orgId, withTrashed: true);
        $this->authorizeAction($request, $customer);

        $this->service->forceDelete($id, $orgId);

        return $this->deleted('Customer permanently deleted.');
    }
}
