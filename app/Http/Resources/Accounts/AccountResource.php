<?php

namespace App\Http\Resources\Accounts;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    /**
    * Transform the resource into an array.
    */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'code' => $this->code,
            'full_code' => $this->when($this->relationLoaded('parent'), $this->full_code),
            'description' => $this->description,
            'level' => $this->level,
            'currency_code' => $this->currency_code,
            'opening_balance' => (float) $this->opening_balance,
            'opening_balance_formatted' => $this->opening_balance_formatted,
            'opening_balance_date' => $this->opening_balance_date?->format('Y-m-d'),
            'is_taxable' => $this->is_taxable,
            'automatic_postings_disabled' => $this->automatic_postings_disabled,
            'type' => $this->type,
            'normal_balance' => $this->normal_balance,
            'is_group' => $this->is_group,
            'is_active' => $this->is_active,
            'status' => $this->is_active ? 'active' : 'inactive',
            
            // Computed fields (when requested)
            'current_balance' => $this->when(
                $request->input('include_balances', false),
                fn() => (float) $this->current_balance
            ),
            'current_balance_formatted' => $this->when(
                $request->input('include_balances', false),
                fn() => number_format($this->current_balance, 2)
            ),
            
            // Hierarchy (when loaded)
            'parent' => $this->whenLoaded('parent', fn() => new AccountResource($this->parent)),
            'children' => $this->whenLoaded('children', fn() => AccountResource::collection($this->children)),
            
            // Timestamps
            // 'created_at' => $this->created_at?->toISOString(),
            // 'updated_at' => $this->updated_at?->toISOString(),
            // 'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     */
    public function with(Request $request): array
    {
        return array_merge(parent::with($request), [
            'account_type_info' => $this->when($request->input('include_meta', false), [
                'type_label' => $this->getTypeLabel(),
                'can_have_children' => $this->is_group,
                'is_leaf' => !$this->is_group && $this->children->isEmpty(),
                'balance_direction' => $this->normal_balance === 'Debit' ? 'left' : 'right',
            ]),
        ]);
    }

    /**
     * Get human-readable account type label.
     */
    protected function getTypeLabel(): string
    {
        $labels = [
            'Asset' => 'Assets',
            'Liability' => 'Liabilities',
            'Equity' => 'Equity',
            'Revenue' => 'Revenue',
            'Expense' => 'Expenses',
        ];

        return $labels[$this->type] ?? $this->type;
    }
}
