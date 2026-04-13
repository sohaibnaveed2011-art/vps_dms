<?php

namespace App\Http\Controllers\v1\api\Governance;

use App\Exceptions\NotFoundException;
use App\Http\Controllers\v1\api\BaseApiController;
use App\Models\Governance\AuthorityPolicy;
use App\Services\Policy\AuthorityPolicyService;
use Illuminate\Http\Request;

class AuthorityPolicyController extends BaseApiController
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
        protected AuthorityPolicyService $service
    ) {
        parent::__construct(app(\App\Services\Auth\AuthorizationService::class));
    }

    /* =========================================================
     | LIST (Pagination Ready)
     ========================================================= */

    public function index(Request $request, int $organization)
    {
        $this->ensureAdmin($request);

        return $this->success(
            $this->service->paginate(
                $organization,
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
            'role_id'        => ['required','integer','exists:roles,id'],
            'subject'        => ['required','string','max:50'],
            'action'         => ['nullable','string','max:50'],
            'voucher_type'   => ['nullable','string','max:50'],
            'hierarchy_type' => ['nullable','string','max:50'],
            'hierarchy_id'   => ['nullable','integer'],
            'effect'         => ['required','in:allow,deny'],
            'description'    => ['nullable','string'],
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
        AuthorityPolicy $policy
    ) {
        $this->ensureAdmin($request);
        $this->assertSameOrganization($organization, $policy);

        $data = $request->validate([
            'effect'      => ['in:allow,deny'],
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
        AuthorityPolicy $policy
    ) {
        $this->ensureAdmin($request);
        $this->assertSameOrganization($organization, $policy);

        $this->service->delete($policy);

        return $this->deleted('Authority policy deleted.');
    }

    /* =========================================================
     | LOCK / UNLOCK
     ========================================================= */

    public function lock(
        Request $request,
        int $organization,
        AuthorityPolicy $policy
    ) {
        $this->ensureAdmin($request);
        $this->assertSameOrganization($organization, $policy);

        $this->service->lock($policy);

        return $this->success(['message' => 'Policy locked']);
    }

    public function unlock(
        Request $request,
        int $organization,
        AuthorityPolicy $policy
    ) {
        $this->ensureAdmin($request);
        $this->assertSameOrganization($organization, $policy);

        $this->service->unlock($policy);

        return $this->success(['message' => 'Policy unlocked']);
    }

    /* ========================================================= */

    protected function assertSameOrganization(
        int $organization,
        AuthorityPolicy $policy
    ): void {
        if ($policy->organization_id !== $organization) {
            throw new NotFoundException(
                'Policy not found for organization.'
            );
        }
    }
}
