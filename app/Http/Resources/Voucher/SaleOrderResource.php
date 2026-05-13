<?php

namespace App\Http\Resources\Voucher;

use Illuminate\Http\Request;
use App\Http\Resources\Auth\UserResource;
use App\Http\Resources\Core\BranchResource;
use App\Http\Resources\Core\OutletResource;
use App\Http\Resources\Core\WarehouseResource;
use App\Http\Resources\Partner\CustomerResource;
use Illuminate\Http\Resources\Json\JsonResource;
// use App\Http\Resources\Voucher\VoucherTypeResource;

class SaleOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_number' => $this->document_number,
            'order_date' => $this->order_date->format('Y-m-d'),
            'delivery_date' => $this->delivery_date?->format('Y-m-d'),
            'grand_total' => number_format($this->grand_total, 4),
            'status' => $this->status,
            'status_label' => $this->getStatusLabel(),
            
            // Relationships
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'warehouse' => new WarehouseResource($this->whenLoaded('warehouse')),
            'outlet' => new OutletResource($this->whenLoaded('outlet')),
            // 'voucher_type' => new VoucherTypeResource($this->whenLoaded('voucherType')),
            'financial_year' => $this->whenLoaded('financialYear', fn() => [
                'id' => $this->financialYear->id,
                'name' => $this->financialYear->name,
                'start_date' => $this->financialYear->start_date->format('Y-m-d'),
                'end_date' => $this->financialYear->end_date->format('Y-m-d'),
            ]),
            
            // Items
            'items' => SaleOrderItemResource::collection($this->whenLoaded('items')),
            
            // Workflow data
            'submitted_at' => $this->submitted_at?->format('Y-m-d H:i:s'),
            'reviewed_at' => $this->reviewed_at?->format('Y-m-d H:i:s'),
            'approved_at' => $this->approved_at?->format('Y-m-d H:i:s'),
            'rejected_at' => $this->rejected_at?->format('Y-m-d H:i:s'),
            'approval_attempts' => $this->approval_attempts,
            'rejection_reason' => $this->rejection_reason,
            'rejection_details' => $this->rejection_details,
            
            // Workflow users
            'created_by' => new UserResource($this->whenLoaded('creator')),
            'reviewed_by' => new UserResource($this->whenLoaded('reviewer')),
            'approved_by' => new UserResource($this->whenLoaded('approver')),
            'rejected_by' => new UserResource($this->whenLoaded('rejector')),
            'updated_by' => new UserResource($this->whenLoaded('editor')),
            
            // Audit data
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
            
            // Additional data
            'can_edit' => $this->isEditable(),
            'can_delete' => $this->isDeletable(),
            'can_submit' => $this->canTransitionTo('submitted'),
            'can_review' => $this->isReviewable(),
            'can_approve' => $this->isApprovable(),
            'can_confirm' => $this->status === 'approved',
            'can_cancel' => $this->canTransitionTo('cancelled'),
            
            // Comments & Attachments
            'comments' => DocumentCommentResource::collection($this->whenLoaded('comments')),
            'attachments' => DocumentAttachmentResource::collection($this->whenLoaded('attachments')),
            'status_history' => DocumentStatusHistoryResource::collection($this->whenLoaded('statusHistory')),
        ];
    }
    
    /**
     * Get human-readable status label
     */
    protected function getStatusLabel(): string
    {
        return match($this->status) {
            'draft' => 'Draft',
            'submitted' => 'Submitted for Approval',
            'reviewed' => 'Reviewed',
            'approved' => 'Approved',
            'confirmed' => 'Confirmed',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            'rejected' => 'Rejected',
            default => ucfirst($this->status),
        };
    }
}