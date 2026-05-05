<?php

namespace App\Http\Controllers\v1\api\Account;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Services\Accounts\AccountService;
use App\Http\Resources\Accounts\AccountResource;
use App\Http\Controllers\v1\api\BaseApiController;
use App\Http\Requests\Accounts\StoreAccountRequest;
use App\Http\Requests\Accounts\UpdateAccountRequest;

class AccountController extends BaseApiController
{
    protected array $permissions = [
        'index'         => 'accounts.account.view',
        'store'         => 'accounts.account.create',
        'show'          => 'accounts.account.show',
        'update'        => 'accounts.account.update',
        'destroy'       => 'accounts.account.destroy',
        'restore'       => 'accounts.account.restore',
        'forceDelete'   => 'accounts.account.forceDelete',
        'tree'          => 'accounts.account.view',
        'selectList'    => 'accounts.account.view',
        'getTypes'      => 'accounts.account.view',
        'hierarchy'     => 'accounts.account.view',
        'getBalance'    => 'accounts.account.view',
        'summary'       => 'accounts.account.view',
        'toggleStatus'  => 'accounts.account.update',
        'bulkUpdate'    => 'accounts.account.update',
        'export'        => 'accounts.account.export',
        'import'        => 'accounts.account.import',
        'chart'         => 'accounts.account.view',
        'trialBalance'  => 'accounts.account.view',
    ];

    public function __construct(protected AccountService $service)
    {
        parent::__construct();
    }

    /**
     * Display a listing of accounts.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $filters = $request->only([
            'search',
            'type',
            'parent_id',
            'is_group',
            'is_active',
            'is_taxable',
            'currency_code',
            'with_descendants',
            'min_level',
            'max_level',
        ]);
        
        $this->restrictToContext($request, $filters);

        $items = $this->service->paginate($filters, $this->perPage($request));

        return $this->success(
            AccountResource::collection($items),
            $this->paginationMetadata($items)
        );
    }

    /**
     * Store a newly created account.
     */
    public function store(StoreAccountRequest $request): JsonResponse
    {
        $this->authorizeAction($request);
        $this->enforcePolicy($request, 'accounts');

        $account = $this->service->create($this->getValidatedData($request));

        return $this->created('Account created successfully');
    }

    /**
     * Display the specified account.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $account = $this->service->find($id, $this->getActiveOrgId($request));
        $this->authorizeAction($request, $account);

        return $this->success(new AccountResource($account));
    }

    /**
     * Update the specified account.
     */
    public function update(UpdateAccountRequest $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $account = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $account);
        $updated = $this->service->update($account, $request->validated());

        return $this->success('Account updated successfully');
    }

    /**
     * Remove the specified account.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $account = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $account);
        $this->service->delete($account);

        return $this->deleted('Account deleted successfully.');
    }

    /**
     * Restore a soft-deleted account.
     */
    public function restore(Request $request, int $id): JsonResponse
    {
        // $this->ensureAdmin($request);
        $account = $this->service->find($id, $this->getActiveOrgId($request), true);

        $this->authorizeAction($request, $account);
        $this->service->restore($account);

        return $this->success('Account restored successfully.');
    }

    /**
     * Permanently delete an account.
     */
    public function forceDelete(Request $request, int $id): JsonResponse
    {
        $this->ensureAdmin($request);
        $account = $this->service->find($id, $this->getActiveOrgId($request), true);

        $this->authorizeAction($request, $account);
        $this->service->forceDelete($account);

        return $this->deleted('Account permanently deleted.');
    }

    /**
     * Get accounts as tree structure (hierarchy).
     */
    public function tree(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $orgId = $this->getActiveOrgId($request);
        $options = $request->only([
            'include_inactive',
            'max_depth',
            'types',
            'include_balances',
        ]);
        
        $tree = $this->service->getTree($orgId, $options);

        return $this->success($tree);
    }

    /**
     * Toggle account status (active/inactive).
     */
    public function toggleStatus(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $account = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $account);
        
        $isActive = $request->input('is_active', !$account->is_active);
        $updated = $this->service->toggleStatus($account, $isActive);

        return $this->success(
            $isActive ? 'Account activated successfully' : 'Account deactivated successfully',
            ['is_active' => $updated->is_active]
        );
    }

    /**
     * Bulk update multiple accounts.
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $this->authorizeAction($request);
        // $this->ensureAdmin($request);

        $validated = $request->validate([
            'accounts' => 'required|array',
            'accounts.*.id' => 'required|exists:accounts,id',
            'accounts.*.is_active' => 'nullable|boolean',
            'accounts.*.is_taxable' => 'nullable|boolean',
            'accounts.*.description' => 'nullable|string',
        ]);

        $orgId = $this->getActiveOrgId($request);
        $results = $this->service->bulkUpdate($validated['accounts'], $orgId);

        return $this->success(
            "{$results['updated']} accounts updated successfully",
            $results
        );
    }

    /**
     * Get account types with counts.
     */
    public function getTypes(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $orgId = $this->getActiveOrgId($request);
        $types = $this->service->getAccountTypesWithCounts($orgId);

        return $this->success($types);
    }

    /**
     * Get chart of accounts structure.
     */
    public function chart(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $orgId = $this->getActiveOrgId($request);
        $filters = $request->only(['type', 'include_balances', 'as_of_date']);
        
        $chart = $this->service->getChartOfAccounts($orgId, $filters);

        return $this->success($chart);
    }

    /**
     * Get trial balance for accounts.
     */
    public function trialBalance(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $orgId = $this->getActiveOrgId($request);
        $params = $request->validate([
            'as_of_date' => 'nullable|date',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'include_zero_balance' => 'nullable|boolean',
            'account_type' => 'nullable|in:Asset,Liability,Equity,Revenue,Expense',
        ]);
        
        $trialBalance = $this->service->getTrialBalance($orgId, $params);

        return $this->success($trialBalance);
    }

    /**
     * Get account balance.
     */
    public function getBalance(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $account = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $account);

        $asOfDate = $request->input('as_of_date');
        $balance = $this->service->getAccountBalance($account, $asOfDate);

        return $this->success([
            'account_id' => $account->id,
            'account_code' => $account->code,
            'account_name' => $account->name,
            'current_balance' => $balance,
            'current_balance_formatted' => number_format($balance, 2),
            'as_of_date' => $asOfDate ?? date('Y-m-d'),
            'normal_balance' => $account->normal_balance,
        ]);
    }

    /**
     * Export accounts to CSV/Excel.
     */
    public function export(Request $request): JsonResponse
    {
        $this->authorizeAction($request);
        
        $orgId = $this->getActiveOrgId($request);
        $format = $request->input('format', 'csv');
        $filters = $request->only(['type', 'is_active', 'is_group']);
        
        $exportUrl = $this->service->export($orgId, $format, $filters);

        return $this->success([
            'message' => 'Export initiated successfully',
            'download_url' => $exportUrl,
            'format' => $format,
        ]);
    }

    /**
     * Import accounts from CSV/Excel.
     */
    public function import(Request $request): JsonResponse
    {
        $this->authorizeAction($request);
        $this->ensureAdmin($request);

        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:5120',
            'overwrite' => 'nullable|boolean',
        ]);

        $orgId = $this->getActiveOrgId($request);
        $result = $this->service->import(
            $request->file('file'),
            $orgId,
            $request->input('overwrite', false)
        );

        return $this->success(
            "Imported {$result['imported']} accounts, skipped {$result['skipped']}",
            $result
        );
    }

    /**
     * Get account hierarchy.
     */
    public function hierarchy(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $account = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $account);

        $hierarchy = [
            'current' => [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'full_code' => $account->full_code,
                'hierarchy' => $account->hierarchy,
            ],
            'parent' => $account->parent ? [
                'id' => $account->parent->id,
                'code' => $account->parent->code,
                'name' => $account->parent->name,
            ] : null,
            'children' => AccountResource::collection($account->children),
            'depth' => $account->level,
        ];

        return $this->success($hierarchy);
    }

    /**
     * Get accounts for dropdown/select.
     */
    public function selectList(Request $request): JsonResponse
    {
        $this->authorizeAction($request);

        $orgId = $this->getActiveOrgId($request);
        $filters = $request->only(['type', 'is_group', 'search']);
        
        $accounts = $this->service->getSelectList($orgId, $filters);

        return $this->success($accounts);
    }

    /**
     * Get account summary with journals.
     */
    public function summary(Request $request, int $id): JsonResponse
    {
        $orgId = $this->getActiveOrgId($request);
        $account = $this->service->find($id, $orgId);

        $this->authorizeAction($request, $account);

        $period = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $summary = $this->service->getAccountSummary($account, $period['from_date'], $period['to_date']);

        return $this->success($summary);
    }
}