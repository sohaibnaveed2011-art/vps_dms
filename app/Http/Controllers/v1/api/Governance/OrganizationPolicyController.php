<?php

namespace App\Http\Controllers\v1\api\Governance;

use App\Exceptions\NotFoundException;
use App\Http\Controllers\v1\api\BaseApiController;
use App\Models\Core\Organization;
use App\Models\Governance\OrganizationPolicy;
use App\Services\Policy\OrganizationPolicyService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrganizationPolicyController extends BaseApiController
{
    protected array $permissions = [
        'index'     => null,
        'store'     => null,
        'update'    => null,
        'destroy'   => null,
        'lock'      => null,
        'unlock'    => null,
        'lockAll'   => null,
        'unlockAll' => null,
    ];

    public function __construct(
        protected OrganizationPolicyService $service
    ) {
        // parent::__construct(app(\App\Services\Auth\AuthorizationService::class));
    }

    /* =========================================================
     | LIST (UI READY)
     ========================================================= */

    public function index(Request $request, int $org)
    {
        $this->ensureAdmin($request);

        $this->assertOrganizationExists($org);

        $policies = $this->service->list(
            orgId: $org,
            category: $request->query('category'),
            perPage: $this->perPage($request)
        );

        return $this->success($policies);
    }

    /* =========================================================
     | CREATE
     ========================================================= */

    public function store(Request $request, int $org)
    {
        $this->ensureAdmin($request);
        $this->assertOrganizationExists($org);

        $data = $request->validate([
            'key' => [
                'required',
                'string',
                Rule::unique('organization_policies')
                    ->where('organization_id', $org),
            ],
            'category'    => ['required', 'string'],
            'value'       => ['required', 'array'],
            'description' => ['required', 'string'],
        ]);

        $policy = $this->service->create(
            $org,
            $data['key'],
            $data['value'] ?? [],
            $data['category'] ?? null,
            $data['description'] ?? null
        );

        return $this->created(['message' => 'Organization Policy created successfully.']);
    }

    /* =========================================================
     | UPDATE
     ========================================================= */

    public function update(
        Request $request,
        int $org,
        OrganizationPolicy $policy
    ) {
        $this->ensureAdmin($request);
        $this->assertSameOrganization($org, $policy);

        $data = $request->validate([
            'value'       => ['required', 'array'],
            'description' => ['nullable', 'string'],
        ]);

        $this->service->update(
            $org,
            $policy->key,
            $data['value']
        );

        if (isset($data['description'])) {
            $policy->update(['description' => $data['description']]);
        }

        return $this->success(['message' => 'Organization Policy updated successfully.']);
    }

    /* =========================================================
     | DELETE
     ========================================================= */

    public function destroy(
        Request $request,
        int $org,
        OrganizationPolicy $policy
    ) {
        $this->ensureAdmin($request);
        $this->assertSameOrganization($org, $policy);

        $this->service->delete($policy);

        return $this->deleted('Organization Policy deleted successfully.');
    }

    /* =========================================================
     | LOCK / UNLOCK ROW
     ========================================================= */

    public function lock(
        Request $request,
        int $org,
        OrganizationPolicy $policy
    ) {
        $this->ensureAdmin($request);
        $this->assertSameOrganization($org, $policy);

        $this->service->lock($policy);

        return $this->success([
            'message' => "Policy [{$policy->key}] locked",
        ]);
    }

    public function unlock(
        Request $request,
        int $org,
        OrganizationPolicy $policy
    ) {
        $this->ensureAdmin($request);
        $this->assertSameOrganization($org, $policy);

        $this->service->unlock($policy);

        return $this->success([
            'message' => "Policy [{$policy->key}] unlocked",
        ]);
    }

    /* =========================================================
     | INTERNAL GUARDS
     ========================================================= */

    protected function assertOrganizationExists(int $org): void
    {
        if (! Organization::where('id', $org)->exists()) {
            throw new NotFoundException('Organization not found.');
        }
    }

    protected function assertSameOrganization(
        int $org,
        OrganizationPolicy $policy
    ): void {
        if ($policy->organization_id !== $org) {
            throw new NotFoundException(
                'Policy not found for organization.'
            );
        }
    }
}
