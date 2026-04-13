<?php

namespace App\Http\Controllers\v1\api\Auth;

use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Resources\Auth\UserContextResource;
use App\Models\Auth\UserContext;
use App\Models\Auth\UserAssignment;
use Illuminate\Http\Request;

class UserContextController extends BaseApiController
{
    protected array $permissions = [
        'index' => 'user.context.view',
        'show'  => 'user.context.show',
        'available' => 'user.context,show',
    ];

    public function index(Request $request)
    {
        $this->authorizeAction($request);

        $contexts = UserContext::where('user_id', $request->user()->id)
            ->latest()
            ->paginate();

        return $this->success(
            UserContextResource::collection($contexts),
            [
                'pagination' => [
                    'total'        => $contexts->total(),
                    'per_page'     => $contexts->perPage(),
                    'current_page' => $contexts->currentPage(),
                ],
            ]
        );
    }

    public function show(Request $request, int $id)
    {
        $context = UserContext::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $this->authorizeAction($request, $context);

        return $this->success(new UserContextResource($context));
    }

    public function available(Request $request)
    {
        $user = $request->user();

        $assignments = UserAssignment::with('assignable')
            ->where('user_id', $user->id)
            ->whereNull('ended_at')
            ->get();

        $contexts = $assignments
            ->map(function ($a) {

                return [
                    'organization_id' => $a->assignable->organization_id ?? $a->assignable->id,
                    'branch_id' => $a->assignable->branch_id ?? null,
                    'warehouse_id' => $a->assignable->warehouse_id ?? null,
                    'outlet_id' => $a->assignable->outlet_id ?? null,
                    'label' => class_basename($a->assignable_type),
                    'scope_id' => $a->assignable_id,
                ];
            })
            ->unique(fn ($c) => json_encode([
                $c['organization_id'],
                $c['branch_id'],
                $c['warehouse_id'],
                $c['outlet_id'],
            ]))
            ->values();

        return $this->success($contexts);
    }
}
