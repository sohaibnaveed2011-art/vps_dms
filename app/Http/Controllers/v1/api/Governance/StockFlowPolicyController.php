<?php

namespace App\Http\Controllers\v1\api\Governance;

use App\Exceptions\NotFoundException;
use App\Http\Controllers\v1\api\BaseApiController;
use App\Models\Governance\StockFlowPolicy;
use App\Services\Policy\StockFlowPolicyService;
use Illuminate\Http\Request;

class StockFlowPolicyController extends BaseApiController
{
    protected array $permissions = [
        'index'   => null,
        'store'   => null,
        'update'  => null,
        'destroy' => null,
        'lock'    => null,
        'unlock'  => null,
    ];

    public function __construct(
        protected StockFlowPolicyService $service
    ) {
        parent::__construct(app(\App\Services\Auth\AuthorizationService::class));
    }

    /* =========================================================
     | LIST (Pagination + Filtering)
     ========================================================= */

    public function index(Request $request, int $organization)
    {
        $this->ensureAdmin($request);

        return $this->success(
            $this->service->paginate(
                $organization,
                fromType: $request->query('from_type'),
                toType: $request->query('to_type'),
                perPage: $this->perPage($request)
            )
        );
    }

    /* =========================================================
     | CREATE
     ========================================================= */

    public function store(Request $request, int $organization)
    {
        $this->ensureAdmin($request);

        $data = $request->validate([
            'from_type'   => ['required','string','max:100'],
            'from_id'     => ['nullable','integer'],
            'to_type'     => ['required','string','max:100'],
            'to_id'       => ['nullable','integer'],
            'allowed'     => ['boolean'],
            'description' => ['nullable','string'],
        ]);

        $policy = $this->service->create($organization, $data);

        return $this->created($policy);
    }

    /* =========================================================
     | UPDATE
     ========================================================= */

    public function update(
        Request $request,
        int $organization,
        StockFlowPolicy $policy
    ) {
        $this->ensureAdmin($request);
        $this->assertSameOrganization($organization, $policy);

        $data = $request->validate([
            'allowed'     => ['boolean'],
            'description' => ['nullable','string'],
        ]);

        $this->service->update($policy, $data);

        return $this->success($policy->refresh());
    }

    /* =========================================================
     | DELETE
     ========================================================= */

    public function destroy(
        Request $request,
        int $organization,
        StockFlowPolicy $policy
    ) {
        $this->ensureAdmin($request);
        $this->assertSameOrganization($organization, $policy);

        $this->service->delete($policy);

        return $this->deleted('Stock flow policy deleted.');
    }

    /* =========================================================
     | LOCK / UNLOCK
     ========================================================= */

    public function lock(
        Request $request,
        int $organization,
        StockFlowPolicy $policy
    ) {
        $this->ensureAdmin($request);
        $this->assertSameOrganization($organization, $policy);

        $this->service->lock($policy);

        return $this->success(['message' => 'Policy locked']);
    }

    public function unlock(
        Request $request,
        int $organization,
        StockFlowPolicy $policy
    ) {
        $this->ensureAdmin($request);
        $this->assertSameOrganization($organization, $policy);

        $this->service->unlock($policy);

        return $this->success(['message' => 'Policy unlocked']);
    }

    protected function assertSameOrganization(
        int $organization,
        StockFlowPolicy $policy
    ): void {
        if ($policy->organization_id !== $organization) {
            throw new NotFoundException(
                'Policy not found for organization.'
            );
        }
    }
}
