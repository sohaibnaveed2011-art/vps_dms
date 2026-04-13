<?php

namespace App\Http\Resources\Account;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GlTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'account_id' => $this->account_id,
            'date' => $this->date,
            'debit' => $this->debit,
            'credit' => $this->credit,
            'narration' => $this->narration,
            'document_number' => $this->document_number,

            // Audit and Reference
            'reference_source' => [
                'type' => $this->reference_type,
                'id' => $this->reference_id,
                // Load the actual source model for full context
                'document' => $this->whenLoaded('reference', function () {
                    return $this->reference ? $this->reference->toArray() : null;
                }),
            ],

            'created_by' => $this->created_by,
            'created_at' => $this->created_at,

            // Relationships
            'account' => new AccountResource($this->whenLoaded('account')),
            'user' => new UserResource($this->whenLoaded('createdBy')),
        ];
    }
}
